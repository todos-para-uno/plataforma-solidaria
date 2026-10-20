<?php
function run_tests(){
    require_once __DIR__ . '/modelos/CategoriaTest.php';
    require_once __DIR__ . '/modelos/DonacionTest.php';
    require_once __DIR__ . '/modelos/RegistroDbTest.php';
    require_once __DIR__ . '/modelos/SubCategoriaInsumosTest.php';
    require_once __DIR__ . '/modelos/MensajesTest.php';
    require_once __DIR__ . '/modelos/ValidacionesTest.php';
    
    categoriaTest();
    donacionTest();
    registroDbTest();
    subCategoriaInsumosTest();
    mensajesTest();
    validacionesTest();
}
run_tests();
