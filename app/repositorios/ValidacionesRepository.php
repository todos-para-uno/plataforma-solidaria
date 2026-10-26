<?php
require_once __DIR__ . '/CrudRepository.php';
require_once __DIR__ . '/../modelos/Validaciones.php';

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

    /**
     * @param array<string, mixed> $fila
     */
    protected function hidratar(array $fila): Validaciones
    {
        return Validaciones::desdePersistencia($fila);
    }

    /**
     * Busca validaciones por nombre.
     *
     * @return array<int, Validaciones>
     */
    public function findByNombre(string $nombre): array
    {
        return $this->hidratarFilas(
            $this->findRowsByNombre($nombre)
        );
    }

    /**
     * Busca filas de validaciones por coincidencia exacta de nombre.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findRowsByNombre(string $nombre): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `validaciones` WHERE `nombre` = :nombre' 
        );
        $stmt->execute(['nombre' => $nombre]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca una validación por su nivel.
     *
     * @return Validaciones|null
     */
    public function findByNivel(int $nivel): ?Validaciones
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `validaciones` WHERE `nivel` = :nivel'
        );

        $stmt->execute(['nivel' => $nivel]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila === false
            ? null
            : $this->hidratar($fila);
    }

    /**
     * Busca las filas correspondientes a un nivel.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findRowsByNivel(int $nivel): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `validaciones` WHERE `nivel` = :nivel'
        );

        $stmt->execute(['nivel' => $nivel]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }    
}
