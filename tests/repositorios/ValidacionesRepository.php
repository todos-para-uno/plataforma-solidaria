<?php

require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';


function validacionesRepositoryTest(PDO $pdo): void
{
    echo 'INICIANDO TEST: app/repositorios/ValidacionesRepository.php' . eol();

    $repositorio = new ValidacionesRepository($pdo);

    // Utilizamos un nivel fuera del rango habitual (1 a 4).
    // Lo comprobamos antes de insertar para evitar colisiones.
    $nivelPrueba = 1000;

    while ($repositorio->findByNivel($nivelPrueba) !== []) {
        $nivelPrueba++;
    }

    $identificadorPrueba = bin2hex(random_bytes(8));

    $nombrePrueba = 'Test_' . $identificadorPrueba;
    $descripcionPrueba = 'Registro temporal para pruebas';

    $pdo->beginTransaction();

    try {
        // 1. INSERTAR
        $id = $repositorio->save([
            'nivel' => $nivelPrueba,
            'nombre' => $nombrePrueba,
            'descripcion' => $descripcionPrueba
        ]);

        TestUtils::comprobar(
            (int) $id > 0,
            'Se insertó una validación y se obtuvo su ID' . eol()
        );

        $validacionesPorNombre = $repositorio->findByNombre($nombrePrueba);

        TestUtils::comprobar(
            count($validacionesPorNombre) === 1,
            'findByNombre encuentra una validación con ese nombre' . eol()
        );

        TestUtils::comprobar(
            $validacionesPorNombre[0] instanceof Validaciones,
            'findByNombre devuelve objetos Validaciones' . eol()
        );

        $validacionesPorNivel = $repositorio->findByNivel($nivelPrueba);

        TestUtils::comprobar(
            count($validacionesPorNivel) > 0,
            'findByNivel devuelve una colección no vacía' . eol()
        );

        $todasSonValidaciones = true;
        $encontroValidacionPrueba = false;

        foreach ($validacionesPorNivel as $validacion) {
            if (!($validacion instanceof Validaciones)) {
                $todasSonValidaciones = false;
                continue;
            }

            if ($validacion->getId() === (int) $id) {
                $encontroValidacionPrueba = true;
            }
        }

        TestUtils::comprobar(
            $todasSonValidaciones,
            'findByNivel devuelve únicamente objetos Validaciones' . eol()
        );

        TestUtils::comprobar(
            $encontroValidacionPrueba,
            'findByNivel incluye la validación insertada' . eol()
        );

        $validacionEntidad = $repositorio->findById($id);

        $validaciones = $repositorio->findAll();

        TestUtils::comprobar(
            count($validaciones) > 0,
            'findAll devuelve una colección no vacía' . eol()
        );

        TestUtils::comprobar(
            $validaciones[0] instanceof Validaciones,
            'findAll devuelve objetos Validaciones' . eol()
        );

        $validacionEncontrada = false;

        foreach ($validaciones as $validacion) {
            if ($validacion->getId() === (int) $id) {
                $validacionEncontrada = true;
                break;
            }
        }

        TestUtils::comprobar(
            $validacionEncontrada,
            'findAll incluye la validación insertada' . eol()
        );

        TestUtils::comprobar(
            $validacionEntidad instanceof Validaciones,
            'findById devuelve un objeto Validaciones' . eol()
        );


        TestUtils::comprobar(
            $validacionEntidad->getNivel() === $nivelPrueba,
            'La entidad recuperada conserva su nivel' . eol()
        );        

        // 2. RECUPERAR POR ID
        $filaValidacion = $repositorio->findRowById($id);

        TestUtils::comprobar(
            $filaValidacion !== null,
            'Se recuperó la validación por su ID' . eol()
        );

        TestUtils::comprobar(
            (int) $filaValidacion['nivel'] === $nivelPrueba,
            'El nivel recuperado coincide con el insertado' . eol()
        );

        // 3. RECUPERAR POR NOMBRE
        $validacionesEncontradas = $repositorio->findByNombre(
            $nombrePrueba
        );

        TestUtils::comprobar(
            count($validacionesEncontradas) === 1,
            'Se encontró exactamente una validación con ese nombre' . eol()
        );

        // 4. ACTUALIZAR
        $nuevoNombre = 'Edit_' . $identificadorPrueba;

        $repositorio->save([
            'id' => $id,
            'nombre' => $nuevoNombre
        ]);

        $filaActualizada = $repositorio->findRowById($id);

        TestUtils::comprobar(
            $filaActualizada !== null
                && $filaActualizada['nombre'] === $nuevoNombre,
            'La actualización del nombre se guardó correctamente' . eol()
        );

        // 5. ELIMINAR
        TestUtils::comprobar(
            $repositorio->delete($id),
            'La validación se eliminó correctamente' . eol()
        );

        TestUtils::comprobar(
            $repositorio->findRowById($id) === null,
            'La validación eliminada ya no puede recuperarse' . eol()
        );

        echo 'Pruebas de ValidacionesRepository completadas correctamente.' . eol();

    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    echo '<hr>';
}


/*function validacionesRepositoryTest (PDO $pdo): void
{
    require_once __DIR__ . '/../../app/repositorios/CrudRepository.php';
    require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';

    echo "INICIANDO TEST: app/repositorios/ValidacionesRepository.php" . "<br>";

    $validacionesRepositorio = new ValidacionesRepository($pdo);

    $nombreArray = array('blanco','celeste','azul','violeta');
    $descripcionArray = array('la más baja','La segunda más baja','a tener en cuenta','la más alta');

    for ($nivel = 1; $nivel < 5; $nivel ++){
        $validaciones = $validacionesRepositorio->findByNivel($nivel);    
        if ($validaciones === []){
            $id = $validacionesRepositorio->save([
                    'nivel' => $nivel,
                    'nombre' => $nombreArray[$nivel-1],
                    'descripcion' => $descripcionArray[$nivel-1],
                ]);
        } else {
            echo "La validacion nivel " . $nivel . " ya existia y es " . $nombreArray[$nivel-1] . ".<br>";
        }

    }
    echo '<hr>';
}
*/