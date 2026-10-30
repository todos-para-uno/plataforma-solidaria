<?php
require_once __DIR__ . '/CrudRepository.php';
require_once __DIR__ . '/../modelos/Objetivo.php';
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

    protected function hidratar(array $fila): Objetivos
    {
        return new Objetivos(
            (int) $fila['id_camp'],
            isset($fila['id_categoria_insumos']) ? (int) $fila['id_categoria_insumos'] : null,
            isset($fila['id_sub_categoria_insumos']) ? (int) $fila['id_sub_categoria_insumos'] : null,
            (float) $fila['objetivo'],
            (int) $fila['id']
        );
    }

    /** Datos de presentación de los objetivos, incluidos sus nombres de insumos. */
    public function buscarDetallePorCampana(int $idCamp): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT objetivos.*, categoria_insumos.nombre AS categoria_insumos_nombre,
                    sub_categoria_insumos.nombre AS subcategoria_nombre
             FROM objetivos
             LEFT JOIN categoria_insumos ON categoria_insumos.id = objetivos.id_categoria_insumos
             LEFT JOIN sub_categoria_insumos ON sub_categoria_insumos.id = objetivos.id_sub_categoria_insumos
             WHERE objetivos.id_camp = :id ORDER BY objetivos.id'
        );
        $stmt->execute(['id' => $idCamp]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
