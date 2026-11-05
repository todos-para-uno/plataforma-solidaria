
<?php

function conectarBdPruebas(): PDO
{
    $configuracion = require __DIR__ . '/../config/conexion.local.php';

    $dsn = $configuracion['dsn'];

    // Sustituimos exclusivamente el nombre de la base.
    $dsnPruebas = preg_replace(
        '/(?<=dbname=)[^;]+/',
        'todosparauno_test',
        $dsn,
        1,
        $reemplazos
    );

    if ($reemplazos !== 1 || $dsnPruebas === null) {
        throw new RuntimeException(
            'No se pudo identificar la base de datos en el DSN.'
        );
    }

    $pdo = new PDO(
        $dsnPruebas,
        $configuracion['usuario'],
        $configuracion['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Protección contra operaciones destructivas.
    $baseActual = $pdo->query('SELECT DATABASE()')->fetchColumn();

    if ($baseActual !== 'todosparauno_test') {
        throw new RuntimeException(
            'La conexión no apunta a la base exclusiva de pruebas.'
        );
    }

    return $pdo;
}
