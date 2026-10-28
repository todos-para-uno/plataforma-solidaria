<?php
require_once __DIR__ . '/CrudRepository.php';
require_once __DIR__ . '/../modelos/Baneo.php';

final class BaneoRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'baneados',
            primaryKey: 'id',
            fields: ['id_usuario', 'id_admin', 'motivo', 'fecha_inicio', 'fecha_fin', 'fecha_unban', 'motivo_unban']
        );
    }

    protected function hidratar(array $fila): Baneo
    {
        $baneo = new Baneo(
            (int) $fila['id_usuario'],
            (int) $fila['id_admin'],
            $fila['motivo'],
            $fila['fecha_inicio'],
            $fila['fecha_fin'],
            $fila['fecha_unban'],
            $fila['motivo_unban']
        );
        $baneo->setId((int) $fila['id']);

        return $baneo;
    }

    // Devuelve el ID generado por la base de datos.
    public function insertarBaneo(Baneo $baneo): int|string
    {
        if ($baneo->tieneId()) {
            throw new InvalidArgumentException('No se puede insertar un baneo que ya tiene ID.');
        }

        $datos = [
            'id_usuario' => $baneo->getIdUsuario(),
            'id_admin' => $baneo->getIdAdmin(),
            'motivo' => $baneo->getMotivo(),
            'fecha_fin' => $baneo->getFechaFin(),
            'fecha_unban' => $baneo->getFechaUnban(),
            'motivo_unban' => $baneo->getMotivoUnban(),
        ];

        // Si no se indicó el inicio, la base asigna CURRENT_TIMESTAMP.
        if ($baneo->getFechaInicio() !== null) {
            $datos['fecha_inicio'] = $baneo->getFechaInicio();
        }

        return $this->save($datos);
    }

    public function estaBaneado(int $idUsuario): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM `baneados`
             WHERE `id_usuario` = :idUsuario
               AND `fecha_unban` IS NULL
               AND `fecha_inicio` <= CURRENT_TIMESTAMP
               AND (`fecha_fin` IS NULL OR `fecha_fin` > CURRENT_TIMESTAMP)
             LIMIT 1'
        );
        $stmt->execute(['idUsuario' => $idUsuario]);

        return $stmt->fetchColumn() !== false;
    }

    public function fechaActual(): string
    {
        return $this->pdo->query('SELECT CURRENT_TIMESTAMP')->fetchColumn();
    }

    // Conservar el registro como antecedente y finalizarlo solo si sigue vigente.
    public function finalizarBaneo(int $idBaneo, string $motivoUnban): bool
    {
        $motivoUnban = Baneo::validarMotivoUnban($motivoUnban);
        $stmt = $this->pdo->prepare(
            'UPDATE `baneados` SET `fecha_unban` = CURRENT_TIMESTAMP, `motivo_unban` = :motivo
             WHERE `id` = :id
               AND `fecha_unban` IS NULL
               AND `fecha_inicio` <= CURRENT_TIMESTAMP
               AND (`fecha_fin` IS NULL OR `fecha_fin` > CURRENT_TIMESTAMP)'
        );
        $stmt->execute(['id' => $idBaneo, 'motivo' => $motivoUnban]);

        return $stmt->rowCount() > 0;
    }

    // Incluye baneos vencidos y vigentes, es decir, si está baneado todavía también arroja true.
    public function estuvoBaneado(int $idUsuario): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM `baneados`
             WHERE `id_usuario` = :idUsuario
               AND `fecha_inicio` <= CURRENT_TIMESTAMP
             LIMIT 1'
        );
        $stmt->execute(['idUsuario' => $idUsuario]);

        return $stmt->fetchColumn() !== false;
    }

    // Devuelve objetos Baneo del usuario, del más reciente al más antiguo, ya que un usuario puede tener varios baneos en su historial.
    public function findByIdUsuario(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `baneados` WHERE `id_usuario` = :idUsuario
             ORDER BY `fecha_inicio` DESC, `id` DESC'
        );
        $stmt->execute(['idUsuario' => $idUsuario]);

        return $this->hidratarFilas($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
