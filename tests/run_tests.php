<?php
function run_tests(){
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
run_tests();
