<?php
require_once __DIR__ . '/../modelos/RegistroDb.php';

abstract class CrudRepository
{
    public function __construct(
        protected PDO $pdo,
        private string $table,
        private string $primaryKey,
        private array $fields
    ) {
        // Los nombres de tabla y columnas no se pueden parametrizar con PDO.
        // Esta validación evita aceptar identificadores arbitrarios.
        foreach ([$table, $primaryKey, ...$fields] as $identifier) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
                throw new InvalidArgumentException("Identificador SQL no válido");
            }
        }
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `{$this->table}`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findRowById(int|string $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $fila === false ? null : $fila;
    }

    public function findById(int|string $id): ?RegistroDb
{
    $fila = $this->findRowById($id);

    return $fila === null
        ? null
        : $this->hidratar($fila);
}

    /**
     * Inserta si no hay clave primaria; actualiza si se proporciona.
     * Devuelve el ID del registro.
     */
    public function save(array $data): int|string
    {
        $id = $data[$this->primaryKey] ?? null;
        unset($data[$this->primaryKey]);

        // Solo aceptar campos definidos por el repositorio concreto.
        $data = array_intersect_key($data, array_flip($this->fields));

        if ($data === []) {
            throw new InvalidArgumentException("No hay campos válidos para guardar");
        }

        if ($id === null) {
            $columns = array_keys($data);
            $columnSql = implode(', ', array_map(
                fn ($column) => "`$column`",
                $columns
            ));
            $placeholderSql = implode(', ', array_map(
                fn ($column) => ":$column",
                $columns
            ));

            $sql = "INSERT INTO `{$this->table}` ($columnSql)
                    VALUES ($placeholderSql)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);

            return $this->pdo->lastInsertId();
        }

        $setSql = implode(', ', array_map(
            fn ($column) => "`$column` = :$column",
            array_keys($data)
        ));

        $sql = "UPDATE `{$this->table}`
                SET $setSql
                WHERE `{$this->primaryKey}` = :__id";

        $data['__id'] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return $id;
    }

    public function delete(int|string $id): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Convierte una fila asociativa en una entidad del modelo.
     *
     * @param array<string, mixed> $fila
     */
    abstract protected function hidratar(array $fila): RegistroDb;
}
