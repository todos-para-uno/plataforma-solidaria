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

    /**
     * Devuelve las campañas creadas por un usuario, de la más nueva a la más vieja.
     * Cada fila trae el nombre de la categoría y la suma de sus objetivos.
     */
    public function buscarPorCreador(int $idUsuario): array
    {
        // LEFT JOIN para que también aparezcan las campañas que todavía no tienen objetivos.
        // Se suman los objetivos porque una campaña de insumos puede pedir varias cosas.
        // En dinero, lo recaudado está en camp.logrado; en insumos, en objetivos.parcial.
        $stmt = $this->pdo->prepare(
            'SELECT camp.id, camp.nombre, camp.monetaria, camp.logrado, camp.estado,
                    cat_camp.nombre AS nombre_categoria,
                    COALESCE(SUM(objetivos.objetivo), 0) AS objetivo,
                    COALESCE(SUM(objetivos.parcial), 0) AS parcial
             FROM camp
             INNER JOIN cat_camp ON cat_camp.id = camp.categoria
             LEFT JOIN objetivos ON objetivos.id_camp = camp.id
             WHERE camp.owner_id = :idUsuario
             GROUP BY camp.id, camp.nombre, camp.monetaria, camp.logrado, camp.estado, cat_camp.nombre
             ORDER BY camp.id DESC'
        );
        $stmt->execute(['idUsuario' => $idUsuario]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function hidratar(array $filaCamp): Camp
    {
        return Camp::desdePersistencia($filaCamp);
    }

}
