<?php
require_once __DIR__ . '/../../app/modelos/Mensajes.php';
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';

function mensajesTest()
{
    

    echo "INICIANDO TEST: app/modelos/Mensajes.php" . eol();

    $mensaje = new Mensajes(1, 2, 'Probando 1, 2, 53!');

    echo $mensaje->getContenido() . eol();

    $mensaje->setLeido(true);

    echo '<hr>';
}
?>