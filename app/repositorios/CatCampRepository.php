<?php
require_once __DIR__ . '/CrudRepository.php';
require_once __DIR__ . '/../modelos/CatCamp.php';
final class CatCampRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'cat_camp',
            primaryKey: 'id',
            fields: ['nombre']
        );
    }

    public function findByNombre(string $nombre): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `cat_camp` WHERE `nombre` = :nombre' 
        );
        $stmt->execute(['nombre' => $nombre]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Categorías disponibles para el selector público, ordenadas por nombre. */
    public function listarParaBuscador(): array
    {
        return $this->pdo->query('SELECT id, nombre FROM cat_camp ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function hidratar(array $fila): CatCamp
    {
        $categoria = new CatCamp($fila['nombre']);
        $categoria->setId((int) $fila['id']);
        return $categoria;
    }

}
