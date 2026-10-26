<?php
function eol(){
$salto = (PHP_SAPI === 'cli') ? PHP_EOL : '<br>';
return $salto;
}
?>
