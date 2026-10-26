<?php
require_once __DIR__ . '/CrudRepository.php';
require_once __DIR__ . '/../modelos/Camp.php';
final class CampRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'camp',
            primaryKey: 'id',
            fields: ['owner_id', 'nombre', 'monetaria', 'categoria', 'logrado', 'descripcion', 'cbu', 'estado', 'id_ubicacion']
        );
    }

    /** @return array<int, Camp> */
    public function findByNombre(string $nombre): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `camp` WHERE `nombre` = :nombre' 
        );
        $stmt->execute(['nombre' => $nombre]);

        return $this->hidratarFilas($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<int, Camp> */
    public function findByCategoria(int $categoria): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `camp` WHERE `categoria` = :categoria' 
        );
        $stmt->execute(['categoria' => $categoria]);

        return $this->hidratarFilas($stmt->fetchAll(PDO::FETCH_ASSOC));
    }


    protected function hidratar(array $filaCamp): Camp
    {
        return Camp::desdePersistencia($filaCamp);
    }

}
