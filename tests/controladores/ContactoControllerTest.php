<?php
require_once __DIR__ . '/../../app/controladores/ContactoController.php';
require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function contactoControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $sesionOriginal = $_SESSION;
    $postOriginal = $_POST;
    $getOriginal = $_GET;
    $servidorOriginal = $_SERVER;
    $codigoOriginal = http_response_code();
    $repositorio = new ContactoRepository($pdo);
    $avisos = [];
    $controlador = new ContactoController($repositorio, static function ($destino, $asunto, $cuerpo, $cabeceras) use (&$avisos): bool {
        $avisos[] = compact('destino', 'asunto', 'cuerpo', 'cabeceras');
        return true;
    });
    $capturar = static function (Closure $accion): string {
        http_response_code(200);
        ob_start();
        try {
            $accion();
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    };
    $mail = 'contacto-' . bin2hex(random_bytes(8)) . '@example.com';
    $filasDePrueba = static function () use ($pdo, $mail): array {
        $stmt = $pdo->prepare('SELECT * FROM contactos WHERE mail = :mail ORDER BY id');
        $stmt->execute(['mail' => $mail]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    };
    $pdo->beginTransaction();

    try {
        $_SESSION = [];
        $token = Csrf::generar();
        $html = $capturar(static fn () => $controlador->mostrarFormulario('.', './assets'));
        TestUtils::comprobar(str_contains($html, $token), 'Contacto incluye un token CSRF en el formulario.');

        $datos = ['nombre' => ' Persona de prueba ', 'mail' => $mail, 'asunto' => 'consulta', 'contenido' => ' Necesito información. ', 'csrf_token' => $token];
        $casos = [
            [['csrf_token' => null], 403],
            [['csrf_token' => 'incorrecto'], 403],
            [['csrf_token' => []], 403],
            [['nombre' => []], 400],
            [['mail' => []], 400],
            [['asunto' => []], 400],
            [['contenido' => []], 400],
            [['nombre' => '  '], 422],
            [['nombre' => str_repeat('á', 101)], 422],
            [['mail' => "persona@example.com\r\nBcc: otro@example.com"], 422],
            [['mail' => 'correo-invalido'], 422],
            [['asunto' => 'inventado'], 422],
            [['contenido' => '  '], 422],
            [['contenido' => str_repeat('á', 1001)], 422],
        ];
        foreach ($casos as [$cambios, $esperado]) {
            $_POST = array_replace($datos, $cambios);
            $capturar(static fn () => $controlador->procesarContacto('.', './assets'));
            TestUtils::comprobar(http_response_code() === $esperado, 'Rechaza datos inválidos o un token incorrecto con HTTP ' . $esperado . '.');
        }
        TestUtils::comprobar($filasDePrueba() === [] && $avisos === [], 'Los envíos rechazados no guardan consultas ni envían correos.');

        $_POST = array_replace($datos, ['nombre' => '<script>alert(1)</script>', 'asunto' => 'invalido']);
        $html = $capturar(static fn () => $controlador->procesarContacto('.', './assets'));
        TestUtils::comprobar(
            !str_contains($html, '<script>alert(1)</script>') && str_contains($html, '&lt;script&gt;'),
            'Los datos conservados en el formulario se escapan al mostrarse.'
        );

        $_POST = $datos + ['id_usuario' => 12345];
        $capturar(static fn () => $controlador->procesarContacto('.', './assets'));
        $filas = $filasDePrueba();
        $contacto = $repositorio->findById($filas[0]['id']);
        TestUtils::comprobar(
            http_response_code() === 303 && count($filas) === 1
                && $contacto instanceof Contacto
                && $contacto->getNombre() === 'Persona de prueba'
                && $contacto->getContenido() === 'Necesito información.'
                && $contacto->getIdUsuario() === null
                && $contacto->getFechaHora() !== null
                && $contacto->getCorreoEnviado(),
            'Guarda e hidrata una consulta anónima e ignora el ID de usuario enviado en el formulario.'
        );
        TestUtils::comprobar(
            count($avisos) === 1
                && $avisos[0]['destino'] === 'contacto@todosparauno.test'
                && $avisos[0]['cabeceras']['Reply-To'] === $mail
                && str_contains($avisos[0]['cuerpo'], 'Necesito información.'),
            'El correo usa un destino simulado y la dirección validada como Reply-To.'
        );
        $html = $capturar(static fn () => $controlador->mostrarFormulario('.', './assets'));
        TestUtils::comprobar(str_contains($html, 'Recibimos tu consulta.'), 'Después del envío muestra la confirmación.');
        $html = $capturar(static fn () => $controlador->mostrarFormulario('.', './assets'));
        TestUtils::comprobar(
            !str_contains($html, 'Recibimos tu consulta.') && count($filasDePrueba()) === 1 && count($avisos) === 1,
            'Recargar el formulario no repite el guardado ni el envío.'
        );

        $usuarios = new UsuariosRepository($pdo);
        $nivel = (new ValidacionesRepository($pdo))->findByNivel(1);
        $idUsuario = (int) $usuarios->insertarUsuario(Usuario::crear('Usuario de contacto', 'usuario-' . $mail, 'PasswordPrueba123', $nivel->getId()));
        $usuario = $usuarios->findById($idUsuario);
        $_SESSION['usuario_id'] = $idUsuario;
        $html = $capturar(static fn () => $controlador->mostrarFormulario('.', './assets', $usuario));
        TestUtils::comprobar(
            str_contains($html, 'value="Usuario de contacto"') && str_contains($html, 'usuario-' . $mail),
            'Con sesión, precarga el nombre y el correo del usuario.'
        );
        $_POST = $datos;
        $capturar(static fn () => $controlador->procesarContacto('.', './assets', $usuario));
        $filas = $filasDePrueba();
        TestUtils::comprobar((int) $filas[1]['id_usuario'] === $idUsuario, 'Asocia la consulta al usuario autenticado.');

        $sinCorreo = new ContactoController($repositorio, static fn (): bool => false);
        $capturar(static fn () => $sinCorreo->procesarContacto('.', './assets', $usuario));
        $filas = $filasDePrueba();
        TestUtils::comprobar(
            http_response_code() === 303 && count($filas) === 3 && !(bool) $filas[2]['correo_enviado'],
            'Si falla el correo, conserva la consulta y confirma su recepción.'
        );
        $usuarios->delete($idUsuario);
        $filas = $filasDePrueba();
        TestUtils::comprobar($filas[1]['id_usuario'] === null, 'Al eliminar una cuenta se conserva la consulta sin su asociación.');

        // Ejercitar el enrutado real sin invocar el transporte de correo.
        $_SESSION = [];
        $_GET = ['ruta' => 'contacto'];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $html = $capturar(static function () { require __DIR__ . '/../../public_php/index.php'; });
        TestUtils::comprobar(http_response_code() === 200 && str_contains($html, Csrf::generar()), 'GET contacto pasa por el controlador.');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array_replace($datos, ['csrf_token' => 'invalido']);
        $capturar(static function () { require __DIR__ . '/../../public_php/index.php'; });
        TestUtils::comprobar(http_response_code() === 403, 'POST contacto está habilitado y verifica CSRF.');
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION = $sesionOriginal;
        $_POST = $postOriginal;
        $_GET = $getOriginal;
        $_SERVER = $servidorOriginal;
        http_response_code($codigoOriginal ?: 200);
    }
}
