<?php
// Las peticiones de la aplicación entran por este archivo.
// El parámetro ?ruta=... elige la pantalla o acción, sin configurar reglas de Apache.
$ruta = $_GET['ruta'] ?? 'inicio';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$esLectura = in_array($metodo, ['GET', 'HEAD'], true);

if (!is_string($ruta) || !in_array($ruta, ['inicio', 'registro', 'ingresar', 'contacto'], true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Página no encontrada.';
    return;
}

if (!$esLectura && !($ruta === 'registro' && $metodo === 'POST')) {
    http_response_code(405);
    header('Allow: ' . ($ruta === 'registro' ? 'GET, HEAD, POST' : 'GET, HEAD'));
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Método no permitido.';
    return;
}

header('Content-Type: text/html; charset=UTF-8');
$rutaBase = '.';
$rutaAssets = './assets';

if ($ruta === 'inicio') {
    require __DIR__ . '/../app/vistas/index.php';
    return;
}

// Ingresar y Contacto por ahora solo muestran el formulario.
// Cuando existan sus controladores, acá se agrega el POST como en registro.
if ($ruta === 'ingresar') {
    require __DIR__ . '/../app/vistas/ingresar.php';
    return;
}

if ($ruta === 'contacto') {
    require __DIR__ . '/../app/vistas/contacto.php';
    return;
}

require_once __DIR__ . '/../app/controladores/RegistroController.php';
$controlador = new RegistroController();

if ($metodo === 'POST') {
    $controlador->procesarRegistro($rutaBase, $rutaAssets);
} else {
    $controlador->mostrarFormulario($rutaBase, $rutaAssets);
}
