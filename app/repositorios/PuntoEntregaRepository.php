<?php
require_once __DIR__ . '/../modelos/Ubicaciones.php';
final class PuntoEntregaRepository
{
    public function __construct(private PDO $pdo) {}

    public function buscar(int $campana): ?array
    {
        $stmt = $this->pdo->prepare('SELECT u.* FROM camp c JOIN ubicaciones u ON u.id = c.id_ubicacion WHERE c.id = ?');
        $stmt->execute([$campana]);
        return $stmt->fetch() ?: null;
    }

    public function guardar(int $actor, int $idCampana, Ubicaciones $punto): void
    {
        $externa = $this->pdo->inTransaction();
        if ($externa) $this->pdo->exec('SAVEPOINT punto_entrega');
        else $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM camp WHERE id = ? FOR UPDATE');
            $stmt->execute([$idCampana]);
            $camp = $stmt->fetch();
            if (!$camp || (int) $camp['owner_id'] !== $actor || $camp['monetaria'] || $camp['estado'] !== 'activa') {
                throw new InvalidArgumentException('Solo el creador puede definir el punto de entrega de una campaña activa de insumos.');
            }
            $datos = [$punto->getProvincia(), $punto->getLocalidad(), $punto->getLatitud(), $punto->getLongitud(), $punto->getReferencia()];
            if ($camp['id_ubicacion']) {
                $stmt = $this->pdo->prepare('UPDATE ubicaciones SET provincia = ?, localidad = ?, latitud = ?, longitud = ?, referencia = ? WHERE id = ?');
                $stmt->execute([...$datos, $camp['id_ubicacion']]);
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO ubicaciones (provincia, localidad, latitud, longitud, referencia) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute($datos);
                $id = (int) $this->pdo->lastInsertId();
                $stmt = $this->pdo->prepare('UPDATE camp SET id_ubicacion = ? WHERE id = ?');
                $stmt->execute([$id, $idCampana]);
            }
            if ($externa) $this->pdo->exec('RELEASE SAVEPOINT punto_entrega');
            else $this->pdo->commit();
        } catch (Throwable $e) {
            if ($externa) $this->pdo->exec('ROLLBACK TO SAVEPOINT punto_entrega');
            else $this->pdo->rollBack();
            throw $e;
        }
    }
}
