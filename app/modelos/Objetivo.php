<?php
require_once __DIR__ . '/RegistroDb.php';
class Objetivos extends RegistroDb
{
    private int $id_camp;
    private ?int $id_categoria_insumos;
    private ?int $id_sub_categoria_insumos;
    private float $objetivo;

    public function __construct(
        int $miId_camp,
        ?int $miId_categoria_insumos,
        ?int $miId_sub_categoria_insumos,
        float $miObjetivo,
        ?int $miId = null
    ){
        $this->id_camp = $miId_camp;
        $this->id_categoria_insumos = $miId_categoria_insumos;
        $this->id_sub_categoria_insumos = $miId_sub_categoria_insumos;
        $this->objetivo = $miObjetivo;
        if ($miId !== null) {
            $this->setId($miId);
        }
    }

    public function setId_camp(int $miId_camp): void
    {
        $this->id_camp = $miId_camp;
    }
    public function getId_camp(): int
    {
        return $this->id_camp;
    }
    public function setId_categoria_insumos(?int $miId_categoria_insumos): void
    {
        $this->id_categoria_insumos = $miId_categoria_insumos;
    }
    public function getId_categoria_insumos(): ?int
    {
        return $this->id_categoria_insumos;
    }
    public function setId_sub_categoria_insumos(?int $miId_sub_categoria_insumos): void
    {
        $this->id_sub_categoria_insumos = $miId_sub_categoria_insumos;
    }
    public function getId_sub_categoria_insumos(): ?int
    {
        return $this->id_sub_categoria_insumos;
    }
    public function setObjetivo(float $miObjetivo): void
    {
        $this->objetivo = $miObjetivo;
    }
    public function getObjetivo(): float
    {
        return $this->objetivo;
    }
}
?>
