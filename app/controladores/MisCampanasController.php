<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../repositorios/CampRepository.php';

/*
 * Pantalla "Mis campañas": lista las campañas que creó el usuario que inició sesión.
 *
 * Supone que, al ingresar, el inicio de sesión guarda en $_SESSION:
 *   'id_usuario' (int), 'nombre' (string) y 'admin' (bool).
 * Si el equipo elige otros nombres para esas claves, se cambian solo acá.
 */
class MisCampanasController
{
    public function mostrar(string $rutaBase, string $rutaAssets): void
    {
        session_start();

        if (!isset($_SESSION['id_usuario'])) {
            $this->redirigirAlIngreso($rutaBase);
            return;
        }

        // El id sale de la sesión y no de la URL: así nadie puede ver
        // las campañas de otra persona cambiando un número en la dirección.
        $idUsuario = (int) $_SESSION['id_usuario'];

        $repositorio = new CampRepository(conectarBd());
        $campanas = $repositorio->buscarPorCreador($idUsuario);
        $resumen = $this->calcularResumen($campanas);
        $usuarioActual = $this->armarUsuarioActual();

        require __DIR__ . '/../vistas/mis_campanas.php';
    }

    private function redirigirAlIngreso(string $rutaBase): void
    {
        // rutas.php usa $rutaBase, que ya está definida como parámetro.
        $rutas = require __DIR__ . '/../vistas/parciales/rutas.php';
        header('Location: ' . $rutas['ingresar']);
    }

    /** Cuenta las campañas por estado y suma el dinero recaudado. */
    private function calcularResumen(array $campanas): array
    {
        $resumen = [
            'creadas' => count($campanas),
            'activas' => 0,
            'finalizadas' => 0,
            'suspendidas' => 0,
            'dinero' => 0.0,
        ];

        foreach ($campanas as $campana) {
            if ($campana['estado'] === 'activa') {
                $resumen['activas']++;
            } elseif ($campana['estado'] === 'finalizada') {
                $resumen['finalizadas']++;
            } elseif ($campana['estado'] === 'suspendida') {
                $resumen['suspendidas']++;
            }

            // Solo se suma el dinero: no tiene sentido sumar pesos con cantidades de insumos.
            if ($campana['monetaria']) {
                $resumen['dinero'] = $resumen['dinero'] + (float) $campana['logrado'];
            }
        }

        return $resumen;
    }

    /** Arma el arreglo que cabecera.php necesita para mostrar el menú de usuario. */
    private function armarUsuarioActual(): array
    {
        return [
            'nombre' => $_SESSION['nombre'] ?? '',
            'admin' => !empty($_SESSION['admin']),
            'mensajesSinLeer' => 0, // Se completa cuando exista la pantalla de mensajes.
        ];
    }
}
