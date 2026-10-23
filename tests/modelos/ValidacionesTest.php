<?php
function validacionesTest()
{
    require_once __DIR__ . '/../../app/modelos/Validaciones.php';

    echo "INICIANDO TEST: app/modelos/Validaciones.php<br>";

    echo "Caso 1 - crea una validación con datos válidos: ";
    $validacion = new Validaciones(5, 'Naranja', 'Usuario baneado');
    $casoValido = $validacion->getNivel() === 5
        && $validacion->getNombre() === 'Naranja'
        && $validacion->getDescripcion() === 'Usuario baneado';
    echo $casoValido ? "OK<br>" : "FALLÓ =(<br>";

    echo "Caso 2 - no permite que el nivel sea menor a 1: ";

    try {
        new Validaciones(0, 'Identidad');
        echo "FALLÓ =(<br>";
    } catch (InvalidArgumentException $excepcion) {
        echo "OK<br>";
    }

    echo "Caso 3 - No permite que el nombre del nivel quede vacío: ";
    try {
        new Validaciones(1, ' ');
        echo "FALLÓ =( <br>";
    } catch (InvalidArgumentException $excepcion) {
        echo "OK<br>";
    }

    $filaValidacion = [
    'id' => 12,
    'nivel' => 3,
    'nombre' => 'azul',
    'descripcion' => 'Validación de ejemplo'
    ];

    $validacionRecuperada = Validaciones::desdePersistencia(
        $filaValidacion
    );

    TestUtils::comprobar(
        $validacionRecuperada->getId() === 12,
        'Se recuperó correctamente el ID' . eol()
    );

    TestUtils::comprobar(
        $validacionRecuperada->getNivel() === 3,
        'Se recuperó correctamente el nivel' . eol()
    );

    TestUtils::comprobar(
        $validacionRecuperada->getNombre() === 'azul',
        'Se recuperó correctamente el nombre' . eol()
    );

    TestUtils::comprobar(
        $validacionRecuperada->getDescripcion() === 'Validación de ejemplo',
        'Se recuperó correctamente la descripción' . eol()
    );

    
    echo "<hr>";
}
?>
