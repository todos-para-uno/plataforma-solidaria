<?php
require_once __DIR__ . '/Csrf.php';

/** Vencimiento impuesto por el servidor, independientemente de JavaScript y del recolector de PHP. */
final class SesionInactiva
{
    public const LIMITE_SEGUNDOS = 600;

    /** Devuelve true si revocó una identidad por inactividad. El reloj opcional permite probar el límite sin esperar. */
    public static function comprobar(?int $ahora = null): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) throw new RuntimeException('La sesión no está iniciada.');
        if (!isset($_SESSION['usuario_id'])) return false;

        $ahora ??= time();
        $ultima = $_SESSION['ultima_actividad'] ?? null;
        // Las sesiones abiertas antes de este cambio comienzan su ventana al volver a navegar.
        if ($ultima === null) {
            $_SESSION['ultima_actividad'] = $ahora;
            return false;
        }
        if (!is_int($ultima) || $ultima > $ahora || $ahora - $ultima >= self::LIMITE_SEGUNDOS) {
            $_SESSION = [];
            session_regenerate_id(true);
            Csrf::generar();
            $_SESSION['sesion_vencida'] = true;
            return true;
        }
        $_SESSION['ultima_actividad'] = $ahora;
        return false;
    }
}
