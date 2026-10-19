<?php
function test_modelo_categoria (){
require_once __DIR__ . '/../../app/modelos/modelo_categoria.php';
echo "INICIANDO TEST: app/modelos/modelo_categoria.php" . "<br>";
echo "Instanciando Categoria..." . "<br>";
$categoria = new Categoria("Libreria");

echo "test rapido herencia con Registro_db...<br>";
echo "tiene id:" . $categoria->tieneId() . "<br>";
echo "asignando id 32... <br>";
$categoria->setId(32);
echo "mostrar id:" . $categoria->getId() . "<br>";

echo "test de Categoria:<br>";
echo "tiene nombre? :" . $categoria->tieneNombre() . "<br>";
echo "nombre :" . $categoria->getNombre() . "<br>";
}
?>