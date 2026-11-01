<?php

require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../app/repositorios/BaneoRepository.php';
require_once __DIR__ . '/../../app/repositorios/EstadoAutenticacionRepository.php';
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
    $getOriginal = $_GET;
    $servidorOriginal = $_SERVER;
    $usuarios = new UsuariosRepository($pdo);
    $baneos = new BaneoRepository($pdo);
    $estadoAutenticacion = new EstadoAutenticacionRepository($pdo);
    $controlador = new IngresoController($usuarios, $baneos, $estadoAutenticacion);
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

        // Integración real: cinco intentos fallidos a través del controlador.
        $estadoAutenticacion->limpiar($id);
        $token = Csrf::generar();
        for ($intento = 1; $intento <= 5; $intento++) {
            $_POST = ['mail' => $mail, 'pass' => 'incorrecta', 'csrf_token' => $token];
            http_response_code(200);
            ob_start();
            $controlador->procesarIngreso('.', './assets');
            ob_end_clean();
            $estado = $estadoAutenticacion->estado($id);
            TestUtils::comprobar(
                http_response_code() === 422 && !isset($_SESSION['usuario_id'])
                    && (int) $estado['intentos_fallidos'] === $intento,
                'Ingreso: intento incorrecto ' . $intento . ' contabilizado sin autenticar.'
            );
            TestUtils::comprobar(
                $estadoAutenticacion->estaBloqueado($id) === ($intento === 5),
                'Ingreso: bloqueo solo al alcanzar cinco fallos (' . $intento . ').'
            );
        }

        $_POST = ['mail' => $mail, 'pass' => $password, 'csrf_token' => $token];
        http_response_code(200);
        ob_start();
        $controlador->procesarIngreso('.', './assets');
        $respuestaBloqueo = ob_get_clean();
        TestUtils::comprobar(
            http_response_code() === 403 && !isset($_SESSION['usuario_id'])
                && str_contains($respuestaBloqueo, 'bloqueada temporalmente'),
            'Ingreso: una contraseña correcta no evita el bloqueo vigente.'
        );
        TestUtils::comprobar(
            (int) $estadoAutenticacion->estado($id)['intentos_fallidos'] === 5,
            'Ingreso: intentar acceder durante el bloqueo no aumenta el contador.'
        );

        // Simular vencimiento con el reloj de MySQL, sin esperar una hora.
        $pdo->prepare('UPDATE estado_autenticacion SET bloqueada_hasta = DATE_SUB(CURRENT_TIMESTAMP(6), INTERVAL 1 SECOND) WHERE id_usuario = :id')
            ->execute(['id' => $id]);
        TestUtils::comprobar(!$estadoAutenticacion->estaBloqueado($id), 'Ingreso: el bloqueo vencido deja de estar activo.');
        $_POST = ['mail' => $mail, 'pass' => $password, 'csrf_token' => $token];
        http_response_code(200);
        $controlador->procesarIngreso('.', './assets');
        $estado = $estadoAutenticacion->estado($id);
        TestUtils::comprobar(
            http_response_code() === 303 && ($_SESSION['usuario_id'] ?? null) === $id,
            'Ingreso: la contraseña correcta permite entrar después del vencimiento.'
        );
        TestUtils::comprobar(
            (int) $estado['intentos_fallidos'] === 0 && $estado['bloqueada_hasta'] === null
                && $estado['ultimo_intento'] === null,
            'Ingreso: el acceso exitoso reinicia el contador y limpia el bloqueo.'
        );
        // Restaurar la sesión de visitante para las pruebas de baneos existentes.
        $_SESSION = [];
        $token = Csrf::generar();

        $ahora = new DateTimeImmutable($pdo->query('SELECT CURRENT_TIMESTAMP')->fetchColumn());
        $ayer = $ahora->modify('-1 day')->format('Y-m-d H:i:s');
        $manana = $ahora->modify('+1 day')->format('Y-m-d H:i:s');
        $adminId = (int) $usuarios->save([
            'nombre' => 'Administrador de prueba',
            'mail' => 'admin-' . $mail,
            'pass' => password_hash('PasswordAdmin123', PASSWORD_DEFAULT),
            'admin' => 1,
            'id_validacion' => $validacion->getId(),
        ]);
        $idBaneo = $baneos->insertarBaneo(new Baneo($id, $adminId, 'Fraude', $ayer));
        foreach ([null, $manana] as $fechaFin) {
            $baneos->save(['id' => $idBaneo, 'fecha_fin' => $fechaFin]);
            $_POST = ['mail' => $mail, 'pass' => $password, 'csrf_token' => $token];
            ob_start();
            $controlador->procesarIngreso('.', './assets');
            $html = ob_get_clean();
            TestUtils::comprobar(
                http_response_code() === 403
                    && !isset($_SESSION['usuario_id'])
                    && str_contains($html, 'Tu cuenta se encuentra baneada.'),
                'Un baneo vigente, permanente o temporal, impide ingresar.'
            );
        }
        $_POST['pass'] = 'incorrecta';
        ob_start();
        $controlador->procesarIngreso('.', './assets');
        $html = ob_get_clean();
        TestUtils::comprobar(
            http_response_code() === 422 && str_contains($html, $mensaje),
            'La contraseña incorrecta conserva el error de credenciales aunque el usuario esté baneado.'
        );
        $baneos->save(['id' => $idBaneo, 'fecha_fin' => $ayer]);

        $sesionAnterior = session_id();
        $_POST = ['mail' => ' ' . $mail . ' ', 'pass' => $password, 'csrf_token' => $token];
        $controlador->procesarIngreso('.', './assets');
        TestUtils::comprobar(http_response_code() === 303, 'El ingreso exitoso redirige');
        TestUtils::comprobar(($_SESSION['usuario_id'] ?? null) === $id, 'La sesión guarda el ID del usuario');
        TestUtils::comprobar(session_id() !== $sesionAnterior, 'El ingreso renueva el identificador de sesión');
        TestUtils::comprobar(!Csrf::validar($token), 'El ingreso renueva el token CSRF');
        TestUtils::comprobar(!isset($_SESSION['pass']), 'La sesión no guarda la contraseña');

        // Ejercitar el punto de entrada real con la misma conexión y transacción.
        $abrirRuta = static function (string $ruta): string {
            $_GET = ['ruta' => $ruta];
            $_SERVER['REQUEST_METHOD'] = 'GET';
            ob_start();
            require __DIR__ . '/../../public_php/index.php';
            return ob_get_clean();
        };
        http_response_code(200);
        $htmlBienvenida = $abrirRuta('contacto');
        TestUtils::comprobar(
            str_contains($htmlBienvenida, 'id="bienvenida-titulo"')
                && str_contains($htmlBienvenida, '¡Hola, Usuario de ingreso!')
                && !isset($_SESSION['mostrar_bienvenida']),
            'El ingreso muestra una bienvenida personalizada y la consume al renderizarla.'
        );
        TestUtils::comprobar(
            !str_contains($abrirRuta('contacto'), 'id="bienvenida-titulo"'),
            'La bienvenida no vuelve a aparecer al recargar o navegar.'
        );
        TestUtils::comprobar(
            http_response_code() === 200 && ($_SESSION['usuario_id'] ?? null) === $id,
            'Un antecedente vencido permite ingresar y mantener la sesión al navegar.'
        );

        $baneos->save(['id' => $idBaneo, 'fecha_inicio' => $manana, 'fecha_fin' => null]);
        $abrirRuta('contacto');
        TestUtils::comprobar(
            ($_SESSION['usuario_id'] ?? null) === $id,
            'Un baneo que aún no comenzó conserva la sesión abierta.'
        );

        $baneos->save(['id' => $idBaneo, 'fecha_inicio' => $ayer]);
        $tokenSesion = Csrf::generar();
        $sesionAntesDelBaneo = session_id();
        $html = $abrirRuta('mis_campanas');
        TestUtils::comprobar(
            http_response_code() === 403
                && $html === 'Tu cuenta se encuentra baneada.'
                && !isset($_SESSION['usuario_id'])
                && session_id() !== $sesionAntesDelBaneo
                && !Csrf::validar($tokenSesion),
            'Un baneo nuevo revoca la sesión y el token antes de ejecutar el controlador solicitado.'
        );

        $baneos->finalizarBaneo((int) $idBaneo, 'Nos equivocamos de usuario');
        $_POST = ['mail' => $mail, 'pass' => $password, 'csrf_token' => Csrf::generar()];
        $controlador->procesarIngreso('.', './assets');
        TestUtils::comprobar(
            ($_SESSION['usuario_id'] ?? null) === $id,
            'Al levantar manualmente un baneo permanente, el usuario puede volver a ingresar.'
        );

        http_response_code(200);
        $abrirRuta('contacto');
        TestUtils::comprobar(
            http_response_code() === 200 && ($_SESSION['usuario_id'] ?? null) === $id
                && $baneos->findById($idBaneo)->getFechaFin() === null,
            'Un baneo levantado conserva su vencimiento y no revoca la sesión al navegar.'
        );

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
        $_GET = $getOriginal;
        $_SERVER = $servidorOriginal;
        http_response_code($codigoOriginal ?: 200);
    }
}
