<?php
/** Configuración privada opcional, fuera del directorio público y excluida de Git. */
function configuracionMercadoPago(): array
{
    $local = is_file(__DIR__ . '/mercadopago.local.php') ? require __DIR__ . '/mercadopago.local.php' : [];
    return array_replace([
        'habilitado' => filter_var(getenv('MP_HABILITADO') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'client_id' => getenv('MP_CLIENT_ID') ?: '',
        'client_secret' => getenv('MP_CLIENT_SECRET') ?: '',
        'url_publica' => rtrim(getenv('MP_URL_PUBLICA') ?: '', '/'),
        'clave_cifrado' => getenv('MP_CLAVE_CIFRADO') ?: '',
        'webhook_secret' => getenv('MP_WEBHOOK_SECRET') ?: '',
        'produccion' => filter_var(getenv('MP_PRODUCCION') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    ], $local);
}
