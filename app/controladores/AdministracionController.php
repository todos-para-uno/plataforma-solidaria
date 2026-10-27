<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../repositorios/CampRepository.php';
require_once __DIR__ . '/../modelos/Usuarios.php';
require_once __DIR__ . '/../seguridad/Csrf.php';

/*
 * Panel de administración. Solo entra quien tiene usuarios.admin = 1.
 * Recibe el usuario que public_php/index.php leyó de la base en esta misma petición,
 * así el permiso de administrador nunca sale de la sesión ni de la URL.
 */
class AdministracionController
{
    public function mostrar(
        string $rutaBase,
        string $rutaAssets,
        ?Usuario $usuario = null
    ): void {
        // Ocultar el enlace en el menú no alcanza: alguien puede escribir la dirección
        // a mano. Por eso el servidor controla el permiso en cada petición.
        if (!$this->esAdministrador($usuario)) {
            $this->redirigirAlIngreso($rutaBase);
            return;
        }

        $pdo = conectarBd();
        $usuarios = new UsuariosRepository($pdo);
        $campanas = new CampRepository($pdo);

        $estadisticas = [
            'usuarios' => $usuarios->contarRegistrados(),
            'activas' => $campanas->contarPorEstado('activa'),
            'finalizadas' => $campanas->contarPorEstado('finalizada'),
        ];

        $usuarioActual = [
            'nombre' => $usuario->getNombre(),
            'admin' => $usuario->getAdmin(),
        ];
        $csrfToken = Csrf::generar();

        require __DIR__ . '/../vistas/administracion.php';
    }

    /** Sin sesión, o con una cuenta que no es de administrador, no se entra. */
    private function esAdministrador(?Usuario $usuario): bool
    {
        if ($usuario === null) {
            return false;
        }
        return $usuario->getAdmin();
    }

    private function redirigirAlIngreso(string $rutaBase): void
    {
        // rutas.php usa $rutaBase, que ya está definida como parámetro.
        $rutas = require __DIR__ . '/../vistas/parciales/rutas.php';
        // 303, igual que IngresoController: el navegador pide la página nueva con GET.
        header('Location: ' . $rutas['ingresar'], true, 303);
    }
}
