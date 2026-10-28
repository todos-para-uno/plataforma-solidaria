<?php
require_once __DIR__ . '/RegistroDb.php';

class Baneo extends RegistroDb
{
    private int $id_usuario;
    private int $id_admin;
    private string $motivo;
    private ?string $fecha_inicio;
    private ?string $fecha_fin;
    private ?string $fecha_unban;
    private ?string $motivo_unban;

    public function __construct(
        int $id_usuario,
        int $id_admin,
        string $motivo,
        ?string $fecha_inicio = null,
        ?string $fecha_fin = null,
        ?string $fecha_unban = null,
        ?string $motivo_unban = null
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
        if ($fecha_unban === null && $motivo_unban !== null) {
            throw new InvalidArgumentException('Un baneo sin levantar no puede tener motivo de desbaneo.');
        }
        if ($fecha_unban !== null) {
            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $fecha_unban);
            if ($fecha === false || $fecha->format('Y-m-d H:i:s') !== $fecha_unban) {
                throw new InvalidArgumentException('La fecha de desbaneo debe usar AAAA-MM-DD HH:MM:SS y ser válida.');
            }
        }
        $this->fecha_unban = $fecha_unban;
        $this->motivo_unban = $fecha_unban !== null ? self::validarMotivoUnban($motivo_unban ?? '') : null;
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

    public function getFechaUnban(): ?string
    {
        return $this->fecha_unban;
    }

    public function getMotivoUnban(): ?string
    {
        return $this->motivo_unban;
    }

    public static function validarMotivoUnban(string $motivo): string
    {
        $motivo = trim($motivo);
        if (!mb_check_encoding($motivo, 'UTF-8') || $motivo === '' || mb_strlen($motivo, 'UTF-8') > 500) {
            throw new InvalidArgumentException('El motivo de desbaneo debe tener entre 1 y 500 caracteres.');
        }
        return $motivo;
    }
}
