<?php
require_once __DIR__ . '/../../app/servicios/ImagenPerfil.php';
$servicio = new ImagenPerfil($argv[2], static fn(string $ruta): bool => is_file($ruta));
$resultado = $servicio->validar(['name' => 'foto.jpg', 'tmp_name' => $argv[1], 'error' => 0, 'size' => filesize($argv[1])]);
try { $servicio->guardar($resultado['archivos']); exit(1); }
catch (RuntimeException) { exit(glob($argv[2] . '/*') === [] ? 0 : 2); }
