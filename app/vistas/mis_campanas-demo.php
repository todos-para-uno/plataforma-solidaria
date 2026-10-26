<?php
/*
 * Vista previa de "Mis campañas" con datos ficticios, sin base de datos ni sesión.
 * Reemplaza al controlador: arma las mismas variables y carga la vista real.
 *
 * En XAMPP:
 *   .../app/vistas/mis_campanas-demo.php          con campañas de ejemplo
 *   .../app/vistas/mis_campanas-demo.php?vacia=1  sin campañas
 *
 * Es solo para probar el diseño: la aplicación usa MisCampanasController.
 */

// Simula al usuario que inició sesión, para que la cabecera muestre su menú.
$usuarioActual = ['nombre' => 'Maximiliano', 'admin' => false, 'mensajesSinLeer' => 2];

// Mismas columnas que devuelve CampRepository::buscarPorCreador().
$campanas = [
    ['id' => 3, 'nombre' => 'Una mesa para compartir', 'monetaria' => 1, 'logrado' => 150000,
     'estado' => 'activa', 'nombre_categoria' => 'Alimentos', 'objetivo' => 250000, 'parcial' => 0],
    ['id' => 2, 'nombre' => 'Mochilas llenas de futuro', 'monetaria' => 0, 'logrado' => 0,
     'estado' => 'finalizada', 'nombre_categoria' => 'Educación', 'objetivo' => 60, 'parcial' => 30],
    ['id' => 1, 'nombre' => 'Abriguemos juntos', 'monetaria' => 1, 'logrado' => 0,
     'estado' => 'suspendida', 'nombre_categoria' => 'Comunidad', 'objetivo' => 0, 'parcial' => 0],
];

// Con ?vacia=1 se ve el mensaje para quien todavía no creó campañas.
if (isset($_GET['vacia'])) {
    $campanas = [];
}

// El controlador calcula estos números; acá los escribimos según los datos de arriba.
$resumen = ['creadas' => 3, 'activas' => 1, 'finalizadas' => 1, 'suspendidas' => 1, 'dinero' => 150000];
if (empty($campanas)) {
    $resumen = ['creadas' => 0, 'activas' => 0, 'finalizadas' => 0, 'suspendidas' => 0, 'dinero' => 0];
}

require __DIR__ . '/mis_campanas.php';
