<?php
require_once __DIR__ . '/../../app/modelos/Donacion.php';

//creo donación de prueba
$donacion = new Donacion(3, 53, 11, 2, 4, '2026-06-01 19:00:00');

//probando los getters y setters:

echo "ID: " . var_export($donacion->getId(), true) . "\n";
echo "Usuario: " . $donacion->getIdUsuario() . "\n";
echo "Campaña: " . $donacion->getIdCamp() . "\n";
echo "Cantidad: " . $donacion->getCantidad() . "\n";
echo "Categoría: " . $donacion->getIdCategoriaInsumos() . "\n";
echo "Subcategoría: " . $donacion->getIdSubCategoriaInsumos() . "\n";

$donacion->setCantidad(15);
$donacion->setIdCategoriaInsumos(5);
$donacion->setIdSubCategoriaInsumos(6);
?>