<?php

function categoriaTest (){
require_once __DIR__ . '/../../app/modelos/Categoria.php';
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';

echo "INICIANDO TEST: app/modelos/Categoria.php" . eol();
echo "Instanciando Categoria..." . eol();
$categoria = new Categoria("Libreria");

echo "test rapido herencia con Registro_db..." . eol();
echo "tiene id:" . $categoria->tieneId() . eol();
echo "asignando id 32... " . eol();
$categoria->setId(32);
echo "mostrar id:" . $categoria->getId() . eol();

echo "test de Categoria:" . eol();
echo "tiene nombre? :" . $categoria->tieneNombre() . eol();
echo "nombre :" . $categoria->getNombre() . eol();
echo "<hr>";
}
?>