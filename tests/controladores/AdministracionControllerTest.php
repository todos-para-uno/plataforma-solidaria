
<?php

require_once __DIR__ . '/../../app/controladores/AdministracionController.php';
require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../../app/repositorios/CampRepository.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function administracionControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $sesionOriginal = $_SESSION;
    $codigoOriginal = http_response_code();

    $usuarios = new UsuariosRepository($pdo);
    $campanas = new CampRepository($pdo);

    $controlador = new AdministracionController(
        $usuarios,
        $campanas
    );

    $pdo->beginTransaction();

    try {
        $_SESSION = [];

        // 1. Un visitante no puede acceder al panel.
        http_response_code(200);

        ob_start();
        $controlador->mostrar('.', './assets', null);
        $html = ob_get_clean();

        TestUtils::comprobar(
            http_response_code() === 303 && $html === '',
            'Administracion redirige al visitante sin mostrar el panel'
        );

        // 2. Crear un usuario común para las pruebas.
        $validacion = (new ValidacionesRepository($pdo))
            ->findByNivel(1);

        $idUsuario = (int) $usuarios->insertarUsuario(
            Usuario::crear(
                'Usuario comun de prueba',
                'admin-test-comun-' . bin2hex(random_bytes(8))
                    . '@example.com',
                'PasswordAdmin123',
                $validacion->getId()
            )
        );

        $usuarioComun = $usuarios->findById($idUsuario);

        TestUtils::comprobar(
            !$usuarioComun->getAdmin(),
            'El usuario de prueba no tiene permisos administrativos'
        );

        // 3. Un usuario común tampoco puede acceder.
        $_SESSION = ['usuario_id' => $idUsuario];
        http_response_code(200);

        ob_start();
        $controlador->mostrar('.', './assets', $usuarioComun);
        $html = ob_get_clean();

        TestUtils::comprobar(
            http_response_code() === 303 && $html === '',
            'Administracion rechaza al usuario comun'
        );

        // 4. Crear un administrador de prueba.
        $idAdministrador = (int) $usuarios->insertarUsuario(
            Usuario::crear(
                'Administrador de prueba',
                'admin-test-' . bin2hex(random_bytes(8))
                    . '@example.com',
                'PasswordAdmin123',
                $validacion->getId()
            )
        );

        // La creación normal no concede privilegios administrativos.
        // Los asignamos directamente en la BD de prueba.
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET admin = 1 WHERE id = :id'
        );
        $stmt->execute(['id' => $idAdministrador]);

        $administrador = $usuarios->findById($idAdministrador);

        TestUtils::comprobar(
            $administrador->getAdmin(),
            'El administrador de prueba tiene permisos'
        );

        // 5. Obtener los valores esperados antes de renderizar.
        $totalUsuarios = count($usuarios->findAll());
        $totalActivas = count(
            $campanas->findByEstado(EstadoCamp::Activa)
        );
        $totalFinalizadas = count(
            $campanas->findByEstado(EstadoCamp::Finalizada)
        );

        // 6. El administrador puede acceder al panel.
        $_SESSION = ['usuario_id' => $idAdministrador];
        http_response_code(200);

        ob_start();
        $controlador->mostrar('.', './assets', $administrador);
        $html = ob_get_clean();

        TestUtils::comprobar(
            http_response_code() === 200,
            'Administracion permite el acceso al administrador'
        );

        TestUtils::comprobar(
            str_contains($html, 'Administración'),
            'Administracion muestra el titulo del panel'
        );

        TestUtils::comprobar(
            str_contains($html, 'Administrador de prueba'),
            'Administracion muestra el nombre del administrador'
        );

        // 7. Comprobar los tres contadores en la vista.
        $comprobarContador = static function (
            string $html,
            int $cantidad,
            string $etiqueta
        ): bool {
            $patron = '/<span class="dato-cifra">\s*'
                . preg_quote((string) $cantidad, '/')
                . '\s*<\/span>\s*'
                . '<span class="dato-etiqueta">\s*'
                . preg_quote($etiqueta, '/')
                . '\s*<\/span>/u';

            return preg_match($patron, $html) === 1;
        };

        TestUtils::comprobar(
            $comprobarContador(
                $html,
                $totalUsuarios,
                'Usuarios registrados'
            ),
            'Administracion muestra el total de usuarios'
        );

        TestUtils::comprobar(
            $comprobarContador(
                $html,
                $totalActivas,
                'Campañas activas'
            ),
            'Administracion muestra el total de campañas activas'
        );

        TestUtils::comprobar(
            $comprobarContador(
                $html,
                $totalFinalizadas,
                'Campañas finalizadas'
            ),
            'Administracion muestra el total de campañas finalizadas'
        );

        // 8. El panel conserva el token CSRF del menú.
        TestUtils::comprobar(
            str_contains(
                $html,
                'name="csrf_token" value="' . Csrf::generar() . '"'
            ),
            'Administracion incluye el token CSRF para cerrar sesion'
        );

    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION = $sesionOriginal;
        http_response_code($codigoOriginal ?: 200);
    }
}
