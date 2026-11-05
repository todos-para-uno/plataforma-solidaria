<?php
require_once __DIR__ . '/../seguridad/Csrf.php';
require_once __DIR__ . '/../servicios/BajaCuenta.php';
final class BajaCuentaController
{
    public function __construct(private PDO $pdo) {}

    public function procesar(?Usuario $usuario, string $rutaBase): void
    {
        if (!$usuario) { http_response_code(401); return; }
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) { http_response_code(403); echo 'Formulario inválido.'; return; }
        $clave = $_POST['pass'] ?? null;
        if (!is_string($clave) || !$usuario->verificarPassword($clave)) {
            http_response_code(422); echo 'Contraseña incorrecta. Volvé a tu perfil e intentá nuevamente.'; return;
        }
        $eliminar = ($_POST['eliminar_datos'] ?? null) === '1';

        if ($eliminar && ($_POST['confirmar_eliminacion'] ?? null) !== '1') {
            http_response_code(422);
            echo 'Debés confirmar expresamente que comprendés que la eliminación de tus datos es irreversible.';
            return;
        }

        try {
            (new BajaCuenta($this->pdo))->desactivar($usuario->getId(), $eliminar);
        }
        catch (Throwable $e) { error_log('Baja de cuenta: ' . $e->getMessage()); http_response_code(503); echo 'No se pudo procesar la baja. Intentá nuevamente.'; return; }
        $_SESSION = [];
        session_regenerate_id(true);
        Csrf::generar();
        header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303);
    }
}
