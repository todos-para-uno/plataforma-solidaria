<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../repositorios/CampRepository.php';

require_once __DIR__ . '/../modelos/Usuarios.php';
require_once __DIR__ . '/../seguridad/Csrf.php';

// Recibe el usuario recuperado desde la base por el punto de entrada.
class MisCampanasController
{
    public function mostrar(
        string $rutaBase,
        string $rutaAssets,
        ?Usuario $usuario = null
    ): void
    {
        if ($usuario === null || !$usuario->tieneId()) {
            $this->redirigirAlIngreso($rutaBase);
            return;
        }

        // El ID pertenece al usuario recuperado desde la sesión, no viene de la URL.
        $idUsuario = $usuario->getId();

        $repositorio = new CampRepository(conectarBd());
        $campanas = $repositorio->buscarPorCreador($idUsuario);
        $resumen = $this->calcularResumen($campanas);
        $usuarioActual = [
            'nombre' => $usuario->getNombre(),
            'admin' => $usuario->getAdmin(),
        ];
        $csrfToken = Csrf::generar();

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
}
