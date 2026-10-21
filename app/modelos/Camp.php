<?php
require_once __DIR__ . '/RegistroDb.php';
enum EstadoCamp: string
{
    case Activa = 'activa';
    case Finalizada = 'finalizada';
    case Suspendida = 'suspendida';
}

class Camp extends RegistroDb
{
    private int $owner_id;
    private string $nombre;
    private bool $monetaria;
    private int $categoria;
    private float $logrado;
    private ?string $descripcion;
    private ?string $cbu; //string para conservar los 0 iniciales de un CBU
    private EstadoCamp $estado;
    private ?int $id_ubicacion;

    public function __construct(int $owner_id, string $nombre, bool $monetaria, int $categoria, ?string $descripcion = null, ?string $cbu = null, ?int $id_ubicacion = null)
    {   
        if ($owner_id <= 0) {
            throw new InvalidArgumentException('El ID del propietario debe ser mayor a cero.');
        }
        if ($categoria <= 0) {
            throw new InvalidArgumentException('La categoría debe ser mayor a cero.');
        }
        $this->owner_id = $owner_id;
        $this->setNombre($nombre);
        $this->monetaria = $monetaria;
        $this->categoria = $categoria;
        $this->logrado = 0;
        $this->descripcion = $descripcion;
        $this->setCbu($cbu);
        $this->estado = EstadoCamp::Activa;
        $this->id_ubicacion = $id_ubicacion;
    }

    public function getOwnerId(): int
    {
        return $this->owner_id;
    }
    public function setNombre(string $nombre): void
    {
        $nombre = trim($nombre);
        if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 150) {
            throw new InvalidArgumentException('El nombre debe tener entre 1 y 150 caracteres.');
        }
        $this->nombre = $nombre;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getMonetaria(): bool
    {
        return $this->monetaria;
    }

    public function getCategoria(): int
    {
        return $this->categoria;
    }

    public function getLogrado(): float
    {
        return $this->logrado;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setCbu(?string $cbu): void
    {
        if ($cbu === null) {
            if ($this->monetaria) {
                throw new InvalidArgumentException(
                    'Las campañas monetarias deben tener un CBU.'
                );
            }
            $this->cbu = null;
            return;
        }
        $cbu = trim($cbu);
        if (strlen($cbu) !== 22 || !ctype_digit($cbu)) {
            throw new InvalidArgumentException('El CBU debe tener 22 números.');
        }
        $this->cbu = $cbu;
    }

    public function getCbu(): ?string
    {
        return $this->cbu;
    }

    public function getEstado(): EstadoCamp
    {
        return $this->estado;
    }

    public function getIdUbicacion(): ?int
    {
        return $this->id_ubicacion;
    }

}