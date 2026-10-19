<?php
function registroDbTest (){
require_once __DIR__ . '/../../app/modelos/RegistroDb.php';
echo "INICIANDO TEST: app/modelos/RegistroDb.php" . "<br>";
echo "Instanciando RegistroDb..." . "<br>";
$registroDb = new RegistroDb();


echo "ID: " . $registroDb->getId() . "<br>";
echo "Tiene ID?: " . $registroDb->tieneId() . "<br>";
$registroDb->setId(23);
echo "asignado 23." . "<br>";
echo "Tiene ID?: " . $registroDb->tieneId() . "<br>";
echo "ID: " . $registroDb->getId() . "<br>";
echo "<hr>";
}
?>
