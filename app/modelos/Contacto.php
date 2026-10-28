<?php
require_once __DIR__ . '/RegistroDb.php';

class Contacto extends RegistroDb
{
    public const ASUNTOS = [
        'consulta' => 'Consulta general',
        'campana' => 'Problema con una campaña',
        'donacion' => 'Problema con una donación',
        'cuenta' => 'Mi cuenta',
        'denuncia' => 'Denunciar una campaña',
    ];

    public function __construct(
        private string $nombre,
        private string $mail,
        private string $asunto,
        private string $contenido,
        private ?int $id_usuario = null,
        private ?string $fecha_hora = null,
        private bool $correo_enviado = false
    ) {
        $this->nombre = trim($nombre);
        $this->mail = trim($mail);
        $this->contenido = trim($contenido);

        if ($this->nombre === '' || mb_strlen($this->nombre, 'UTF-8') > 100) {
            throw new InvalidArgumentException('El nombre debe tener entre 1 y 100 caracteres.');
        }
        if (strlen($this->mail) > 150 || filter_var($this->mail, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Ingresá un correo electrónico válido de hasta 150 caracteres.');
        }
        if (!isset(self::ASUNTOS[$asunto])) {
            throw new InvalidArgumentException('Elegí uno de los asuntos disponibles.');
        }
        if ($this->contenido === '' || mb_strlen($this->contenido, 'UTF-8') > 1000) {
            throw new InvalidArgumentException('El mensaje debe tener entre 1 y 1000 caracteres.');
        }
        if ($id_usuario !== null && $id_usuario < 1) {
            throw new InvalidArgumentException('El ID del usuario debe ser positivo.');
        }
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getMail(): string
    {
        return $this->mail;
    }

    public function getAsunto(): string
    {
        return $this->asunto;
    }

    public function getContenido(): string
    {
        return $this->contenido;
    }

    public function getIdUsuario(): ?int
    {
        return $this->id_usuario;
    }

    public function getFechaHora(): ?string
    {
        return $this->fecha_hora;
    }

    public function getCorreoEnviado(): bool
    {
        return $this->correo_enviado;
    }
}
