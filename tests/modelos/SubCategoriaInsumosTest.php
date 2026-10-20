<?php
function subCategoriaInsumosTest (){
require_once __DIR__ . '/../../app/modelos/SubCategoriaInsumos.php';
echo "INICIANDO TEST: app/modelos/SubCategoriaInsumos.php" . "<br>";
echo "Instanciando Sub_categoria_insumos... nombre: Lapiz 2HB, id: 32<br>";
$subCategoriaInsumos = new SubCategoriaInsumos("Lapiz 2HB", "32");
echo "Definiendo id de categoria 4.";
$subCategoriaInsumos->setId(4);
echo "Probando getters: <br>";
echo $subCategoriaInsumos->getId_categoria();

echo "tiene nombre? :" . $categoria->tieneNombre() . "<br>";
echo "nombre :" . $categoria->getNombre() . "<br>";
echo "<hr>";
}
?>