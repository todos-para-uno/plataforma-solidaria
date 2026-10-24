<?php
// Las peticiones de la aplicación entran por este archivo.
// El parámetro ?ruta=... elige la pantalla o acción, sin configurar reglas de Apache.
$ruta = $_GET['ruta'] ?? 'inicio';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$esLectura = in_array($metodo, ['GET', 'HEAD'], true);
$vistas = [
    'inicio' => 'index.php',
    'buscador' => 'buscador.php',
    'resultados_buscar' => 'resultados_buscar.php',
    'ingresar' => 'ingresar.php',
    'contacto' => 'contacto.php',
    'crear_campana' => 'crear_campana.php',
];

if (!is_string($ruta) || ($ruta !== 'registro' && !isset($vistas[$ruta]))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Página no encontrada.';
    return;
}

$aceptaPost = in_array($ruta, ['registro', 'resultados_buscar'], true);
if (!$esLectura && !($aceptaPost && $metodo === 'POST')) {
    http_response_code(405);
    header('Allow: ' . ($aceptaPost ? 'GET, HEAD, POST' : 'GET, HEAD'));
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Método no permitido.';
    return;
}

header('Content-Type: text/html; charset=UTF-8');
$rutaBase = '.';
$rutaAssets = './assets';

if (isset($vistas[$ruta])) {
    require __DIR__ . '/../app/vistas/' . $vistas[$ruta];
    return;
}

require_once __DIR__ . '/../app/controladores/RegistroController.php';
$controlador = new RegistroController();

if ($metodo === 'POST') {
    $controlador->procesarRegistro($rutaBase, $rutaAssets);
} else {
    $controlador->mostrarFormulario($rutaBase, $rutaAssets);
}
