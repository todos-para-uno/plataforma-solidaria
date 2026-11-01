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
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `campana_imagenes` WHERE `id_camp` = :idCamp ORDER BY `orden`'
        );
        $stmt->execute(['idCamp' => $idCamp]);

        return $this->hidratarFilas($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    protected function hidratar(array $fila): CampanaImagen
    {
        $imagen = new CampanaImagen((int) $fila['id_camp'], $fila['ruta'], (int) $fila['orden']);
        $imagen->setId((int) $fila['id']);
        return $imagen;
    }
}
