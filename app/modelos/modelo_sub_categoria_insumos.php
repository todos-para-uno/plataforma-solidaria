<?php
require_once __DIR__ . '/modelo_categoria.php';
class Sub_categoria_insumos extends Categoria
{
    private ?int $id_categoria = null;

    function __construct(string $miNombre, int $miId_categoria)
    {
        parent::__construct($miNombre);
        $this->id_categoria = $miId_categoria;
    }

    function setId_categoria(int $miId_categoria){
        $this->id_categoria = $miId_categoria;
    }
    function getId_categoria(){
        return $this->id_categoria;
    }
    function tieneId_categoria(): bool
    {
        return $this->id_categoria !== null;
    }
}
?>