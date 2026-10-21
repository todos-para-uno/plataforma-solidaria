<?php
function run_modelos_tests(){
    require_once __DIR__ . '/modelos/CategoriaTest.php';
    require_once __DIR__ . '/modelos/DonacionTest.php';
    require_once __DIR__ . '/modelos/RegistroDbTest.php';
    require_once __DIR__ . '/modelos/SubCategoriaInsumosTest.php';
    require_once __DIR__ . '/modelos/MensajesTest.php';
    
    categoriaTest();
    donacionTest();
    registroDbTest();
    subCategoriaInsumosTest();
    mensajesTest();
}
function run_repositorios_tests(){
    require_once __DIR__ . '/repositorios/UsuariosRepository.php';

    usuariosRepositoryTest();
}

//run_modelos_tests();
run_repositorios_tests();