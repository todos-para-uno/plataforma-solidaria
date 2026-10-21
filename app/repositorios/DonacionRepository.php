<?php
require_once __DIR__ . '/CrudRepository.php';
final class DonacionRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'donacion',
            primaryKey: 'id',
            fields: ['id_usuario', 'id_camp', 'id_categoria_insumos', 
            'id_sub_categoria_insumos', 'cantidad', 'fecha_hora']
        );
    }

    public function findByIdUsuario(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `donacion` WHERE `id_usuario` = :idUsuario' 
        );
        $stmt->execute(['idUsuario' => $idUsuario]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdCamp(int $idCamp): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `donacion` WHERE `id_camp` = :idCamp' 
        );
        $stmt->execute(['idCamp' => $idCamp]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
