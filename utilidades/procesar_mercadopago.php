<?php
// Ejecutar por CLI con cron cada minuto; --watch mantiene un worker de desarrollo.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/mercadopago.php';
require_once __DIR__ . '/../app/servicios/MercadoPagoServicio.php';
$config = configuracionMercadoPago();
$repositorio = new MercadoPagoRepository(conectarBd());
$servicio = new MercadoPagoServicio($repositorio, new MercadoPagoCliente(), $config);
$servicio->verificarConfiguracion();
$watch = in_array('--watch', $argv, true);
do {
    $procesadas = 0; $fallidas = 0;
    // Acotar cada pasada para no monopolizar el servidor.
    for ($i = 0; $i < 100; $i++) {
        $resultado = $repositorio->procesarSiguienteNotificacion(fn ($evento) => $servicio->procesarNotificacion($evento));
        if ($resultado === null) break;
        if ($resultado) $procesadas++; else $fallidas++;
    }
    if ($procesadas || $fallidas || !$watch) echo "Notificaciones: $procesadas procesadas; $fallidas pendientes de reintento.\n";
    if ($watch) sleep(3);
} while ($watch);
