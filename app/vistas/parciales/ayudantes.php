<?php
// Funciones de presentación que podemos reutilizar en las vistas.
// cabecera.php las carga con require_once para evitar definirlas más de una vez.

/**
 * Prepara texto para mostrarlo en HTML: por ejemplo, '<' se convierte en '&lt;'.
 * Usala al mostrar datos de formularios o de la base, también en atributos entre comillas.
 * Para insertar datos en JavaScript o construir URLs necesitamos el tratamiento de ese contexto.
 */
function escapar(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Muestra un monto en pesos sin decimales. Ejemplo: 320000 se muestra como "$320.000". */
function formatearPesos(float $monto): string
{
    return '$' . number_format($monto, 0, ',', '.');
}

/**
 * Calcula el porcentaje entero para la barra de progreso y lo limita a 100.
 * Si el objetivo es cero o negativo, devuelve 0. Espera un monto logrado no negativo.
 */
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

/** Devuelve aria-current="page" para el enlace activo, o una cadena vacía para los demás. */
function marcarActual(string $clave, ?string $paginaActual): string
{
    if ($clave === $paginaActual) {
        return ' aria-current="page"';
    }
    return '';
}
