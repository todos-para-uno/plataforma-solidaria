<?php
function usuariosRepositoryTest (){
require_once __DIR__ . '/../../config/conexion.php';

require_once __DIR__ . '/../../app/repositorios/CrudRepository.php';
require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';


echo "INICIANDO TEST: app/repositorios/UsuariosRepository.php" . "<br>";

$usuariosRepositorio = new UsuariosRepository($pdo);

$nombre = "pepe";
$mail = "pepe@pepeylola.co.uk";

$id = $usuariosRepositorio->save([
        'nombre' => $nombre,
        'mail' => $mail,
        'telefono' => '0000000000',
        'pass' => password_hash('clave-temporal', PASSWORD_DEFAULT),
        'verificado' => 0,
        'admin' => 0,
        'validacion' => 0,
    ]);
}