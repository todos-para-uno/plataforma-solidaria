<?php
require_once __DIR__ . '/CrudRepository.php';
final class CategoriaInsumosRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'categoria_insumos',
            primaryKey: 'id',
            fields: ['nombre']
        );
    }

    public function findByNombre(string $nombre): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `categoria_insumos` WHERE `nombre` = :nombre' 
        );
        $stmt->execute(['nombre' => $nombre]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
