<?php

require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../app/controladores/IngresoController.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function ingresoControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $sesionOriginal = $_SESSION;
    $postOriginal = $_POST;
    $codigoOriginal = http_response_code();
    $usuarios = new UsuariosRepository($pdo);
    $controlador = new IngresoController($usuarios);
    $mail = 'ingreso-test-' . bin2hex(random_bytes(8)) . '@example.com';
    $password = 'PasswordIngreso123';
    $pdo->beginTransaction();

    try {
        $_SESSION = [];
        $token = Csrf::generar();
        $validacion = (new ValidacionesRepository($pdo))->findByNivel(1);
        $id = (int) $usuarios->insertarUsuario(
            Usuario::crear('Usuario de ingreso', $mail, $password, $validacion->getId())
        );

        ob_start();
        $controlador->mostrarFormulario('.', './assets');
        $html = ob_get_clean();
        TestUtils::comprobar(str_contains($html, $token), 'El formulario incluye el token CSRF');

        $casos = [
            [['mail' => $mail, 'pass' => 'incorrecta', 'csrf_token' => $token], 422],
            [['mail' => 'no-existe-' . $mail, 'pass' => $password, 'csrf_token' => $token], 422],
            [['mail' => '', 'pass' => '', 'csrf_token' => $token], 422],
            [['mail' => [$mail], 'pass' => $password, 'csrf_token' => $token], 400],
            [['mail' => $mail, 'pass' => [$password], 'csrf_token' => $token], 400],
            [['mail' => $mail, 'pass' => $password], 403],
            [['mail' => $mail, 'pass' => $password, 'csrf_token' => ['invalido']], 403],
            [['mail' => $mail, 'pass' => $password, 'csrf_token' => 'invalido'], 403],
        ];
        $respuestas = [];
        foreach ($casos as [$post, $codigo]) {
            $_POST = $post;
            http_response_code(200);
            ob_start();
            $controlador->procesarIngreso('.', './assets');
            $respuestas[] = ob_get_clean();
            TestUtils::comprobar(http_response_code() === $codigo, 'El ingreso inválido devuelve HTTP ' . $codigo);
            TestUtils::comprobar(!isset($_SESSION['usuario_id']), 'El ingreso rechazado no autentica al usuario');
        }
        $mensaje = 'El correo o la contraseña son incorrectos.';
        TestUtils::comprobar(str_contains($respuestas[0], $mensaje) && str_contains($respuestas[1], $mensaje), 'Correo inexistente y contraseña incorrecta muestran el mismo mensaje');
        TestUtils::comprobar(str_contains($respuestas[0], $mail), 'Se conserva el correo después de un error');
        TestUtils::comprobar(!str_contains($respuestas[0], 'value="incorrecta"'), 'La contraseña no se vuelve a mostrar');

        $sesionAnterior = session_id();
        $_POST = ['mail' => ' ' . $mail . ' ', 'pass' => $password, 'csrf_token' => $token];
        $controlador->procesarIngreso('.', './assets');
        TestUtils::comprobar(http_response_code() === 303, 'El ingreso exitoso redirige');
        TestUtils::comprobar(($_SESSION['usuario_id'] ?? null) === $id, 'La sesión guarda el ID del usuario');
        TestUtils::comprobar(session_id() !== $sesionAnterior, 'El ingreso renueva el identificador de sesión');
        TestUtils::comprobar(!Csrf::validar($token), 'El ingreso renueva el token CSRF');
        TestUtils::comprobar(!isset($_SESSION['pass']), 'La sesión no guarda la contraseña');

        $_POST = ['csrf_token' => $token];
        ob_start();
        $controlador->cerrarSesion('.');
        ob_end_clean();
        TestUtils::comprobar(http_response_code() === 403 && isset($_SESSION['usuario_id']), 'Un token inválido no cierra la sesión');

        $_POST = ['csrf_token' => Csrf::generar()];
        $controlador->cerrarSesion('.');
        TestUtils::comprobar(http_response_code() === 303, 'El cierre de sesión redirige');
        TestUtils::comprobar($_SESSION === [] && session_status() === PHP_SESSION_NONE, 'El cierre elimina la sesión del usuario');
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = $sesionOriginal;
        $_POST = $postOriginal;
        http_response_code($codigoOriginal ?: 200);
    }
}
