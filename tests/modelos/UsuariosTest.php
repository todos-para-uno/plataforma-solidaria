<?php

require_once __DIR__ . '/../../app/modelos/Usuarios.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';



function usuariosTest(){
    $usuarioNuevo = Usuarios::crear(
        'Ana',
        'ana@example.com',
        'MiPasswordSegura123'
    );
    $filaUsuario = [
        'id' => 5,
        'nombre' => $usuarioNuevo->getNombre(),
        'mail' => $usuarioNuevo->getEmail(),
        'pass' => $usuarioNuevo->getPassword(),
        'telefono' => null,
        'tipo_documento' => null,
        'documento' => null,
        'verificado' => 1,
        'admin' => 0,
        'validacion' => 3
    ];

    $usuarioRecuperado = Usuarios::desdePersistencia($filaUsuario);
    TestUtils::comprobar(
        $usuarioRecuperado->getId() === 5,
        'Se recuperó correctamente el ID' . eol()
    );

    TestUtils::comprobar(
        $usuarioRecuperado->verificarPassword('MiPasswordSegura123'),
        'La contraseña se verifica correctamente' . eol()
    );

    TestUtils::comprobar(
        $usuarioRecuperado->getPassword() === $usuarioNuevo->getPassword(),
        'El hash original se conservó sin modificaciones' . eol()
    );

    echo "Prueba para /../../app/modelos/Usuarios.php completada correctamente." . eol();
}