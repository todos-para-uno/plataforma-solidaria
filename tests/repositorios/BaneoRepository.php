<?php
require_once __DIR__ . '/../../app/repositorios/BaneoRepository.php';
require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function baneoRepositoryTest(PDO $pdo): void
{
    echo "INICIANDO TEST: app/repositorios/BaneoRepository.php\n";
    $repositorio = new BaneoRepository($pdo);
    $usuarios = new UsuariosRepository($pdo);
    $idValidacion = $pdo->query('SELECT id FROM validaciones WHERE nivel = 1')->fetchColumn();
    if ($idValidacion === false) {
        throw new RuntimeException('No existe la validación inicial.');
    }

    $pdo->beginTransaction();
    try {
        $ids = [];
        foreach (['Usuario', 'Admin'] as $nombre) {
            $ids[] = (int) $usuarios->save([
                'nombre' => $nombre,
                'mail' => 'baneo-' . bin2hex(random_bytes(8)) . '@example.com',
                'pass' => password_hash('PasswordPrueba123', PASSWORD_DEFAULT),
                'admin' => (int) ($nombre === 'Admin'),
                'id_validacion' => (int) $idValidacion,
            ]);
        }
        [$idUsuario, $idAdmin] = $ids;

        TestUtils::comprobar(
            !$repositorio->estaBaneado($idUsuario)
                && !$repositorio->estuvoBaneado($idUsuario)
                && $repositorio->findByIdUsuario($idUsuario) === [],
            'Un usuario sin historial no está ni estuvo baneado.'
        );

        $id = $repositorio->insertarBaneo(new Baneo($idUsuario, $idAdmin, 'Fraude'));
        $recuperado = $repositorio->findById($id);
        TestUtils::comprobar(
            $recuperado instanceof Baneo
                && $recuperado->getId() === (int) $id
                && $recuperado->getIdUsuario() === $idUsuario
                && $recuperado->getIdAdmin() === $idAdmin
                && $recuperado->getMotivo() === 'Fraude'
                && $recuperado->getFechaInicio() !== null
                && $recuperado->getFechaFin() === null
                && $repositorio->estaBaneado($idUsuario)
                && $repositorio->estuvoBaneado($idUsuario),
            'Guarda e hidrata un baneo permanente con inicio asignado por la base.'
        );

        $rechazado = false;
        try {
            $repositorio->insertarBaneo($recuperado);
        } catch (InvalidArgumentException $excepcion) {
            $rechazado = true;
        }
        TestUtils::comprobar($rechazado, 'Rechaza insertar un baneo que ya tiene ID.');

        $ahora = new DateTimeImmutable($pdo->query('SELECT CURRENT_TIMESTAMP')->fetchColumn());
        $ayer = $ahora->modify('-1 day')->format('Y-m-d H:i:s');
        $manana = $ahora->modify('+1 day')->format('Y-m-d H:i:s');
        $repositorio->save(['id' => $id, 'fecha_inicio' => $ayer, 'fecha_fin' => $manana]);
        TestUtils::comprobar($repositorio->estaBaneado($idUsuario), 'Un baneo temporal vigente está activo.');

        $repositorio->save(['id' => $id, 'fecha_fin' => $ayer]);
        TestUtils::comprobar(
            !$repositorio->estaBaneado($idUsuario) && $repositorio->estuvoBaneado($idUsuario),
            'Un baneo vencido cuenta como antecedente, pero no está activo.'
        );

        $idFuturo = $repositorio->insertarBaneo(new Baneo($idAdmin, $idUsuario, 'Programado', $manana));
        TestUtils::comprobar(
            !$repositorio->estaBaneado($idAdmin) && !$repositorio->estuvoBaneado($idAdmin),
            'Un baneo futuro no está activo ni cuenta aún como antecedente.'
        );

        $idSegundo = $repositorio->insertarBaneo(new Baneo($idUsuario, $idAdmin, 'Reincidencia'));
        $historial = $repositorio->findByIdUsuario($idUsuario);
        TestUtils::comprobar(
            count($historial) === 2
                && $historial[0] instanceof Baneo
                && $historial[1] instanceof Baneo
                && $historial[0]->getId() === (int) $idSegundo
                && $historial[1]->getId() === (int) $id
                && $historial[1]->getFechaFin() === $ayer,
            'El historial devuelve objetos Baneo ordenados y solo del usuario solicitado.'
        );
        TestUtils::comprobar(
            $repositorio->estaBaneado($idUsuario),
            'Un antecedente vencido no oculta otro baneo vigente.'
        );
        TestUtils::comprobar(
            !$repositorio->finalizarBaneo($idFuturo, 'Cancelación')
                && !$repositorio->finalizarBaneo((int) $id, 'Ya venció'),
            'No levanta baneos futuros ni vencidos.'
        );
        $rechazado = false;
        try {
            $repositorio->finalizarBaneo((int) $idSegundo, '   ');
        } catch (InvalidArgumentException $e) {
            $rechazado = true;
        }
        TestUtils::comprobar($rechazado && $repositorio->estaBaneado($idUsuario), 'El repositorio exige un motivo válido antes de modificar el registro.');
        TestUtils::comprobar($repositorio->finalizarBaneo((int) $idSegundo, '  Nos equivocamos de usuario  '), 'Levanta un baneo permanente.');
        $levantado = $repositorio->findById($idSegundo);
        TestUtils::comprobar(
            $levantado->getUnban() && $levantado->getFechaFin() === null
                && $levantado->getMotivoUnban() === 'Nos equivocamos de usuario'
                && !$repositorio->estaBaneado($idUsuario) && $repositorio->estuvoBaneado($idUsuario),
            'Conserva vencimiento, motivo e historial del baneo permanente levantado.'
        );
        TestUtils::comprobar(
            !$repositorio->finalizarBaneo((int) $idSegundo, 'Otra explicación')
                && $repositorio->findById($idSegundo)->getMotivoUnban() === 'Nos equivocamos de usuario',
            'Un segundo intento no sobrescribe el motivo original.'
        );
        $temporal = (int) $repositorio->insertarBaneo(new Baneo($idUsuario, $idAdmin, 'Temporal', $ayer, $manana));
        $otroVigente = (int) $repositorio->insertarBaneo(new Baneo($idUsuario, $idAdmin, 'Otro vigente'));
        $repositorio->finalizarBaneo($temporal, 'Revisión favorable');
        TestUtils::comprobar(
            $repositorio->findById($temporal)->getFechaFin() === $manana && $repositorio->estaBaneado($idUsuario),
            'Conserva el vencimiento temporal y otro baneo vigente sigue bloqueando.'
        );
        $repositorio->finalizarBaneo($otroVigente, 'Resuelto');
        TestUtils::comprobar(!$repositorio->estaBaneado($idUsuario), 'Sin otros baneos vigentes, el usuario deja de estar bloqueado.');
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}
