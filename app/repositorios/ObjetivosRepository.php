<?php
require_once __DIR__ . '/CrudRepository.php';
final class ObjetivosRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'objetivos',
            primaryKey: 'id',
            fields: ['id_camp', 'id_categoria_insumos', 'id_sub_categoria_insumos', 
            'objetivo']
        );
    }

    public function findByIdCamp(int $idCamp): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `objetivos` WHERE `id_camp` = :idCamp' 
        );
        $stmt->execute(['idCamp' => $idCamp]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdCatInsumos(int $idCatInsumos): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `objetivos` WHERE `id_categoria_insumos` = :idCatInsumos' 
        );
        $stmt->execute(['idCatInsumos' => $idCatInsumos]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdCampAndIdCatInsumos(int $idCamp, int $idCatInsumos): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `objetivos` WHERE `id_camp` = :idCamp AND `id_categoria_insumos` = :idCatInsumos' 
        );
        $stmt->execute([
            'idCamp' => $idCamp,
            'idCatInsumos' => $idCatInsumos
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdCampIdCatInsumosAndIdSubCatInsumos(
        int $idCamp, int $idCatInsumos, int $idSubCatInsumos): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `objetivos` 
            WHERE `id_camp` = :idCamp AND 
            `id_categoria_insumos` = :idCatInsumos AND
            `id_sub_categoria_insumos` = :idSubCatInsumos'
        );
        $stmt->execute([
            'idCamp' => $idCamp,
            'idCatInsumos' => $idCatInsumos,
            'idSubCatInsumos' => $idSubCatInsumos
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
