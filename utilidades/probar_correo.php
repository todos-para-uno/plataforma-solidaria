<?php
/** Herramienta CLI: comprobar no envía; --enviar entrega un único mensaje al destino configurado. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/correo.php';
require_once __DIR__ . '/../app/servicios/CorreoServicio.php';
if (count($argv) !== 2 || !in_array($argv[1], ['--comprobar', '--enviar'], true)) {
    fwrite(STDERR, "Uso: php utilidades/probar_correo.php --comprobar | --enviar\n");
    exit(1);
}
$config = configuracionCorreo();
$correo = new CorreoServicio($config);
try { $correo->verificarConfiguracion(); }
catch (RuntimeException $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }
echo "Configuración válida. SMTP seguro y autenticado; certificado TLS verificado.\n";
if ($argv[1] === '--comprobar') {
    echo "No se contactó al servidor ni se enviaron correos.\n";
    exit;
}
try {
    $enviado = $correo->enviar($config['destino'], 'todospara.uno: prueba de correo',
        "Este es un mensaje de prueba de todospara.uno.\n\nEl envío SMTP se está comprobando desde la herramienta local.\n",
        ['Reply-To' => $config['remitente']]);
    if (!$enviado) throw new RuntimeException('No aceptado');
    echo "El servidor SMTP aceptó el mensaje. Revisá la bandeja de entrada y Spam del destino configurado.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo completar el envío. Revisá host, puerto y credenciales privados; no se muestran para protegerlos.\n");
    exit(1);
}
