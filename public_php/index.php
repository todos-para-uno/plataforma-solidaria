<?php
// Las peticiones de la aplicación entran por este archivo.
// El parámetro ?ruta=... elige la pantalla o acción, sin configurar reglas de Apache.
$ruta = $_GET['ruta'] ?? 'inicio';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$esLectura = in_array($metodo, ['GET', 'HEAD'], true);

// 1. Definimos las rutas permitidas y sus respectivas vistas estáticas
$vistas = [
    'buscador' => 'buscador.php',
    'resultados_buscar' => 'resultados_buscar.php',
    'ingresar' => 'ingresar.php',
    'contacto' => 'contacto.php',
];

// Rutas totales válidas en la aplicación (vistas estáticas + inicio + controladores + crear_campana)
$rutasValidas = array_merge(['inicio', 'registro', 'crear_campana'], array_keys($vistas));

// 2. Validamos que la ruta solicitada exista
if (!is_string($ruta) || !in_array($ruta, $rutasValidas, true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Página no encontrada.';
    return;
}

// 3. Validamos los métodos permitidos (GET, HEAD, POST según corresponda)
// Las rutas que aceptan POST son 'registro', 'crear_campana' y 'resultados_buscar'
$rutasConPost = ['registro', 'crear_campana', 'resultados_buscar'];
$aceptaPost = in_array($ruta, $rutasConPost, true);

if (!$esLectura && !aceptaPost) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Método no permitido.';
    return;
}

if (!$esLectura && $aceptaPost && $metodo !== 'POST') {
    // Si la ruta acepta POST pero llegó otro método no válido (ej: PUT/DELETE)
    http_response_code(405);
    header('Allow: GET, HEAD, POST');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Método no permitido.';
    return;
}

header('Content-Type: text/html; charset=UTF-8');
$rutaBase = '.';
$rutaAssets = './assets';

// 4. Direccionamiento según la ruta

if ($ruta === 'inicio') {
    require __DIR__ . '/../app/vistas/index.php';
    return;
}

if ($ruta === 'crear_campana') {
    require_once __DIR__ . '/../app/vistas/crear_campana.php';
    return;
}

if (isset($vistas[$ruta])) {
    require __DIR__ . '/../app/vistas/' . $vistas[$ruta];
    return;
}

// 5. Si es la ruta de registro, delegamos a su controlador
require_once __DIR__ . '/../app/controladores/RegistroController.php';
$controlador = new RegistroController();

if ($metodo === 'POST') {
    $controlador->procesarRegistro($rutaBase, $rutaAssets);
} else {
    $controlador->mostrarFormulario($rutaBase, $rutaAssets);
}
