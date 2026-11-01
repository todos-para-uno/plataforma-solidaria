<?php
// Las peticiones de la aplicación entran por este archivo.
// El parámetro ?ruta=... elige la pantalla o acción, sin configurar reglas de Apache.
$ruta = $_GET['ruta'] ?? 'inicio';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$esLectura = in_array($metodo, ['GET', 'HEAD'], true);
$vistas = [
    'inicio' => 'index.php',
    'preguntas_frecuentes' => 'preguntas_frecuentes.php',

    
];

// Estas rutas pasan por un controlador en lugar de abrir la vista directamente.
$rutasConControlador = [
    'crear_campana',
    'perfil',
    'actualizar_documento',
    'mis_donaciones',
    'recepcion_insumos',
    'punto_entrega',
    'campana',
    'buscador',
    'resultados_buscar',
    'registro',
    'ingresar',
    'salir',
    'mis_campanas',
    'administracion',
    'contacto',
    'banear',
    'finalizar_baneo',
    'admin_mensajes',
    'admin_usuarios',
    'admin_actividad',
    'admin_password',
    'admin_desbloquear',
];

if (!is_string($ruta) || (!in_array($ruta, $rutasConControlador, true) && !isset($vistas[$ruta]))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Página no encontrada.';
    return;
}

$soloPost = in_array($ruta, ['salir', 'banear', 'finalizar_baneo', 'admin_password', 'admin_desbloquear', 'actualizar_documento'], true);
$aceptaPost = $soloPost || in_array($ruta, ['registro', 'ingresar', 'resultados_buscar', 'contacto', 'crear_campana', 'punto_entrega', 'recepcion_insumos'], true);
if (($soloPost && $metodo !== 'POST') || (!$esLectura && !($aceptaPost && $metodo === 'POST'))) {
    http_response_code(405);
    header('Allow: ' . ($soloPost ? 'POST' : ($aceptaPost ? 'GET, HEAD, POST' : 'GET, HEAD')));
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
            require_once __DIR__ . '/../app/repositorios/ConfianzaRepository.php';
            $_SESSION['nivel_confianza'] = (new ConfianzaRepository(conectarBd()))->actualizar($usuario->getId());
            $usuario = $usuarios->findById($usuario->getId());
            $usuarioActual = [
                'nombre' => $usuario->getNombre(),
                'mail' => $usuario->getEmail(),
                'admin' => $usuario->getAdmin(),
            ];
        }
    }

    if (in_array($ruta, ['ingresar', 'salir'], true)) {
        require_once __DIR__ . '/../app/controladores/IngresoController.php';
        $controlador = new IngresoController($usuarios, $baneos, new EstadoAutenticacionRepository(conectarBd()));
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

if ($ruta === 'admin_mensajes') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/AdministracionMensajesController.php';
    $pdo = conectarBd();
    (new AdministracionMensajesController(
        new AdministracionMensajesRepository($pdo), new UsuariosRepository($pdo)
    ))->atender();
    return;
}

if (in_array($ruta, ['admin_usuarios', 'admin_actividad', 'admin_password', 'admin_desbloquear'], true)) {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/AdministracionUsuariosController.php';
    $pdo = conectarBd();
    $controlador = new AdministracionUsuariosController($pdo, new UsuariosRepository($pdo),
        new EstadoAutenticacionRepository($pdo));
    if ($ruta === 'admin_usuarios') $controlador->buscar();
    elseif ($ruta === 'admin_actividad') $controlador->actividad();
    else $controlador->password($ruta === 'admin_desbloquear');
    return;
}

if (in_array($ruta, ['banear', 'finalizar_baneo'], true)) {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/BaneoController.php';
    $pdo = conectarBd();
    $controlador = new BaneoController(new UsuariosRepository($pdo), new BaneoRepository($pdo));
    if ($ruta === 'banear') {
        $controlador->procesarBaneo();
    } else {
        $controlador->finalizarBaneo();
    }
    return;
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

if ($ruta === 'crear_campana') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/CrearCampanaController.php';
    (new CrearCampanaController(conectarBd()))->mostrar($usuario ?? null, $rutaBase, $rutaAssets);
    return;
}

if ($ruta === 'mis_donaciones') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/MisDonacionesController.php';
    (new MisDonacionesController(new DonacionRepository(conectarBd())))->mostrar($usuario ?? null, $rutaBase, $rutaAssets);
    return;
}

if ($ruta === 'recepcion_insumos') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/RecepcionInsumosController.php';
    (new RecepcionInsumosController(conectarBd()))->atender($usuario ?? null, $rutaBase, $rutaAssets);
    return;
}

if ($ruta === 'punto_entrega') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/PuntoEntregaController.php';
    (new PuntoEntregaController(conectarBd()))->atender($usuario ?? null, $rutaBase, $rutaAssets);
    return;
}

if ($ruta === 'campana') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/CampanaController.php';
    require_once __DIR__ . '/../app/repositorios/PuntoEntregaRepository.php';
    $pdo = conectarBd();
    $controlador = new CampanaController(
        new CampRepository($pdo), new CatCampRepository($pdo),
        new UsuariosRepository($pdo), new ValidacionesRepository($pdo),
        new ObjetivosRepository($pdo), new PuntoEntregaRepository($pdo), new CampanaImagenesRepository($pdo)
    );
    $controlador->mostrar($rutaBase, $rutaAssets, $_GET['id'] ?? null, $usuarioActual);
    return;
}

if (in_array($ruta, ['buscador', 'resultados_buscar'], true)) {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/BuscadorController.php';
    $pdo = conectarBd();
    $controlador = new BuscadorController(new CampRepository($pdo), new CatCampRepository($pdo), new CampanaImagenesRepository($pdo));
    // Las búsquedas nuevas usan GET. Se conserva la lectura de formularios POST anteriores.
    $controlador->mostrar($rutaBase, $rutaAssets, $metodo === 'POST' ? $_POST : $_GET,
        $ruta === 'resultados_buscar', $usuarioActual);
    return;
}

if (in_array($ruta, ['perfil', 'actualizar_documento'], true)) {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../app/controladores/PerfilController.php';
    (new PerfilController(conectarBd()))->mostrar($usuario ?? null, $rutaBase, $rutaAssets);
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
