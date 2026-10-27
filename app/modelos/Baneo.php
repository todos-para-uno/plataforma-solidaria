<?php
require_once __DIR__ . '/RegistroDb.php';

class Baneo extends RegistroDb
{
    private int $id_usuario;
    private int $id_admin;
    private string $motivo;
    private ?string $fecha_inicio;
    private ?string $fecha_fin;

    public function __construct(
        int $id_usuario,
        int $id_admin,
        string $motivo,
        ?string $fecha_inicio = null,
        ?string $fecha_fin = null
    ) {
        if ($id_usuario < 1 || $id_admin < 1) {
            throw new InvalidArgumentException('Los IDs de usuario y administrador deben ser mayores a cero.');
        }

        $motivo = trim($motivo);
        if ($motivo === '' || mb_strlen($motivo, 'UTF-8') > 500) {
            throw new InvalidArgumentException('El motivo debe tener entre 1 y 500 caracteres.');
        }

        $this->id_usuario = $id_usuario;
        $this->id_admin = $id_admin;
        $this->motivo = $motivo;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_fin = $fecha_fin;
    }

    public function getIdUsuario(): int
    {
        return $this->id_usuario;
    }

    public function getIdAdmin(): int
    {
        return $this->id_admin;
    }

    public function getMotivo(): string
    {
        return $this->motivo;
    }

    public function getFechaInicio(): ?string
    {
        return $this->fecha_inicio;
    }

    public function getFechaFin(): ?string
    {
        return $this->fecha_fin;
    }
}
