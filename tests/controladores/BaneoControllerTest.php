<?php
require_once __DIR__ . '/../../app/controladores/BaneoController.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function baneoControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $sesionOriginal = $_SESSION;
    $postOriginal = $_POST;
    $getOriginal = $_GET;
    $servidorOriginal = $_SERVER;
    $codigoOriginal = http_response_code();
    $usuarios = new UsuariosRepository($pdo);
    $baneos = new BaneoRepository($pdo);
    $pdo->beginTransaction();

    try {
        $validacion = (new ValidacionesRepository($pdo))->findByNivel(1);
        $ids = [];
        foreach (['Admin', 'Otro admin', 'Usuario', 'Otro usuario'] as $indice => $nombre) {
            $ids[] = (int) $usuarios->save([
                'nombre' => $nombre,
                'mail' => 'baneo-controller-' . bin2hex(random_bytes(8)) . '@example.com',
                'pass' => password_hash('PasswordPrueba123', PASSWORD_DEFAULT),
                'admin' => (int) ($indice < 2),
                'id_validacion' => $validacion->getId(),
            ]);
        }
        [$admin, $otroAdmin, $usuario, $otroUsuario] = $ids;
        $postValido = ['id_usuario' => (string) $usuario, 'motivo' => 'Fraude'];

        // Pasar por las rutas reales para verificar también métodos HTTP y sesión.
        $solicitar = static function (string $ruta, ?int $actor, array $datos, string $metodo = 'POST'): array {
            $_SESSION = $actor === null ? [] : ['usuario_id' => $actor, 'admin' => true];
            $_POST = array_replace(['csrf_token' => Csrf::generar()], $datos);
            $_GET = ['ruta' => $ruta];
            $_SERVER['REQUEST_METHOD'] = $metodo;
            http_response_code(200);
            ob_start();
            try {
                require __DIR__ . '/../../public_php/index.php';
                $html = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            return [http_response_code(), json_decode($html, true)];
        };

        foreach (['banear', 'finalizar_baneo'] as $ruta) {
            foreach (['GET', 'HEAD', 'PUT'] as $metodo) {
                [$codigo] = $solicitar($ruta, $admin, $postValido, $metodo);
                TestUtils::comprobar($codigo === 405, "$ruta rechaza el método $metodo.");
            }
            [$codigo] = $solicitar($ruta, null, $postValido);
            TestUtils::comprobar($codigo === 401, "$ruta requiere una sesión autenticada.");
            [$codigo] = $solicitar($ruta, $usuario, $postValido);
            TestUtils::comprobar($codigo === 403, "$ruta no acepta un usuario común aunque la sesión diga admin=true.");
            foreach ([null, 'incorrecto', ['token']] as $token) {
                [$codigo] = $solicitar($ruta, $admin, $postValido + ['csrf_token' => $token]);
                TestUtils::comprobar($codigo === 403, "$ruta exige un token CSRF válido.");
            }
        }

        foreach ([$admin, $otroAdmin] as $destino) {
            [$codigo] = $solicitar('banear', $admin, ['id_usuario' => $destino, 'motivo' => 'Prueba']);
            TestUtils::comprobar(
                $codigo === 403 && $baneos->findByIdUsuario($destino) === [],
                'No permite banearse a sí mismo ni a otro administrador, sin crear registros.'
            );
        }

        $casosInvalidos = [
            [['id_usuario' => [$usuario]], 400],
            [['id_usuario' => 0], 400],
            [['id_usuario' => '1.5'], 400],
            [['motivo' => []], 400],
            [['motivo' => '   '], 422],
            [['motivo' => str_repeat('á', 501)], 422],
            [['fecha_fin' => []], 400],
            [['fecha_fin' => '2026-02-30 12:00:00'], 422],
            [['fecha_fin' => "2030-01-01\0"], 422],
            [['fecha_fin' => '2000-01-01 00:00:00'], 422],
        ];
        foreach ($casosInvalidos as [$datos, $esperado]) {
            [$codigo] = $solicitar('banear', $admin, array_replace($postValido, $datos));
            TestUtils::comprobar($codigo === $esperado, 'Rechaza datos inválidos del baneo.');
        }
        TestUtils::comprobar($baneos->findByIdUsuario($usuario) === [], 'Los intentos rechazados no crean baneos.');

        [$codigo, $respuesta] = $solicitar('banear', $admin, $postValido + ['id_admin' => $otroAdmin]);
        $idBaneo = $respuesta['id_baneo'] ?? null;
        $baneo = $idBaneo === null ? null : $baneos->findById($idBaneo);
        TestUtils::comprobar(
            $codigo === 201 && $baneo instanceof Baneo
                && $baneo->getIdAdmin() === $admin
                && $baneo->getFechaFin() === null
                && $baneos->estaBaneado($usuario),
            'Crea un baneo permanente y obtiene el administrador de la sesión, ignorando el ID enviado.'
        );

        [$codigo] = $solicitar('banear', $admin, $postValido);
        TestUtils::comprobar(
            $codigo === 409 && count($baneos->findByIdUsuario($usuario)) === 1,
            'Rechaza un segundo baneo vigente.'
        );
        [$codigo] = $solicitar('finalizar_baneo', $usuario, ['id_baneo' => $idBaneo]);
        TestUtils::comprobar($codigo === 403 && $baneos->estaBaneado($usuario), 'Un usuario no puede quitar su propio baneo.');
        [$codigo] = $solicitar('finalizar_baneo', $admin, ['id_baneo' => $idBaneo, 'csrf_token' => null]);
        TestUtils::comprobar($codigo === 403 && $baneos->estaBaneado($usuario), 'Sin CSRF válido no se modifica el baneo.');

        [$codigo] = $solicitar('finalizar_baneo', $otroAdmin, ['id_baneo' => $idBaneo]);
        TestUtils::comprobar(
            $codigo === 200 && !$baneos->estaBaneado($usuario)
                && $baneos->estuvoBaneado($usuario)
                && count($baneos->findByIdUsuario($usuario)) === 1,
            'Otro administrador puede finalizar el baneo conservando su historial.'
        );
        [$codigo] = $solicitar('finalizar_baneo', $admin, ['id_baneo' => $idBaneo]);
        TestUtils::comprobar($codigo === 409, 'No vuelve a finalizar un baneo vencido.');
        [$codigo] = $solicitar('finalizar_baneo', $admin, ['id_baneo' => []]);
        TestUtils::comprobar($codigo === 400, 'Rechaza un ID de baneo inválido.');

        // Insertar y eliminar una entidad propia deja un ID conocido que ya no existe.
        $idAusente = $baneos->insertarBaneo(new Baneo($usuario, $admin, 'Temporal de prueba'));
        $baneos->delete($idAusente);
        [$codigo] = $solicitar('finalizar_baneo', $admin, ['id_baneo' => $idAusente]);
        TestUtils::comprobar($codigo === 404, 'Informa cuando el baneo no existe.');

        $manana = (new DateTimeImmutable($baneos->fechaActual()))->modify('+1 day')->format('Y-m-d\TH:i');
        [$codigo, $respuesta] = $solicitar('banear', $admin, [
            'id_usuario' => $otroUsuario, 'motivo' => 'Fraude', 'fecha_fin' => $manana,
        ]);
        TestUtils::comprobar(
            $codigo === 201 && $baneos->estaBaneado($otroUsuario)
                && $baneos->findById($respuesta['id_baneo'])->getFechaFin() === str_replace('T', ' ', $manana) . ':00',
            'Crea un baneo temporal aceptando el formato de fecha de un formulario HTML.'
        );

        $usuarios->save(['id' => $otroAdmin, 'admin' => 0]);
        [$codigo] = $solicitar('banear', $otroAdmin, $postValido);
        TestUtils::comprobar($codigo === 403, 'Los permisos se vuelven a consultar en la base en cada petición.');
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
