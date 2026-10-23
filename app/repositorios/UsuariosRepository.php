<?php
require_once __DIR__ . '/CrudRepository.php';
require_once __DIR__ . '/../modelos/Usuarios.php';

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

    /**
     * @param array<string, mixed> $fila
     */
    protected function hidratar(array $fila): Usuario
    {
        return Usuario::desdePersistencia($fila);
    }

    /**
     * Busca usuarios por coincidencia exacta de nombre.
     *
     * @return array<int, Usuario>
     */
    public function findByNombre(string $nombre): array
    {
        return $this->hidratarFilas(
            $this->findRowsByNombre($nombre)
        );
    }

    /**
     * Recupera las filas correspondientes a un nombre.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findRowsByNombre(string $nombre): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `usuarios` WHERE `nombre` = :nombre'
        );

        $stmt->execute(['nombre' => $nombre]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

     /**
     * Busca un usuario por su correo electrónico.
     *
     * @return Usuario|null
     */
    public function findByMail(string $mail): ?Usuario
    {
        $fila = $this->findRowByMail($mail);
        return $fila !== null? $this->hidratar($fila): null;
    }

    /**
     * Recupera una fila por correo electrónico.
     *
     * @return array<string, mixed>|null
     */
    public function findRowByMail(string $mail): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `usuarios` WHERE `mail` = :mail'
        );

        $stmt->execute(['mail' => $mail]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila === false ? null : $fila;
    }


}
