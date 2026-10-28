<?php
/** Estado persistente de intentos de acceso; usa el reloj de MySQL. */
final class EstadoAutenticacionRepository
{
    public function __construct(private PDO $pdo) {}

    public function estaBloqueado(int $idUsuario): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM estado_autenticacion WHERE id_usuario = :id AND bloqueada_hasta > CURRENT_TIMESTAMP(6)');
        $consulta->execute(['id' => $idUsuario]);
        return $consulta->fetchColumn() !== false;
    }

    /** Serializa los fallos de una cuenta mediante SELECT FOR UPDATE. */
    public function registrarFallo(int $idUsuario): bool
    {
        $externa = $this->pdo->inTransaction();
        if ($externa) {
            $this->pdo->exec('SAVEPOINT estado_autenticacion_fallo');
        } else {
            $this->pdo->beginTransaction();
        }
        try {
            $this->pdo->prepare('INSERT INTO estado_autenticacion (id_usuario) VALUES (:id) ON DUPLICATE KEY UPDATE id_usuario = id_usuario')
                ->execute(['id' => $idUsuario]);
            $stmt = $this->pdo->prepare('SELECT intentos_fallidos, ultimo_intento, bloqueada_hasta, CURRENT_TIMESTAMP(6) AS ahora FROM estado_autenticacion WHERE id_usuario = :id FOR UPDATE');
            $stmt->execute(['id' => $idUsuario]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            $ahora = new DateTimeImmutable($fila['ahora']);
            $hasta = $fila['bloqueada_hasta'] === null ? null : new DateTimeImmutable($fila['bloqueada_hasta']);
            if ($hasta !== null && $hasta > $ahora) {
                $bloqueado = true;
            } else {
                $ultimo = $fila['ultimo_intento'] === null ? null : new DateTimeImmutable($fila['ultimo_intento']);
                $reiniciar = $hasta !== null || $ultimo === null || $ahora >= $ultimo->modify('+15 minutes');
                $intentos = $reiniciar ? 1 : ((int) $fila['intentos_fallidos'] + 1);
                $bloqueado = $intentos >= 5;
                $nuevoHasta = $bloqueado ? $ahora->modify('+60 minutes')->format('Y-m-d H:i:s.u') : null;
                $update = $this->pdo->prepare('UPDATE estado_autenticacion SET intentos_fallidos = :intentos, ultimo_intento = :ahora, bloqueada_hasta = :hasta WHERE id_usuario = :id');
                $update->execute(['intentos' => $intentos, 'ahora' => $fila['ahora'], 'hasta' => $nuevoHasta, 'id' => $idUsuario]);
            }
            if ($externa) {
                $this->pdo->exec('RELEASE SAVEPOINT estado_autenticacion_fallo');
            } else {
                $this->pdo->commit();
            }
            return $bloqueado;
        } catch (Throwable $e) {
            if ($externa) {
                $this->pdo->exec('ROLLBACK TO SAVEPOINT estado_autenticacion_fallo');
            } elseif ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function limpiar(int $idUsuario): void
    {
        $this->pdo->prepare('INSERT INTO estado_autenticacion (id_usuario) VALUES (:id)
            ON DUPLICATE KEY UPDATE intentos_fallidos = 0, ultimo_intento = NULL, bloqueada_hasta = NULL')
            ->execute(['id' => $idUsuario]);
    }

    public function estado(int $idUsuario): ?array
    {
        $stmt = $this->pdo->prepare('SELECT intentos_fallidos, ultimo_intento, bloqueada_hasta FROM estado_autenticacion WHERE id_usuario = :id');
        $stmt->execute(['id' => $idUsuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
