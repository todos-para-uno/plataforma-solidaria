<?php
function run_modelos_tests(){
    require_once __DIR__ . '/modelos/CategoriaTest.php';
    require_once __DIR__ . '/modelos/DonacionTest.php';
    require_once __DIR__ . '/modelos/RegistroDbTest.php';
    require_once __DIR__ . '/modelos/SubCategoriaInsumosTest.php';
    require_once __DIR__ . '/modelos/MensajesTest.php';
    require_once __DIR__ . '/modelos/ValidacionesTest.php';
    require_once __DIR__ . '/modelos/UsuariosTest.php';
    require_once __DIR__ . '/modelos/CampTest.php';
    require_once __DIR__ . '/modelos/UbicacionesTest.php';
    
    categoriaTest();
    donacionTest();
    registroDbTest();
    subCategoriaInsumosTest();
    mensajesTest();
    validacionesTest();
    usuariosTest();
    campTest();
    ubicacionesTest();
}
function run_repositorios_tests(){
    require_once __DIR__ . '/repositorios/UsuariosRepository.php';
    require_once __DIR__ . '/repositorios/ValidacionesRepository.php';

    validacionesRepositoryTest();
    usuariosRepositoryTest();
    
}

run_modelos_tests();
//run_repositorios_tests();
