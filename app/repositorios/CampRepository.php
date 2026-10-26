<?php
require_once __DIR__ . '/CrudRepository.php';
final class CampRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'camp',
            primaryKey: 'id',
            fields: ['owner_id', 'nombre', 'monetaria', 'categoria', 'logrado', 'descripcion', 'cbu', 'estado']
        );
    }

    public function findByNombre(string $nombre): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `camp` WHERE `nombre` = :nombre' 
        );
        $stmt->execute(['nombre' => $nombre]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByCategoria(int $categoria): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `camp` WHERE `categoria` = :categoria' 
        );
        $stmt->execute(['categoria' => $categoria]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // protected function hidratar(array $fila): Camp
    // {
    //     return Camp::desdePersistencia($fila);
    // }

}
