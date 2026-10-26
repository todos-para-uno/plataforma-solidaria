<?php
// Destinos de los enlaces compartidos. cabecera.php guarda este arreglo en $rutas.
// Para cambiar un destino, modificá su valor acá: las vistas que lo usan toman el cambio.
// '#' indica una funcionalidad pendiente. Al agregar su URL, implementá también
// la ruta en public_php/index.php; este arreglo por sí solo no crea la pantalla.

$rutaEntrada = ($rutaBase ?? '../../public_php') . '/index.php';

return [
    'inicio'          => $rutaEntrada,
    'explorar'        => $rutaEntrada . '?ruta=buscador',
    'resultados_buscar' => $rutaEntrada . '?ruta=resultados_buscar',
    'como_funciona'   => '#',
    'contacto'        => $rutaEntrada . '?ruta=contacto',
    'ingresar'        => $rutaEntrada . '?ruta=ingresar',
    'registro'        => $rutaEntrada . '?ruta=registro',
    'crear_campana'   => $rutaEntrada . '?ruta=crear_campana',
    'mis_campanas'    => '#',
    'mis_donaciones'  => $rutaEntrada . '?ruta=mis_donaciones',
    'mensajes'        => '#',
    'perfil'          => '#',
    'administracion'  => '#',
    'salir'           => $rutaEntrada . '?ruta=salir',
    'preguntas'       => '#',
    'terminos'        => '#',
    'privacidad'      => '#',
];
