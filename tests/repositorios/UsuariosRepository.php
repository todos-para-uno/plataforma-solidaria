<?php
function usuariosRepositoryTest (){
    require_once __DIR__ . '/../../config/conexion.php';
    $pdo = conectarBd();
    
    echo "PREPARANDO OBJETO VALIDACION PARA UsuariosRepository...";
    require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
    $validacionesRepositorio = new ValidacionesRepository($pdo);
    $validaciones = $validacionesRepositorio->findByNivel(1);
    if ($validaciones === []) {
        throw new RuntimeException('No existe una validación con nivel 1.');
    }
    $validacionId = $validaciones[0]['id'];


    echo "INICIANDO TEST: app/repositorios/UsuariosRepository.php" . "<br>";
    require_once __DIR__ . '/../../app/repositorios/CrudRepository.php';
    require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
    $usuariosRepositorio = new UsuariosRepository($pdo);

    $nombre = "pepe";
    $mail = "pepe@pepeylola.co.uk";
    $usuarios = $usuariosRepositorio->findByMail($mail);
    if ($usuarios === []) {
        $id = $usuariosRepositorio->save([
                'nombre' => $nombre,
                'mail' => $mail,
                'telefono' => '0000000000',
                'pass' => password_hash('clave-temporal', PASSWORD_DEFAULT),
                'verificado' => 0,
                'admin' => 0,
                'validacion' => $validacionId, 
            ]);
    } else {
        echo "Se intento agregar usuario con mail " . $mail . " pero ya existe y es ";
        echo $usuarios[0]['nombre'] . " con id " . $usuarios[0]['id'];
    }
    echo '<hr>';
}