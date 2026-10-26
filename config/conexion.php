<?php
function conectarBd(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $archivoLocal = __DIR__ . '/conexion.local.php';
        $configuracion = is_file($archivoLocal)
            ? require $archivoLocal
            : [];

        $pdo = new PDO(
            $configuracion['dsn'] ?? 'mysql:host=localhost;dbname=todosparauno;charset=utf8mb4',
            $configuracion['usuario'] ?? 'root',
            $configuracion['password'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}
