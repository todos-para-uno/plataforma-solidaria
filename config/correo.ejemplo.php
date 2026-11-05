<?php
// Copiar a correo.local.php. No poner credenciales en este archivo versionado.
return [
    'habilitado' => false, // Cambiar a true después de completar host y contraseña.
    'host' => '', // Servidor SMTP exacto que muestra DonWeb, por ejemplo usuario.ferozo.com.
    'puerto' => 465,
    'seguridad' => 'smtps', // TLS implícito. Para 587, usar 'starttls'.
    'usuario' => 'grupo@todospara.uno',
    'password' => '', // Contraseña de la casilla; nunca pegarla en el chat.
    'remitente' => 'grupo@todospara.uno',
    'nombre_remitente' => 'todospara.uno',
    'destino' => 'grupo@todospara.uno',
];
