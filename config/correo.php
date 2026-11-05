<?php
/** Credenciales privadas fuera de public_php; también admite variables de entorno. */
function configuracionCorreo(): array
{
    $local = is_file(__DIR__ . '/correo.local.php') ? require __DIR__ . '/correo.local.php' : [];
    return array_replace([
        'habilitado' => filter_var(getenv('CORREO_HABILITADO') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'host' => getenv('CORREO_SMTP_HOST') ?: '',
        'puerto' => (int) (getenv('CORREO_SMTP_PUERTO') ?: 465),
        'seguridad' => getenv('CORREO_SMTP_SEGURIDAD') ?: 'smtps',
        'usuario' => getenv('CORREO_SMTP_USUARIO') ?: 'grupo@todospara.uno',
        'password' => getenv('CORREO_SMTP_PASSWORD') ?: '',
        'remitente' => getenv('CORREO_REMITENTE') ?: 'grupo@todospara.uno',
        'nombre_remitente' => 'todospara.uno',
        'destino' => getenv('CORREO_DESTINO') ?: 'grupo@todospara.uno',
    ], $local);
}
