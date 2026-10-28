<?php
require_once __DIR__ . '/../../app/repositorios/EstadoAutenticacionRepository.php';
require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function estadoAutenticacionRepositoryTest(PDO $pdo): void
{
    $pdo->beginTransaction();
    try {
        $validacion = (new ValidacionesRepository($pdo))->findByNivel(1);
        $id = (int) (new UsuariosRepository($pdo))->save([
            'nombre' => 'Prueba estado de autenticacion',
            'mail' => 'estado-' . bin2hex(random_bytes(8)) . '@example.com',
            'pass' => password_hash('Prueba123456', PASSWORD_DEFAULT),
            'id_validacion' => $validacion->getId(),
        ]);
        $repo = new EstadoAutenticacionRepository($pdo);
        TestUtils::comprobar(!$repo->estaBloqueado($id), 'Cuenta nueva sin bloqueo');
        for ($n = 1; $n <= 4; $n++) {
            TestUtils::comprobar(!$repo->registrarFallo($id), 'Fallo ' . $n . ' sin bloqueo');
        }
        TestUtils::comprobar($repo->registrarFallo($id), 'Quinto fallo activa el bloqueo');
        TestUtils::comprobar($repo->estaBloqueado($id), 'Bloqueo persistido');
        $repo->limpiar($id);
        TestUtils::comprobar(!$repo->estaBloqueado($id), 'Limpieza elimina bloqueo');
        TestUtils::comprobar((int) $repo->estado($id)['intentos_fallidos'] === 0, 'Contador restablecido');
        $pdo->prepare('UPDATE estado_autenticacion SET intentos_fallidos = 5, bloqueada_hasta = CURRENT_TIMESTAMP(6) - INTERVAL 1 SECOND WHERE id_usuario = :id')->execute(['id' => $id]);
        TestUtils::comprobar(!$repo->registrarFallo($id), 'Fallo posterior al vencimiento inicia una cadena nueva');
        TestUtils::comprobar((int) $repo->estado($id)['intentos_fallidos'] === 1, 'Contador reiniciado tras vencimiento');
    } finally {
        $pdo->rollBack();
    }
}
