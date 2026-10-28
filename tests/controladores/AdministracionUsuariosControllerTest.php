<?php
require_once __DIR__ . '/../../app/controladores/AdministracionUsuariosController.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function administracionUsuariosControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $sesion = $_SESSION;
    $post = $_POST;
    $get = $_GET;
    $codigo = http_response_code();
    $usuarios = new UsuariosRepository($pdo);
    $estados = new EstadoAutenticacionRepository($pdo);
    $controlador = new AdministracionUsuariosController($pdo, $usuarios, $estados);
    $pdo->beginTransaction();
    try {
        $validacion = (new ValidacionesRepository($pdo))->findByNivel(1);
        $ids = [];
        foreach ([['Administrador', 1], ['Segundo administrador', 1], ['Usuario buscable', 0]] as [$nombre, $admin]) {
            $ids[] = (int) $usuarios->save([
                'nombre' => $nombre,
                'mail' => 'admin-api-' . bin2hex(random_bytes(8)) . '@example.com',
                'pass' => password_hash('Anterior1234', PASSWORD_DEFAULT),
                'admin' => $admin,
                'id_validacion' => $validacion->getId(),
            ]);
        }
        [$idAdmin, $idOtroAdmin, $idUsuario] = $ids;
        $solicitar = static function (string $accion, ?int $actor, array $datos = [], bool $csrf = true) use ($controlador): array {
            $_SESSION = $actor === null ? [] : ['usuario_id' => $actor, 'admin' => true];
            $_POST = $datos;
            if ($csrf && $actor !== null) $_POST['csrf_token'] = Csrf::generar();
            $_GET = $datos;
            http_response_code(200);
            ob_start();
            try {
                if ($accion === 'buscar') $controlador->buscar();
                else $controlador->password($accion === 'desbloquear');
                $salida = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            return [http_response_code(), json_decode($salida, true)];
        };
        [$http] = $solicitar('buscar', null);
        TestUtils::comprobar($http === 401, 'API administrativa: visitante rechazado.');
        [$http] = $solicitar('buscar', $idUsuario);
        TestUtils::comprobar($http === 403, 'API administrativa: usuario común rechazado aunque la sesión diga admin.');
        [$http, $datos] = $solicitar('buscar', $idAdmin, ['q' => 'Usuario buscable', 'limite' => '15', 'pagina' => '1']);
        TestUtils::comprobar($http === 200 && count($datos['usuarios'] ?? []) === 1 && (int) $datos['usuarios'][0]['id'] === $idUsuario, 'API administrativa: búsqueda paginada por nombre.');
        TestUtils::comprobar(!array_key_exists('pass', $datos['usuarios'][0]), 'API administrativa: búsqueda no revela hashes.');
        [$http] = $solicitar('buscar', $idAdmin, ['limite' => '999']);
        TestUtils::comprobar($http === 422, 'API administrativa: límite inválido rechazado.');
        $form = ['id_usuario' => (string) $idUsuario, 'password' => 'NuevaClave1234', 'confirmacion' => 'NuevaClave1234'];
        [$http] = $solicitar('password', $idAdmin, $form, false);
        TestUtils::comprobar($http === 403 && $usuarios->findById($idUsuario)->verificarPassword('Anterior1234'), 'API administrativa: CSRF obligatorio y sin cambios ante rechazo.');
        [$http] = $solicitar('password', $idAdmin, array_replace($form, ['confirmacion' => 'Diferente1234']));
        TestUtils::comprobar($http === 422 && $usuarios->findById($idUsuario)->verificarPassword('Anterior1234'), 'API administrativa: contraseñas distintas no alteran la cuenta.');
        foreach ([$idAdmin, $idOtroAdmin] as $destino) {
            [$http] = $solicitar('password', $idAdmin, array_replace($form, ['id_usuario' => (string) $destino]));
            TestUtils::comprobar($http === 403, 'API administrativa: prohibido cambiar contraseñas de administradores.');
        }
        [$http] = $solicitar('password', $idAdmin, $form);
        TestUtils::comprobar($http === 200 && $usuarios->findById($idUsuario)->verificarPassword('NuevaClave1234'), 'API administrativa: restablecimiento manual actualiza el hash.');
        for ($i = 0; $i < 5; $i++) $estados->registrarFallo($idUsuario);
        TestUtils::comprobar($estados->estaBloqueado($idUsuario), 'API administrativa: cuenta de prueba bloqueada.');
        [$http] = $solicitar('desbloquear', $idAdmin, array_replace($form, ['password' => 'Desbloqueada1234', 'confirmacion' => 'Desbloqueada1234']));
        $estado = $estados->estado($idUsuario);
        TestUtils::comprobar($http === 200 && !$estados->estaBloqueado($idUsuario) && (int) $estado['intentos_fallidos'] === 0 && $usuarios->findById($idUsuario)->verificarPassword('Desbloqueada1234'), 'API administrativa: desbloqueo actualiza contraseña y reinicia intentos.');
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION = $sesion;
        $_POST = $post;
        $_GET = $get;
        http_response_code($codigo);
    }
}
