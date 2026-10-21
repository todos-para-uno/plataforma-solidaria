<?php
require_once __DIR__ . '/CrudRepository.php';
final class SubCategoriaInsumosRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'sub_categoria_insumos',
            primaryKey: 'id',
            fields: ['id_categoria','nombre']
        );
    }

    public function findByIdCategoria(int $idCategoria): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `sub_categoria_insumos` WHERE `id_categoria` = :idCategoria' 
        );
        $stmt->execute(['idCategoria' => $idCategoria]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByNombre(string $nombre): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `sub_categoria_insumos` WHERE `nombre` = :nombre' 
        );
        $stmt->execute(['nombre' => $nombre]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
