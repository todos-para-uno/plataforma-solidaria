<?php
// Destinos de los enlaces compartidos. cabecera.php guarda este arreglo en $rutas.
// Para cambiar un destino, modificá su valor acá: las vistas que lo usan toman el cambio.
// '#' indica una funcionalidad pendiente. Al agregar su URL, implementá también
// la ruta en public_php/index.php; este arreglo por sí solo no crea la pantalla.

$rutaEntrada = ($rutaBase ?? '../../app/vistas') . '/index.php';

return [
    'inicio'          => 'index.php',
    'explorar'        => 'buscador.php',
    'como_funciona'   => '#',
    'contacto'        => 'contacto.php',
    'ingresar'        => 'ingresar.php',
    'registro'        => 'registro.php',
    'crear_campana'   => '#',
    'mis_campanas'    => '#',
    'mis_donaciones'  => '#',
    'mensajes'        => '#',
    'perfil'          => '#',
    'administracion'  => '#',
    'salir'           => '#',
    'preguntas'       => '#',
    'terminos'        => '#',
    'privacidad'      => '#',
];
