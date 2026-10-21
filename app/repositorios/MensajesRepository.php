<?php
require_once __DIR__ . '/CrudRepository.php';
final class MensajesRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'mensajes',
            primaryKey: 'id',
            fields: ['id_emisor', 'id_receptor', 'contenido', 
            'fecha_hora', 'leido']
        );
    }

    public function findByIdEmisor(int $idEmisor): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `mensajes` WHERE `id_emisor` = :idEmisor' 
        );
        $stmt->execute(['idEmisor' => $idEmisor]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdReceptor(int $idReceptor): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `mensajes` 
            WHERE `id_receptor` = :idReceptor' 
        );
        $stmt->execute(['idReceptor' => $idReceptor]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdReceptorAndLeido(int $idReceptor): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `mensajes`
            WHERE `id_receptor` = :idReceptor
            AND `leido` = :leido'
        );

        $stmt->execute([
            'idReceptor' => $idReceptor,
            'leido' => 1,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
