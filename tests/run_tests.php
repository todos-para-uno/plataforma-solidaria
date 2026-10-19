<?php
function run_tests(){
    require_once __DIR__ . '/modelos/test_modelo_registro_db.php';
    require_once __DIR__ . '/modelos/test_modelo_categoria.php';
    
    test_modelo_registro_db();
    test_modelo_categoria();
}

run_tests()
?>

