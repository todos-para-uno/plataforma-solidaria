<?php
// Evita que la salida de las pruebas envíe cabeceras HTTP
// antes de ejecutar las pruebas de controladores.
ob_start();
$pruebas = [
    'modelos' => [
        'Baneo' => [
            'archivo' => 'modelos/BaneoTest.php',
            'funcion' => 'baneoTest',
        ],
        'Categoria' => [
            'archivo' => 'modelos/CategoriaTest.php',
            'funcion' => 'categoriaTest',
        ],
        'Donacion' => [
            'archivo' => 'modelos/DonacionTest.php',
            'funcion' => 'donacionTest',
        ],
        'RegistroDb' => [
            'archivo' => 'modelos/RegistroDbTest.php',
            'funcion' => 'registroDbTest',
        ],
        'SubCategoriaInsumos' => [
            'archivo' => 'modelos/SubCategoriaInsumosTest.php',
            'funcion' => 'subCategoriaInsumosTest',
        ],
        'Mensajes' => [
            'archivo' => 'modelos/MensajesTest.php',
            'funcion' => 'mensajesTest',
        ],
        'Validaciones' => [
            'archivo' => 'modelos/ValidacionesTest.php',
            'funcion' => 'validacionesTest',
        ],
        'Usuarios' => [
            'archivo' => 'modelos/UsuariosTest.php',
            'funcion' => 'usuariosTest',
        ],
        'Camp' => [
            'archivo' => 'modelos/CampTest.php',
            'funcion' => 'campTest',
        ],
        'Ubicaciones' => [
            'archivo' => 'modelos/UbicacionesTest.php',
            'funcion' => 'ubicacionesTest',
        ],
    ],
    'servicios' => [
        'ImagenesCampana' => [
            'archivo' => 'servicios/ImagenesCampanaTest.php',
            'funcion' => 'imagenesCampanaTest',
        ],
    ],
    'repositorios' => [
        'EstadoAutenticacionRepository' => [
            'archivo' => 'repositorios/EstadoAutenticacionRepositoryTest.php',
            'funcion' => 'estadoAutenticacionRepositoryTest',
        ],
        'BaneoRepository' => [
            'archivo' => 'repositorios/BaneoRepository.php',
            'funcion' => 'baneoRepositoryTest',
        ],
        'BuscadorRepository' => [
            'archivo' => 'repositorios/BuscadorRepositoryTest.php',
            'funcion' => 'buscadorRepositoryTest',
        ],
        'ValidacionesRepository' => [
            'archivo' => 'repositorios/ValidacionesRepository.php',
            'funcion' => 'validacionesRepositoryTest',
        ],
        'UsuariosRepository' => [
            'archivo' => 'repositorios/UsuariosRepository.php',
            'funcion' => 'usuariosRepositoryTest',
        ],
    ],
    'controladores' => [
        'CampanaController' => [
            'archivo' => 'controladores/CampanaControllerTest.php',
            'funcion' => 'campanaControllerTest',
        ],
        'BaneoController' => [
            'archivo' => 'controladores/BaneoControllerTest.php',
            'funcion' => 'baneoControllerTest',
        ],
        'BuscadorController' => [
            'archivo' => 'controladores/BuscadorControllerTest.php',
            'funcion' => 'buscadorControllerTest',
        ],
        'ContactoController' => [
            'archivo' => 'controladores/ContactoControllerTest.php',
            'funcion' => 'contactoControllerTest',
        ],
        'MisCampanasController' => [
            'archivo' => 'controladores/MisCampanasControllerTest.php',
            'funcion' => 'misCampanasControllerTest',
        ],
        'IngresoController' => [
            'archivo' => 'controladores/IngresoControllerTest.php',
            'funcion' => 'ingresoControllerTest',
        ],
        'ValidacionesController' => [
            'archivo' => 'controladores/ValidacionesControllerTest.php',
            'funcion' => 'validacionesControllerTest',
        ],
        'RegistroController' => [
            'archivo' => 'controladores/RegistroControllerTest.php',
            'funcion' => 'registroControllerTest',
        ],
        'AdministracionUsuariosController' => [
            'archivo' => 'controladores/AdministracionUsuariosControllerTest.php',
            'funcion' => 'administracionUsuariosControllerTest',
        ],
        'AdministracionController' => [
            'archivo' => 'controladores/AdministracionControllerTest.php',
            'funcion' => 'administracionControllerTest',
        ],
    ],
];

function ejecutarPrueba(array $prueba, ?PDO $pdo): void
{
    require_once __DIR__ . '/' . $prueba['archivo'];

    $funcion = $prueba['funcion'];

    if ($pdo === null) {
        $funcion();
    } else {
        $funcion($pdo);
    }
}

$argumentos = array_slice($argv, 1);
$suite = $argumentos[0] ?? 'todos';
$nombrePrueba = $argumentos[1] ?? null;

if (count($argumentos) > 2) {
    fwrite(STDERR, 'Demasiados argumentos.' . PHP_EOL);
    exit(1);
}

if ($suite === 'todos') {
    if ($nombrePrueba !== null) {
        fwrite(
            STDERR,
            'No se puede seleccionar una prueba con la suite todos.'
            . PHP_EOL
        );
        exit(1);
    }

    $suitesSeleccionadas = array_keys($pruebas);
} elseif (isset($pruebas[$suite])) {
    $suitesSeleccionadas = [$suite];
} else {
    fwrite(STDERR, "Suite desconocida: {$suite}" . PHP_EOL);
    exit(1);
}

if ($nombrePrueba !== null && !isset($pruebas[$suite][$nombrePrueba])) {
    fwrite(
        STDERR,
        "Prueba desconocida: {$nombrePrueba}" . PHP_EOL
    );
    exit(1);
}

$pdo = null;

foreach ($suitesSeleccionadas as $suiteActual) {
    $pruebasSeleccionadas = $nombrePrueba === null
        ? $pruebas[$suiteActual]
        : [$pruebas[$suiteActual][$nombrePrueba]];

    if ($suiteActual === 'repositorios'
        || $suiteActual === 'controladores') {
        if ($pdo === null) {
            require_once __DIR__ . '/../config/conexion.php';
            $pdo = conectarBd();
        }
    }

    foreach ($pruebasSeleccionadas as $prueba) {
        ejecutarPrueba($prueba, $pdo);
    }
}