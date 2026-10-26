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

    public function __construct(
        int $owner_id,
        string $nombre,
        bool $monetaria,
        int $categoria,
        ?string $descripcion = null,
        ?string $cbu = null,
        ?int $id_ubicacion = null
    ) {
        $this->setOwnerId($owner_id);
        $this->setNombre($nombre);
        $this->setMonetaria($monetaria);
        $this->setCategoria($categoria);
        $this->logrado = 0;
        $this->descripcion = $descripcion;
        $this->setCbu($cbu);
        $this->estado = EstadoCamp::Activa;
        $this->setIdUbicacion($id_ubicacion);
    }

    /**
     * Reconstruye una campaña conservando los valores guardados.
     *
     * @param array<string, mixed> $filaCamp
     */
    public static function desdePersistencia(array $filaCamp): self
    {
        $campana = new self(
            owner_id: (int) $filaCamp['owner_id'],
            nombre: $filaCamp['nombre'],
            monetaria: (bool) $filaCamp['monetaria'],
            categoria: (int) $filaCamp['categoria'],
            descripcion: $filaCamp['descripcion'] ?? null,
            cbu: $filaCamp['cbu'] ?? null,
            id_ubicacion: isset($filaCamp['id_ubicacion'])
                ? (int) $filaCamp['id_ubicacion']
                : null
        );

        $campana->setId((int) $filaCamp['id']);
        $campana->logrado = (float) $filaCamp['logrado'];
        $campana->estado = EstadoCamp::from($filaCamp['estado']);

        return $campana;
    }

    public function setOwnerId(int $owner_id): void
    {
        if ($owner_id <= 0) {
            throw new InvalidArgumentException('El ID del propietario debe ser mayor a cero.');
        }
        $this->owner_id = $owner_id;
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

    public function setMonetaria(bool $monetaria): void
    {
        // En el constructor, el CBU y la ubicación se validan después con sus setters.
        if (isset($this->monetaria)) {
            if ($monetaria && $this->cbu === null) {
                throw new InvalidArgumentException('Las campañas monetarias deben tener un CBU.');
            }
            if (!$monetaria && $this->id_ubicacion === null) {
                throw new InvalidArgumentException('Las campañas de insumos deben tener una ubicación.');
            }
        }
        $this->monetaria = $monetaria;
    }

    public function getMonetaria(): bool
    {
        return $this->monetaria;
    }

    public function setCategoria(int $categoria): void
    {
        if ($categoria <= 0) {
            throw new InvalidArgumentException('La categoría debe ser mayor a cero.');
        }
        $this->categoria = $categoria;
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

    public function setIdUbicacion(?int $id_ubicacion): void
    {
        if (!$this->monetaria && $id_ubicacion === null) {
            throw new InvalidArgumentException('Las campañas de insumos deben tener una ubicación.');
        }
        if ($id_ubicacion !== null && $id_ubicacion <= 0) {
            throw new InvalidArgumentException('La ubicación debe ser un ID positivo.');
        }
        $this->id_ubicacion = $id_ubicacion;
    }

    public function getIdUbicacion(): ?int
    {
        return $this->id_ubicacion;
    }

}
