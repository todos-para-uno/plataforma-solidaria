<?php
// Único lugar donde se definen las URLs de la aplicación.
// Este archivo devuelve (return) un arreglo: cabecera.php lo guarda en $rutas.
// Las funcionalidades pendientes siguen apuntando a '#'.
// Cuando el equipo acuerde una ruta real, se cambia ACÁ y se actualiza en todas las pantallas.

$rutaEntrada = ($rutaBase ?? '../../public_php') . '/index.php';

return [
    'inicio'          => $rutaEntrada,
    'explorar'        => '#',
    'como_funciona'   => '#',
    'contacto'        => '#',
    'ingresar'        => '#',
    'registro'        => $rutaEntrada . '?ruta=registro',
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
