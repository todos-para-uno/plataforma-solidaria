<?php
// Funciones chicas que usan todas las vistas.
// Se carga una sola vez desde cabecera.php (con require_once).

/** Escapa cualquier texto antes de mostrarlo en HTML. Usala SIEMPRE con datos del usuario o de la base. */
function escapar(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** 320000 -> "$320.000" (formato argentino, sin decimales). */
function formatearPesos(float $monto): string
{
    return '$' . number_format($monto, 0, ',', '.');
}

/** Porcentaje entero entre 0 y 100 para la barra de progreso. */
function porcentajeProgreso(float $logrado, float $objetivo): int
{
    if ($objetivo <= 0) {
        return 0;
    }
    $porcentaje = (int) round($logrado / $objetivo * 100);
    if ($porcentaje > 100) {
        $porcentaje = 100;
    }
    return $porcentaje;
}

/** Devuelve el atributo que marca el enlace de la página actual en el menú, o nada. */
function marcarActual(string $clave, ?string $paginaActual): string
{
    if ($clave === $paginaActual) {
        return ' aria-current="page"';
    }
    return '';
}
