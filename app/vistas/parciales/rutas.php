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
    'como_funciona'   => $rutaEntrada . '?ruta=preguntas_frecuentes',
    'contacto'        => $rutaEntrada . '?ruta=contacto',
    'ingresar'        => $rutaEntrada . '?ruta=ingresar',
    'registro'        => $rutaEntrada . '?ruta=registro',
    'campana'         => $rutaEntrada . '?ruta=campana',
    'crear_campana'   => $rutaEntrada . '?ruta=crear_campana',
    'mis_campanas'    => $rutaEntrada . '?ruta=mis_campanas',
    'mis_donaciones'  => $rutaEntrada . '?ruta=mis_donaciones',
    'mensajes'        => '#',
    'administracion'  => $rutaEntrada . '?ruta=administracion',
    'perfil'          => $rutaEntrada . '?ruta=perfil',
    'salir'           => $rutaEntrada . '?ruta=salir',
    'preguntas'       => $rutaEntrada . '?ruta=preguntas_frecuentes',
    'terminos'        => '#',
    'privacidad'      => '#',
];
