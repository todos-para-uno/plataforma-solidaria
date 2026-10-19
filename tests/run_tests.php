<?php
function run_tests(){
    require_once __DIR__ . '/modelos/CategoriaTest.php';
    require_once __DIR__ . '/modelos/DonacionTest.php';
    require_once __DIR__ . '/modelos/RegistroDbTest.php';
    require_once __DIR__ . '/modelos/SubCategoriaInsumosTest.php';
    
    categoriaTest();
    donacionTest();
    registroDbTest();
    subCategoriaInsumosTest();
}
run_tests();
