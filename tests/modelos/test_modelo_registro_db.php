<?php
function test_modelo_registro_db (){
require_once __DIR__ . '/../../app/modelos/modelo_registro_db.php';
echo "INICIANDO TEST: app/modelos/modelo_registro_db.php" . "<br>";
echo "Instanciando Registro_db..." . "<br>";
$registro_db = new Registro_db();


echo "ID: " . $registro_db->getId() . "<br>";
echo "Tiene ID?: " . $registro_db->tieneId() . "<br>";
$registro_db->setId(23);
echo "asignado 23." . "<br>";
echo "Tiene ID?: " . $registro_db->tieneId() . "<br>";
echo "ID: " . $registro_db->getId() . "<br>";
}
?>
