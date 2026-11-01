<?php
require_once __DIR__ . '/CrudRepository.php';
require_once __DIR__ . '/../modelos/CampanaImagen.php';

final class CampanaImagenesRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'campana_imagenes',
            primaryKey: 'id',
            fields: ['id_camp', 'ruta', 'orden']
        );
    }

    /**
     * Guarda las rutas en el orden recibido: la primera queda como portada (orden 0).
     * No abre una transacción propia: el controlador la maneja junto con la campaña.
     *
     * @param list<string> $rutas
     * @return list<int> IDs creados
     */
    public function guardarParaCampana(int $idCamp, array $rutas): array
    {
        if (count($rutas) > CampanaImagen::MAXIMO_POR_CAMPANA) {
            throw new InvalidArgumentException('Una campaña admite hasta ' . CampanaImagen::MAXIMO_POR_CAMPANA . ' imágenes.');
        }

        $ids = [];
        foreach (array_values($rutas) as $orden => $ruta) {
            // El modelo valida la ruta y el orden antes de llegar a la base.
            $imagen = new CampanaImagen($idCamp, $ruta, $orden);
            $ids[] = (int) $this->save([
                'id_camp' => $imagen->getIdCamp(),
                'ruta' => $imagen->getRuta(),
                'orden' => $imagen->getOrden(),
            ]);
        }

        return $ids;
    }

    /** @return list<CampanaImagen> Imágenes de la campaña, empezando por la portada. */
    public function buscarPorCampana(int $idCamp): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM `campana_imagenes` WHERE `id_camp` = :idCamp ORDER BY `orden`');
            $stmt->execute(['idCamp' => $idCamp]);
        } catch (PDOException $e) {
            // Permitir leer campañas existentes mientras se importa la migración.
            if (($e->errorInfo[1] ?? null) === 1146) return [];
            throw $e;
        }

        return $this->hidratarFilas($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Portadas para una lista de tarjetas, en una sola consulta. */
    public function portadas(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) return [];
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM campana_imagenes WHERE orden = 0 AND id_camp IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
            $stmt->execute($ids);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1146) return [];
            throw $e;
        }
        $portadas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) $portadas[(int) $fila['id_camp']] = $this->hidratar($fila)->getRuta();
        return $portadas;
    }

    protected function hidratar(array $fila): CampanaImagen
    {
        $imagen = new CampanaImagen((int) $fila['id_camp'], $fila['ruta'], (int) $fila['orden']);
        $imagen->setId((int) $fila['id']);
        return $imagen;
    }
}
