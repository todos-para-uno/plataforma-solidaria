<?php
require_once __DIR__ . '/modelo_registro_db.php';
class Categoria extends Registro_db
{
    private ?string $nombre = null;

    public function __construct(string $miNombre)
    {
        $this->setNombre($miNombre);
    }
    public function setNombre(string $miNombre): void
    {
        $miNombre = trim($miNombre);
        if ($miNombre === '') {
            throw new InvalidArgumentException('El nombre no puede estar vacío.');
        }
        $this->nombre = $miNombre;
    }
    public function getNombre(): ?string
    {
        return $this->nombre;
    }
    public function tieneNombre(): bool
    {
        return $this->nombre !== null;
    }
}
?>