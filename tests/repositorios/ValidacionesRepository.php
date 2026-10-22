<?php
function validacionesRepositoryTest (){
    require_once __DIR__ . '/../../config/conexion.php';
    $pdo = conectarBd();

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