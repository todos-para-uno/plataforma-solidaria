<?php
// Copiar a mercadopago.local.php. Nunca subir credenciales ni la clave de cifrado.
return [
    'habilitado' => false,
    'client_id' => '',
    'client_secret' => '',
    // URL HTTPS que sirve public_php, SIN barra final ni /index.php.
    'url_publica' => '',
    // Generar: php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'
    'clave_cifrado' => '',
    'webhook_secret' => '',
    'produccion' => false,
];
