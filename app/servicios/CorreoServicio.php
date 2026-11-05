<?php
require_once __DIR__ . '/../../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/** SMTP autenticado. El destinatario y el remitente provienen de configuración privada. */
final class CorreoServicio
{
    public function __construct(private array $config, private ?Closure $crearMailer = null) {}

    public function verificarConfiguracion(): void
    {
        if (($this->config['habilitado'] ?? false) !== true) {
            throw new RuntimeException('El envío de correo está deshabilitado.');
        }
        if (!extension_loaded('openssl')) throw new RuntimeException('PHP necesita la extensión OpenSSL para SMTP seguro.');
        $host = $this->config['host'] ?? null;
        if (!is_string($host) || $host === '' || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new RuntimeException('Completá el host SMTP con el servidor indicado por DonWeb.');
        }
        $puerto = $this->config['puerto'] ?? null;
        if (!is_int($puerto) || $puerto < 1 || $puerto > 65535) throw new RuntimeException('El puerto SMTP no es válido.');
        if (!in_array($this->config['seguridad'] ?? null, ['smtps', 'starttls'], true)) {
            throw new RuntimeException('La seguridad SMTP debe ser smtps o starttls.');
        }
        foreach (['usuario', 'password'] as $campo) {
            if (!is_string($this->config[$campo] ?? null) || $this->config[$campo] === '') {
                throw new RuntimeException('Completá el usuario y la contraseña SMTP en la configuración privada.');
            }
        }
        foreach (['remitente', 'destino'] as $campo) {
            if (!is_string($this->config[$campo] ?? null) || strlen($this->config[$campo]) > 254
                || !filter_var($this->config[$campo], FILTER_VALIDATE_EMAIL)
                || preg_match('/[\r\n]/', $this->config[$campo])) {
                throw new RuntimeException('Completá un remitente y un destino de correo válidos.');
            }
        }
        $nombre = $this->config['nombre_remitente'] ?? null;
        if (!is_string($nombre) || preg_match('/[\r\n]/', $nombre) || mb_strlen($nombre) > 100) {
            throw new RuntimeException('El nombre del remitente no es válido.');
        }
    }

    public function enviar(string $destino, string $asunto, string $mensaje, array $cabeceras): bool
    {
        if (($this->config['habilitado'] ?? false) !== true) return false;
        $this->verificarConfiguracion();
        // Evita convertir el formulario en un mecanismo de envío a direcciones arbitrarias.
        if ($destino !== $this->config['destino'] || preg_match('/[\r\n]/', $asunto)) {
            throw new InvalidArgumentException('El destino o el asunto del aviso no son válidos.');
        }
        $replyTo = $cabeceras['Reply-To'] ?? null;
        if (!is_string($replyTo) || strlen($replyTo) > 254 || !filter_var($replyTo, FILTER_VALIDATE_EMAIL)
            || preg_match('/[\r\n]/', $replyTo)) {
            throw new InvalidArgumentException('La dirección de respuesta no es válida.');
        }
        $mail = $this->crearMailer !== null ? ($this->crearMailer)() : new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->Port = $this->config['puerto'];
        $mail->SMTPAuth = true;
        $mail->Username = $this->config['usuario'];
        $mail->Password = $this->config['password'];
        $mail->SMTPSecure = $this->config['seguridad'] === 'smtps'
            ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPDebug = 0;
        $mail->SMTPOptions = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]];
        $mail->Timeout = 8;
        $mail->getSMTPInstance()->Timelimit = 15;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;
        $mail->XMailer = '';
        $mail->setFrom($this->config['remitente'], $this->config['nombre_remitente']);
        $mail->addAddress($this->config['destino']);
        $mail->addReplyTo($replyTo);
        $mail->isHTML(false);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        return $mail->send();
    }
}
