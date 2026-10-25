<?php

require_once __DIR__ . '/../../app/modelos/Validaciones.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../app/controladores/ValidacionesController.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';

function validacionesControllerTest(PDO $pdo): void
{
    //echo 'INICIANDO TEST: ValidacionesController.php' . eol();

    $repositorio = new ValidacionesRepository($pdo);
    $controlador = new ValidacionesController($repositorio);

    // Comprobamos un nivel existente.
    ob_start();
    $controlador->buscarPorNivel(1);
    $respuesta = ob_get_clean();

    $datos = json_decode($respuesta, true);

    TestUtils::comprobar(
        is_array($datos)
            && ($datos['nivel'] ?? null) === 1,
        'buscarPorNivel devuelve los datos del nivel 1' . eol()
    );

    // Comprobamos un nivel inexistente.
    ob_start();
    $controlador->buscarPorNivel(999999);
    $respuesta = ob_get_clean();

    TestUtils::comprobar(
        str_contains($respuesta, 'No existe'),
        'buscarPorNivel informa cuando el nivel no existe' . eol()
    );

    // Comprobamos un nivel inválido.
    ob_start();
    $controlador->buscarPorNivel(0);
    $respuesta = ob_get_clean();

    TestUtils::comprobar(
        str_contains($respuesta, 'entero positivo'),
        'buscarPorNivel rechaza niveles inválidos' . eol()
    );
}