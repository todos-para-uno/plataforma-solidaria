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
            fields: [
                'nombre',
                'mail',
                'telefono',
                'tipo_documento',
                'documento',
                'pass',
                'verificado',
                'admin',
                'id_validacion',
            ]
        );
    }

    /**
     * Inserta un usuario nuevo a partir de una entidad.
     *
     * @return int|string ID generado por la base de datos.
     */
    public function insertarUsuario(Usuario $usuario): int|string
    {
        if ($usuario->getId() !== null) {
            throw new InvalidArgumentException(
                'No se puede insertar un usuario que ya tiene ID.'
            );
        }

        return $this->save([
            'nombre' => $usuario->getNombre(),
            'mail' => $usuario->getEmail(),
            'telefono' => $usuario->getTelefono(),
            'tipo_documento' => $usuario->getTipoDocumento()?->value,
            'documento' => $usuario->getDocumento(),
            'pass' => $usuario->getPassword(),
            'verificado' => (int) $usuario->getVerificado(),
            'admin' => (int) $usuario->getAdmin(),
            'id_validacion' => $usuario->getIdValidacion(),
        ]);
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
