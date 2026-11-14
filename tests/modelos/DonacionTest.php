<?php
function donacionTest(){
require_once __DIR__ . '/../../app/modelos/Donacion.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
echo "INICIANDO TEST: app/modelos/Donacion.php" . "<br>";

$donacion = new Donacion(3, 53, 11, 2, 4, '2026-06-01 19:00:00');
TestUtils::comprobar($donacion->getIdUsuario() === 3, 'Conserva la cuenta asociada a una donación');
$sinCuenta = new Donacion(null, 53, 2.5, 2, 4);
TestUtils::comprobar($sinCuenta->getIdUsuario() === null && $sinCuenta->getCantidad() === 2.5, 'Admite una donación sin cuenta asociada');

echo "ID: " . var_export($donacion->getId(), true) . "\n";
echo "Usuario: " . $donacion->getIdUsuario() . "\n";
echo "Campaña: " . $donacion->getIdCamp() . "\n";
echo "Cantidad: " . $donacion->getCantidad() . "\n";
echo "Categoría: " . $donacion->getIdCategoriaInsumos() . "\n";
echo "Subcategoría: " . $donacion->getIdSubCategoriaInsumos() . "\n";

require_once __DIR__ . '/../../utilidades/TestUtils.php';
$invitado = new Donacion(null, 53, 11);
TestUtils::comprobar($invitado->getIdUsuario() === null && $invitado->getCantidad() === 11.0, 'Una donación admite un visitante sin cuenta.');
echo "<hr>";
}
?>
