<?php
require_once __DIR__ . '/RegistroDb.php';

/** Una foto subida por el creador de una campaña (tabla campana_imagenes). */
class CampanaImagen extends RegistroDb
{
    /** Coincide con la restricción CHECK de la tabla: orden 0 a 3. */
    public const MAXIMO_POR_CAMPANA = 4;

    private int $id_camp;
    private string $ruta;
    private int $orden;

    public function __construct(int $id_camp, string $ruta, int $orden)
    {
        $this->setIdCamp($id_camp);
        $this->setRuta($ruta);
        $this->setOrden($orden);
    }

    public function setIdCamp(int $id_camp): void
    {
        if ($id_camp <= 0) {
            throw new InvalidArgumentException('El ID de la campaña debe ser mayor a cero.');
        }
        $this->id_camp = $id_camp;
    }

    public function getIdCamp(): int
    {
        return $this->id_camp;
    }

    public function setRuta(string $ruta): void
    {
        // Solo rutas relativas simples: sin "..", sin barra inicial y con extensión de imagen.
        if (!preg_match('#^[a-z0-9_-]+(/[a-z0-9_-]+)*\.(png|jpg|webp)$#', $ruta) || strlen($ruta) > 255) {
            throw new InvalidArgumentException('La ruta de la imagen no es válida.');
        }
        $this->ruta = $ruta;
    }

    public function getRuta(): string
    {
        return $this->ruta;
    }

    public function setOrden(int $orden): void
    {
        if ($orden < 0 || $orden >= self::MAXIMO_POR_CAMPANA) {
            throw new InvalidArgumentException('Una campaña admite hasta ' . self::MAXIMO_POR_CAMPANA . ' imágenes.');
        }
        $this->orden = $orden;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function esPortada(): bool
    {
        return $this->orden === 0;
    }
}
