<?php
function mensajesTest()
{
    require_once __DIR__ . '/../../app/modelos/Mensajes.php';

    echo "INICIANDO TEST: app/modelos/Mensajes.php" . "<br>";

    $mensaje = new Mensajes(1, 2, 'Probando 1, 2, 53!');

    echo $mensaje->getContenido();

    $mensaje->setLeido(true);
}
?>