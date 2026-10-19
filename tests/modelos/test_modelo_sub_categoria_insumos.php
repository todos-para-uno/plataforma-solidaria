<?php
function test_modelo_categoria (){
require_once __DIR__ . '/../../app/modelos/modelo_sub_categoria_insumos.php';
echo "INICIANDO TEST: app/modelos/modelo_sub_categoria_insumos.php" . "<br>";
echo "Instanciando Sub_categoria_insumos... nombre: Lapiz 2HB, id: 32<br>";
$sub_categoria_insumos = new Sub_categoria_insumos("Lapiz 2HB", "32");
echo "Definiendo id de categoria 4.";
$sub_categoria_insumos->setId(4);
echo "Probando getters: <br>";
echo $sub_categoria_insumos->getId_categoria();

echo "tiene nombre? :" . $categoria->tieneNombre() . "<br>";
echo "nombre :" . $categoria->getNombre() . "<br>";
}
?>