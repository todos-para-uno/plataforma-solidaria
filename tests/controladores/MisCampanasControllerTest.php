<?php
require_once __DIR__ . '/../../app/controladores/MisCampanasController.php';
require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function misCampanasControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $sesionOriginal = $_SESSION;
    $codigoOriginal = http_response_code();
    $pdo->beginTransaction();
    try {
        $_SESSION = [];
        $controlador = new MisCampanasController();
        http_response_code(200);
        ob_start();
        $controlador->mostrar('.', './assets');
        $html = ob_get_clean();
        TestUtils::comprobar(http_response_code() === 302 && $html === '', 'Mis campañas redirige sin un usuario autenticado');

        $usuarios = new UsuariosRepository($pdo);
        $validacion = (new ValidacionesRepository($pdo))->findByNivel(1);
        $ids = [];
        for ($i = 0; $i < 2; $i++) {
            $ids[] = $usuarios->insertarUsuario(Usuario::crear(
                'Usuario campañas ' . $i,
                'campanas-' . bin2hex(random_bytes(8)) . '@example.com',
                'PasswordCampanas123',
                $validacion->getId()
            ));
        }
        $stmt = $pdo->prepare('INSERT INTO cat_camp (nombre) VALUES (:nombre)');
        $stmt->execute(['nombre' => 'Categoria-' . bin2hex(random_bytes(8))]);
        $categoriaId = (int) $pdo->lastInsertId();
        $campanas = new CampRepository($pdo);
        foreach ($ids as $i => $id) {
            $campanas->save([
                'owner_id' => $id,
                'nombre' => 'Campaña de prueba propietario ' . $i,
                'monetaria' => 1,
                'categoria' => $categoriaId,
                'cbu' => str_repeat('0', 22),
                'logrado' => 100,
                'estado' => 'activa',
            ]);
        }
        // El ingreso guarda solo usuario_id; no hay nombre ni admin en la sesión.
        $_SESSION = ['usuario_id' => (int) $ids[0]];
        $usuario = $usuarios->findById($ids[0]);
        http_response_code(200);
        ob_start();
        $controlador->mostrar('.', './assets', $usuario);
        $html = ob_get_clean();
        TestUtils::comprobar(http_response_code() === 200, 'Mis campañas acepta el usuario del inicio de sesión');
        TestUtils::comprobar(str_contains($html, 'Usuario campañas 0'), 'El nombre se obtiene del objeto Usuario');
        TestUtils::comprobar(str_contains($html, 'Campaña de prueba propietario 0') && !str_contains($html, 'Campaña de prueba propietario 1'), 'Se muestran solo las campañas del usuario autenticado');
        TestUtils::comprobar(str_contains($html, 'name="csrf_token" value="' . Csrf::generar() . '"'), 'El menú de Mis campañas permite salir con CSRF válido');
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION = $sesionOriginal;
        http_response_code($codigoOriginal ?: 200);
    }
}
