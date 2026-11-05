<?php

/** Política de la aplicación; los recursos estáticos también deben cubrirse en el hosting. */
final class CabecerasSeguridad
{
    public static function enviar(): void
    {
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // Leaflet requiere estilos dinámicos y las vistas actuales usan atributos style.
        // No se permiten scripts inline ni eval; los únicos scripts externos son los de Leaflet.
        header('Content-Security-Policy: ' . implode('; ', [
            "default-src 'self'",
            "script-src 'self' https://unpkg.com/leaflet@1.9.4/dist/leaflet.js",
            "style-src 'self' 'unsafe-inline' https://unpkg.com/leaflet@1.9.4/dist/leaflet.css",
            "img-src 'self' data: blob: https://tile.openstreetmap.org https://unpkg.com/leaflet@1.9.4/dist/images/",
            "font-src 'self'",
            "connect-src 'self' https://photon.komoot.io",
            "frame-src https://www.openstreetmap.org",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));
    }
}
