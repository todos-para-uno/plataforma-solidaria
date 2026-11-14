<?php
require_once __DIR__ . '/../../app/servicios/CorreoServicio.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

/** Genera el mensaje MIME real, sustituyendo solo la entrega final para no contactar a terceros. */
final class CorreoSinEntrega extends PHPMailer\PHPMailer\PHPMailer
{
    public bool $aceptar = true;
    public function postSend() { return $this->aceptar; }
}

function correoServicioTest(): void
{
    $config = [
        'habilitado' => true, 'host' => 'smtp.example.test', 'puerto' => 465, 'seguridad' => 'smtps',
        'usuario' => 'avisos@example.test', 'password' => 'clave-solo-pruebas',
        'remitente' => 'avisos@example.test', 'nombre_remitente' => 'todospara.uno', 'destino' => 'equipo@example.test',
    ];
    $mail = new CorreoSinEntrega(true);
    $servicio = new CorreoServicio($config, static fn () => $mail);
    TestUtils::comprobar($servicio->enviar('equipo@example.test', 'Contacto: Problema con una donación',
        "Nombre: Lucía\n\nNecesito información.\n.texto\n<script>sin HTML</script>", ['Reply-To' => 'visitante@example.test']),
        'SMTP acepta un aviso UTF-8 con el remitente y destino configurados.');
    TestUtils::comprobar($mail->Mailer === 'smtp' && $mail->Host === 'smtp.example.test' && $mail->Port === 465
        && $mail->SMTPAuth && $mail->SMTPSecure === 'ssl', 'Configura SMTP autenticado con TLS implícito en puerto 465.');
    TestUtils::comprobar($mail->Username === 'avisos@example.test' && $mail->Password === 'clave-solo-pruebas',
        'Las credenciales se toman de la configuración del servidor.');
    TestUtils::comprobar($mail->From === 'avisos@example.test' && $mail->Sender === 'avisos@example.test'
        && $mail->getToAddresses() === [['equipo@example.test', '']]
        && $mail->getReplyToAddresses() === [['visitante@example.test', '']],
        'From y envelope sender pertenecen al sitio; Reply-To pertenece al visitante.');
    $mime = $mail->getSentMIMEMessage();
    TestUtils::comprobar(str_contains(strtolower($mime), 'content-type: text/plain; charset=utf-8')
        && str_contains($mime, 'Content-Transfer-Encoding: quoted-printable')
        && str_contains(quoted_printable_decode($mime), 'Lucía')
        && str_contains(strtolower($mime), 'subject: =?utf-8?')
        && !str_contains($mime, 'clave-solo-pruebas'),
        'El MIME conserva tildes, codifica el asunto y el texto, y no incluye la contraseña.');
    TestUtils::comprobar($mail->SMTPDebug === 0 && $mail->SMTPOptions['ssl'] === [
        'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false,
    ], 'Verifica el certificado TLS y mantiene desactivado el diagnóstico SMTP con credenciales.');

    $starttls = new CorreoSinEntrega(true);
    (new CorreoServicio(array_replace($config, ['seguridad' => 'starttls', 'puerto' => 587]), static fn () => $starttls))
        ->enviar('equipo@example.test', 'Prueba', 'Mensaje', ['Reply-To' => 'visitante@example.test']);
    TestUtils::comprobar($starttls->SMTPSecure === 'tls' && $starttls->Port === 587, 'También admite STARTTLS explícito.');

    $sinEnvio = new CorreoServicio(array_replace($config, ['habilitado' => false]), static function () {
        throw new RuntimeException('No debe construir ni contactar SMTP.');
    });
    TestUtils::comprobar(!$sinEnvio->enviar('equipo@example.test', 'Prueba', 'Mensaje', ['Reply-To' => 'visitante@example.test']),
        'Con correo deshabilitado no inicia conexiones ni construye el transporte.');
    $rechazado = new CorreoSinEntrega(true); $rechazado->aceptar = false;
    TestUtils::comprobar(!(new CorreoServicio($config, static fn () => $rechazado))
        ->enviar('equipo@example.test', 'Prueba', 'Mensaje', ['Reply-To' => 'visitante@example.test']),
        'No informa éxito cuando el transporte rechaza la entrega.');

    foreach ([['host' => ''], ['host' => "smtp.example.test\r\n"], ['puerto' => 0], ['puerto' => 65536],
        ['seguridad' => 'none'], ['usuario' => ''], ['password' => ''], ['remitente' => 'inválido'],
        ['destino' => "equipo@example.test\r\nBcc: otro@example.test"], ['nombre_remitente' => "Nombre\nBcc:otro"]] as $cambio) {
        $rechazo = false;
        try { (new CorreoServicio(array_replace($config, $cambio)))->verificarConfiguracion(); }
        catch (RuntimeException $e) { $rechazo = !str_contains($e->getMessage(), 'clave-solo-pruebas'); }
        TestUtils::comprobar($rechazo, 'Rechaza configuración SMTP inválida sin revelar secretos: ' . array_key_first($cambio) . '.');
    }
    foreach ([['otro@example.test', 'Prueba', 'visitante@example.test'],
        ['equipo@example.test', "Asunto\r\nBcc: otro@example.test", 'visitante@example.test'],
        ['equipo@example.test', 'Prueba', "visitante@example.test\r\nBcc: otro@example.test"]] as [$destino, $asunto, $replyTo]) {
        $rechazo = false;
        try { $servicio->enviar($destino, $asunto, 'Mensaje', ['Reply-To' => $replyTo]); }
        catch (InvalidArgumentException $e) { $rechazo = true; }
        TestUtils::comprobar($rechazo, 'Rechaza destinatarios arbitrarios e inyecciones en cabeceras antes del envío.');
    }
}
