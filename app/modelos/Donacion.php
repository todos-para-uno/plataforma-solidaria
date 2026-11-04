<?php
require_once __DIR__ . "/RegistroDb.php";
class Donacion extends RegistroDb
{
    private ?int $id_usuario;
    private int $id_camp;
    private ?int $id_categoria_insumos;
    private ?int $id_sub_categoria_insumos;
    private float $cantidad;
    private ?string $fecha_hora;

    public function __construct(
        ?int $id_usuario,
        int $id_camp,
        float $cantidad,
        ?int $id_categoria_insumos = null,
        ?int $id_sub_categoria_insumos = null,
        ?string $fecha_hora = null
    ) {
        $this->id_usuario = $id_usuario;
        $this->id_camp = $id_camp;
        $this->id_categoria_insumos = $id_categoria_insumos;
        $this->id_sub_categoria_insumos = $id_sub_categoria_insumos;
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }
        $this->cantidad = $cantidad;
        $this->fecha_hora = $fecha_hora;
    }

    public function getIdUsuario(): ?int {
        return $this->id_usuario;
    }
    public function getIdCamp(): int {
        return $this->id_camp;
    }
    public function getIdCategoriaInsumos(): ?int {
        return $this->id_categoria_insumos;
    }
    public function getIdSubCategoriaInsumos(): ?int {
        return $this->id_sub_categoria_insumos;
    }
    public function getCantidad(): float {
        return $this->cantidad;
    }
    public function getFechaHora(): ?string {
        return $this->fecha_hora;
    }
}
?>
