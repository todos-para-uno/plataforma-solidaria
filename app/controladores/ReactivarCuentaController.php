<?php
require_once __DIR__ . '/../seguridad/Csrf.php';
require_once __DIR__ . '/../servicios/BajaCuenta.php';
require_once __DIR__ . '/../repositorios/BaneoRepository.php';
final class ReactivarCuentaController
{
    public function __construct(private PDO $pdo) {}
    public function atender(string $rutaBase, string $rutaAssets): void
    {
        header('Cache-Control: no-store');
        $id = $_SESSION['reactivar_usuario_id'] ?? null;
        if (!is_int($id)) { header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303); return; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validar($_POST['csrf_token'] ?? null)) { http_response_code(403); return; }
            $aceptar = ($_POST['decision'] ?? '') === 'reactivar';
            unset($_SESSION['reactivar_usuario_id']);
            if ($aceptar && !(new BaneoRepository($this->pdo))->estaBaneado($id) && (new BajaCuenta($this->pdo))->reactivar($id)) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $id;
                $_SESSION['mostrar_bienvenida'] = true;
                Csrf::generar();
                header('Location: ' . $rutaBase . '/index.php', true, 303);
                return;
            }
            header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303);
            return;
        }
        $csrfToken = Csrf::generar();
        require __DIR__ . '/../vistas/reactivar_cuenta.php';
    }
}
