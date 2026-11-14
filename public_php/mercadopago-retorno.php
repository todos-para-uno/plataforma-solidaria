<?php
// Diagnóstico temporal, activado mediante un archivo privado fuera de la raíz web.
// Registrar antes de cualquier validación permite detectar incluso retornos sin sesión.
$directorioDiagnostico = dirname(__DIR__) . '/.respaldos-locales';
if (is_file($directorioDiagnostico . '/mp-oauth-diagnostico.activo')) {
    // No leer ni registrar URL, querystring, IP, cabeceras o datos de la sesión.
    @file_put_contents(
        $directorioDiagnostico . '/mp-oauth-llegadas.log',
        gmdate('Y-m-d\TH:i:s\Z') . " callback_recibido\n",
        FILE_APPEND | LOCK_EX
    );
}

// Retorno OAuth con una URL fija sin parámetros de enrutamiento.
// El punto de entrada conserva las comprobaciones de sesión, baneo, state y PKCE.
$_GET['ruta'] = 'mp_retorno';
require __DIR__ . '/index.php';
