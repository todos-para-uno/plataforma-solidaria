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
    'contacto' => 'contacto.php',
    'crear_campana' => 'crear_campana.php',
    'mis_donaciones' => 'mis_donaciones.php',
    'preguntas_frecuentes' => 'preguntas_frecuentes.php',
    'perfil' => 'perfil.php'
    
];

// Estas rutas pasan por un controlador en lugar de abrir la vista directamente.
$rutasConControlador = ['registro', 'ingresar', 'salir', 'mis_campanas'];

if (!is_string($ruta) || (!in_array($ruta, $rutasConControlador, true) && !isset($vistas[$ruta]))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Página no encontrada.';
    return;
}

$aceptaPost = in_array($ruta, ['registro', 'ingresar', 'salir', 'resultados_buscar'], true);
if (($ruta === 'salir' && $metodo !== 'POST') || (!$esLectura && !($aceptaPost && $metodo === 'POST'))) {
    http_response_code(405);
    header('Allow: ' . ($ruta === 'salir' ? 'POST' : ($aceptaPost ? 'GET, HEAD, POST' : 'GET, HEAD')));
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Método no permitido.';
    return;
}

header('Content-Type: text/html; charset=UTF-8');
$rutaBase = '.';
$rutaAssets = './assets';

require_once __DIR__ . '/../app/seguridad/Csrf.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}
$csrfToken = Csrf::generar();
$usuarioActual = null;

if (isset($_SESSION['usuario_id']) || in_array($ruta, ['ingresar', 'salir'], true)) {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/repositorios/UsuariosRepository.php';
    require_once __DIR__ . '/../app/repositorios/BaneoRepository.php';
    $usuarios = new UsuariosRepository(conectarBd());
    $baneos = new BaneoRepository(conectarBd());

    if (isset($_SESSION['usuario_id'])) {
        $usuario = is_int($_SESSION['usuario_id'])
            ? $usuarios->findById($_SESSION['usuario_id'])
            : null;
        if ($usuario === null) {
            unset($_SESSION['usuario_id']);
        } elseif ($baneos->estaBaneado($usuario->getId())) {
            // Revocar la identidad antes de ejecutar cualquier acción de la petición.
            $_SESSION = [];
            session_regenerate_id(true);
            Csrf::generar();
            http_response_code(403);
            echo 'Tu cuenta se encuentra baneada.';
            return;
        } else {
            $usuarioActual = [
                'nombre' => $usuario->getNombre(),
                'admin' => $usuario->getAdmin(),
            ];
        }
    }

    if (in_array($ruta, ['ingresar', 'salir'], true)) {
        require_once __DIR__ . '/../app/controladores/IngresoController.php';
        $controlador = new IngresoController($usuarios, $baneos);
        if ($ruta === 'salir') {
            $controlador->cerrarSesion($rutaBase);
        } elseif ($usuarioActual !== null) {
            header('Location: ' . $rutaBase . '/index.php', true, 303);
        } elseif ($metodo === 'POST') {
            $controlador->procesarIngreso($rutaBase, $rutaAssets);
        } else {
            $controlador->mostrarFormulario($rutaBase, $rutaAssets);
        }
        return;
    }
}

if (isset($vistas[$ruta])) {
    require __DIR__ . '/../app/vistas/' . $vistas[$ruta];
    return;
}

if ($ruta === 'mis_campanas') {
    require_once __DIR__ . '/../app/controladores/MisCampanasController.php';
    $controlador = new MisCampanasController();
    $controlador->mostrar($rutaBase, $rutaAssets, $usuario ?? null);
    return;
}

if ($ruta === 'registro') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/repositorios/UsuariosRepository.php';
    require_once __DIR__ . '/../app/repositorios/ValidacionesRepository.php';
    require_once __DIR__ . '/../app/controladores/RegistroController.php';

    $pdo = conectarBd();

    $controlador = new RegistroController(
        new UsuariosRepository($pdo),
        new ValidacionesRepository($pdo)
    );

    if ($metodo === 'POST') {
        $controlador->procesarRegistro($rutaBase, $rutaAssets);
    } else {
        $controlador->mostrarFormulario($rutaBase, $rutaAssets);
    }

    return;
}
