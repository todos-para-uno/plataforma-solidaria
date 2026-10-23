<?php
require_once __DIR__ . '/RegistroDb.php';

class Validaciones extends RegistroDb
{
    private int $nivel;
    private string $nombre;
    private ?string $descripcion;

    public function __construct(int $nivel, string $nombre, ?string $descripcion = null)
    {
        if ($nivel < 1) {
            throw new InvalidArgumentException('La validación debe ser mayor a 0');
        }

        $nombre = trim($nombre);
        if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 30) {
            throw new InvalidArgumentException('El nombre debe tener entre 1 y 30 caracteres.');
        }

        if ($descripcion !== null && strlen($descripcion) > 255) {
            throw new InvalidArgumentException('La descripción no puede tener más de 255 caracteres.');
        }

        $this->nivel = $nivel;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
    }

    /**
     * Reconstruye una validación a partir de una fila de la base de datos.
     *
     * @param array<string, mixed> $filaValidacion
     */
    public static function desdePersistencia(array $filaValidacion): self
    {
        $validacion = new self(
            (int) $filaValidacion['nivel'],
            $filaValidacion['nombre'],
            $filaValidacion['descripcion'] ?? null
        );

        $validacion->setId((int) $filaValidacion['id']);

        return $validacion;
    }


    public function getNivel(): int
    {
        return $this->nivel;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }
}
