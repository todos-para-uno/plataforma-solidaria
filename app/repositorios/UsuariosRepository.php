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


    /** Búsqueda administrativa paginada; no expone hashes. */
    public function buscarAdministracion(string $termino, int $limite = 15, int $offset = 0): array
    {
        if (!in_array($limite, [10, 15, 25, 50], true) || $offset < 0) {
            throw new InvalidArgumentException('Paginación inválida.');
        }
        $sql = "SELECT u.id, u.nombre, u.mail, u.telefono, u.admin, u.verificado,
                       u.id_validacion, v.nombre AS validacion_nombre,
                       (SELECT b.id FROM baneados b WHERE b.id_usuario = u.id
                           AND b.fecha_unban IS NULL AND b.fecha_inicio <= CURRENT_TIMESTAMP
                           AND (b.fecha_fin IS NULL OR b.fecha_fin > CURRENT_TIMESTAMP)
                           ORDER BY b.fecha_inicio DESC, b.id DESC LIMIT 1) AS id_baneo_vigente,
                       EXISTS(SELECT 1 FROM baneados b WHERE b.id_usuario = u.id
                           AND b.fecha_unban IS NULL AND b.fecha_inicio <= CURRENT_TIMESTAMP
                           AND (b.fecha_fin IS NULL OR b.fecha_fin > CURRENT_TIMESTAMP)) AS baneado,
                       EXISTS(SELECT 1 FROM estado_autenticacion ea WHERE ea.id_usuario = u.id
                           AND ea.bloqueada_hasta > CURRENT_TIMESTAMP(6)) AS bloqueado
                FROM usuarios u LEFT JOIN validaciones v ON v.id = u.id_validacion
                WHERE LOWER(u.nombre) LIKE :nombre ESCAPE '!' OR LOWER(u.mail) LIKE :mail ESCAPE '!'
                ORDER BY u.nombre, u.id LIMIT :limite OFFSET :offset";
        // El carácter ! es el escape explícito para LIKE.
        $patron = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower(trim($termino), 'UTF-8')) . '%';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':nombre', $patron);
        $stmt->bindValue(':mail', $patron);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarHashPassword(int $idUsuario, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET pass = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $idUsuario]);
    }

}
