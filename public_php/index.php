<?php
// Las peticiones de la aplicación entran por este archivo.
// El parámetro ?ruta=... elige la pantalla o acción, sin configurar reglas de Apache.
$ruta = $_GET['ruta'] ?? 'inicio';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$esLectura = in_array($metodo, ['GET', 'HEAD'], true);
$vistas = [
    'inicio' => 'index.php',
    'crear_campana' => 'crear_campana.php',
    'mis_donaciones' => 'mis_donaciones.php',
    'preguntas_frecuentes' => 'preguntas_frecuentes.php',
    'perfil' => 'perfil.php'
    
];

// Estas rutas pasan por un controlador en lugar de abrir la vista directamente.
$rutasConControlador = [
    'buscador',
    'resultados_buscar',
    'registro',
    'ingresar',
    'salir',
    'mis_campanas',
    'administracion',
    'contacto',
];

if (!is_string($ruta) || (!in_array($ruta, $rutasConControlador, true) && !isset($vistas[$ruta]))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Página no encontrada.';
    return;
}

$aceptaPost = in_array($ruta, ['registro', 'ingresar', 'salir', 'resultados_buscar', 'contacto'], true);
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
    $usuarios = new UsuariosRepository(conectarBd());

    if (isset($_SESSION['usuario_id'])) {
        $usuario = is_int($_SESSION['usuario_id'])
            ? $usuarios->findById($_SESSION['usuario_id'])
            : null;
        if ($usuario === null) {
            unset($_SESSION['usuario_id']);
        } else {
            $usuarioActual = [
                'nombre' => $usuario->getNombre(),
                'admin' => $usuario->getAdmin(),
            ];
        }
    }

    if (in_array($ruta, ['ingresar', 'salir'], true)) {
        require_once __DIR__ . '/../app/controladores/IngresoController.php';
        $controlador = new IngresoController($usuarios);
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

if ($ruta === 'contacto') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/ContactoController.php';
    $controlador = new ContactoController(new ContactoRepository(conectarBd()));
    if ($metodo === 'POST') {
        $controlador->procesarContacto($rutaBase, $rutaAssets, $usuario ?? null);
    } else {
        $controlador->mostrarFormulario($rutaBase, $rutaAssets, $usuario ?? null);
    }
    return;
}

if (in_array($ruta, ['buscador', 'resultados_buscar'], true)) {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/BuscadorController.php';
    $pdo = conectarBd();
    $controlador = new BuscadorController(new CampRepository($pdo), new CatCampRepository($pdo));
    // Las búsquedas nuevas usan GET. Se conserva la lectura de formularios POST anteriores.
    $controlador->mostrar($rutaBase, $rutaAssets, $metodo === 'POST' ? $_POST : $_GET,
        $ruta === 'resultados_buscar', $usuarioActual);
    return;
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

if ($ruta === 'administracion') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/repositorios/UsuariosRepository.php';
    require_once __DIR__ . '/../app/repositorios/CampRepository.php';
    require_once __DIR__ . '/../app/controladores/AdministracionController.php';

    $pdo = conectarBd();

    $controlador = new AdministracionController(
        new UsuariosRepository($pdo),
        new CampRepository($pdo)
    );

    $controlador->mostrar(
        $rutaBase,
        $rutaAssets,
        $usuario ?? null
    );

    return;
}
