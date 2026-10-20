<?php
class Mensajes {
    private ?int $id = null;
    private int $id_emisor;
    private int $id_receptor;
    private string $contenido;
    private ?string $fecha_hora;
    private bool $leido;
    public function __construct(int $id_emisor, int $id_receptor, string $contenido, ?string $fecha_hora = null) {
        $this->id_emisor = $id_emisor;
        $this->id_receptor = $id_receptor;
        $this->setContenido($contenido);
        $this->fecha_hora = $fecha_hora;
        $this->leido = false;
    }
    function setId($id): void {
        $this->id = $id;
    }
    function getId() {
        return $this->id;
    }
    function setIdEmisor($id_emisor): void {
        $this->id_emisor = $id_emisor;
    }
    function getIdEmisor() {
        return $this->id_emisor;
    }
    function setIdReceptor($id_receptor): void {
        $this->id_receptor = $id_receptor;
    }
    function getIdReceptor() {
        return $this->id_receptor;
    }
    public function setContenido(string $contenido): void {
        if ($contenido === '') {
            throw new InvalidArgumentException(
                "El mensaje no puede estar vacío."
            );
        }
        if (mb_strlen($contenido, 'UTF-8') > 255) {
            throw new InvalidArgumentException(
                "El mensaje no puede superar los 255 caracteres."
            );
        }
        $this->contenido = $contenido;
    }

    function getContenido() {
        return $this->contenido;
    }
    function setFechaHora($fecha_hora): void {
        $this->fecha_hora = $fecha_hora;
    }
    function getFechaHora() {
        return $this->fecha_hora;
    }
    function setLeido($leido): void {
        $this->leido = $leido;
    }
    function getLeido() {
        return $this->leido;
    }
}
?>