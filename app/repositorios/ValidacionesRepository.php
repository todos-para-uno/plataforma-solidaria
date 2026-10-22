<?php
require_once __DIR__ . '/CrudRepository.php';
final class ValidacionesRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'validaciones',
            primaryKey: 'id',
            fields: ['nivel', 'nombre', 'descripcion']
        );
    }

    public function findByNombre(string $nombre): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `validaciones` WHERE `nombre` = :nombre' 
        );
        $stmt->execute(['nombre' => $nombre]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByNivel(int $nivel): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `validaciones` WHERE `nivel` = :nivel' 
        );
        $stmt->execute(['nivel' => $nivel]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
