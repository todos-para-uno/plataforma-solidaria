<?php
require_once __DIR__ . '/../repositorios/ConfianzaRepository.php';
require_once __DIR__ . '/../repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../seguridad/Csrf.php';
final class PerfilController
{
    public function __construct(private PDO $pdo) {}
    public function mostrar(?Usuario $cuenta, string $rutaBase = '.', string $rutaAssets = './assets'): void
    {
        if (!$cuenta) { header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303); return; }
        header('Cache-Control: no-store');
        $errores = []; $documentoGuardado = $_SESSION['documento_guardado'] ?? false; unset($_SESSION['documento_guardado']);
        $repo = new ConfianzaRepository($this->pdo);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validar($_POST['csrf_token'] ?? null)) { http_response_code(403); echo 'El formulario venció. Recargá la página.'; return; }
            $tipoDocumento = is_string($_POST['tipo_documento'] ?? null) ? $_POST['tipo_documento'] : '';
            $documento = is_string($_POST['documento'] ?? null) ? $_POST['documento'] : '';
            try {
                $repo->guardarDocumento($cuenta->getId(), $_POST['tipo_documento'] ?? null, $_POST['documento'] ?? null);
                $_SESSION['documento_guardado'] = true;
                header('Location: ' . $rutaBase . '/index.php?ruta=perfil', true, 303); return;
            } catch (InvalidArgumentException $e) { http_response_code(422); $errores[] = $e->getMessage(); }
            catch (PDOException $e) { http_response_code(503); $errores[] = 'No pudimos guardar el documento. Intentá nuevamente.'; }
        }
        $confianza = $repo->actualizar($cuenta->getId());
        $_SESSION['nivel_confianza'] = $confianza;
        $cuenta = (new UsuariosRepository($this->pdo))->findById($cuenta->getId());
        $usuarioActual = ['nombre' => $cuenta->getNombre(), 'mail' => $cuenta->getEmail(), 'admin' => $cuenta->getAdmin()];
        $usuario = $usuarioActual + ['telefono' => $cuenta->getTelefono(), 'documento' => $cuenta->getDocumento(), 'tipo_documento' => $cuenta->getTipoDocumento()?->value, 'validacion_nombre' => $confianza['nombre']];
        $csrfToken = Csrf::generar();
        require __DIR__ . '/../vistas/perfil.php';
    }
}
