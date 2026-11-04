<?php
require_once __DIR__ . '/../repositorios/ConfianzaRepository.php';
require_once __DIR__ . '/../repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../seguridad/Csrf.php';
require_once __DIR__ . '/../servicios/PerfilUsuario.php';
final class PerfilController
{
    private ImagenPerfil $imagenes;
    public function __construct(private PDO $pdo, ?ImagenPerfil $imagenes = null)
    {
        $this->imagenes = $imagenes ?? new ImagenPerfil();
    }
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
        $this->renderizar($cuenta, $rutaBase, $rutaAssets, $errores, $documentoGuardado,
            $tipoDocumento ?? null, $documento ?? null);
    }

    public function actualizar(?Usuario $cuenta, string $rutaBase = '.', string $rutaAssets = './assets'): void
    {
        if (!$cuenta) { header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303); return; }
        header('Cache-Control: no-store');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); return; }
        $limiteFormulario = ImagenPerfil::tamanoMaximoTotal();
        if ($limiteFormulario > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $limiteFormulario) {
            http_response_code(413); echo 'El formulario supera el tamaño permitido. Elegí una foto más liviana.'; return;
        }
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) { http_response_code(403); echo 'El formulario venció o supera el tamaño permitido. Recargá la página.'; return; }
        try {
            (new PerfilUsuario($this->pdo, $this->imagenes))->guardar($cuenta, $_POST, $_FILES['avatar'] ?? null);
            $_SESSION['perfil_guardado'] = true;
            header('Location: ' . $rutaBase . '/index.php?ruta=perfil', true, 303);
            return;
        } catch (InvalidArgumentException $e) {
            http_response_code(422); $erroresPerfil = [$e->getMessage()];
        } catch (RuntimeException $e) {
            // Incluye fallos de escritura, recodificación y PDO: no exponer rutas ni detalles internos.
            error_log('No se pudo guardar el perfil: ' . $e->getMessage());
            http_response_code(503); $erroresPerfil = ['No pudimos procesar o guardar el perfil. Tu foto anterior se conserva. Intentá nuevamente o guardá sin elegir una foto nueva.'];
        }
        $this->renderizar($cuenta, $rutaBase, $rutaAssets, erroresPerfil: $erroresPerfil, datosPerfil: $_POST);
    }

    public function imagen(?Usuario $cuenta): void
    {
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        if (!$cuenta) { http_response_code(401); return; }
        $cuenta = (new UsuariosRepository($this->pdo))->findById($cuenta->getId());
        $archivo = $this->imagenes->archivo($cuenta?->getAvatar());
        if (!$archivo) { http_response_code(404); return; }
        $datos = @getimagesize($archivo);
        if (!$datos || $datos[0] !== 250 || $datos[1] !== 250 || ($datos['mime'] ?? '') !== 'image/webp') {
            http_response_code(404); return;
        }
        $lectura = @fopen($archivo, 'rb');
        if ($lectura === false) { http_response_code(404); return; }
        header('Content-Type: image/webp');
        header('Content-Disposition: inline; filename="avatar.webp"');
        header('Content-Length: ' . fstat($lectura)['size']);
        try { fpassthru($lectura); }
        finally { fclose($lectura); }
    }

    private function renderizar(Usuario $cuenta, string $rutaBase, string $rutaAssets,
        array $errores = [], bool $documentoGuardado = false, ?string $tipoDocumento = null,
        ?string $documento = null, array $erroresPerfil = [], array $datosPerfil = []): void
    {
        $confianza = (new ConfianzaRepository($this->pdo))->actualizar($cuenta->getId());
        $_SESSION['nivel_confianza'] = $confianza;
        $cuenta = (new UsuariosRepository($this->pdo))->findById($cuenta->getId());
        $usuarioActual = ['nombre' => $cuenta->getNombre(), 'apellido' => $cuenta->getApellido(), 'avatar' => $cuenta->getAvatar(), 'mail' => $cuenta->getEmail(), 'admin' => $cuenta->getAdmin()];
        $usuario = $usuarioActual + ['telefono' => $cuenta->getTelefono(), 'documento' => $cuenta->getDocumento(), 'tipo_documento' => $cuenta->getTipoDocumento()?->value, 'validacion_nombre' => $confianza['nombre']];
        $perfilGuardado = $_SESSION['perfil_guardado'] ?? false;
        unset($_SESSION['perfil_guardado']);
        $csrfToken = Csrf::generar();
        require __DIR__ . '/../vistas/perfil.php';
    }
}
