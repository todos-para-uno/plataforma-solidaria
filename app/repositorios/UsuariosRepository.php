<?php
require_once __DIR__ . '/CrudRepository.php';
final class UsuariosRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'usuarios',
            primaryKey: 'id',
            fields: ['nombre', 'mail', 'telefono', 'tipo_documento', 'documento', 'pass', 'verificado', 'admin', 'validacion']
        );
    }

    public function findByNombre(string $nombre): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `usuarios` WHERE `nombre` = :nombre' 
        );
        $stmt->execute(['nombre' => $nombre]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByMail(string $mail): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `usuarios` WHERE `mail` = :mail' 
        );
        $stmt->execute(['mail' => $mail]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // protected function hidratar(array $fila): Usuarios
    // {
    //     return Usuarios::desdePersistencia($fila);
    // }

}
