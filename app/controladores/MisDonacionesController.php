<?php
require_once __DIR__ . '/../repositorios/DonacionRepository.php';
require_once __DIR__ . '/../modelos/Usuarios.php';
require_once __DIR__ . '/../seguridad/Csrf.php';
final class MisDonacionesController
{
    public function __construct(private DonacionRepository $donaciones) {}

    public function mostrar(?Usuario $usuario, string $rutaBase = '.', string $rutaAssets = './assets'): void
    {
        if ($usuario === null) {
            header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303);
            return;
        }
        header('Cache-Control: no-store');
        $usuarioActual = ['nombre' => $usuario->getNombre(), 'admin' => $usuario->getAdmin()];
        $csrfToken = Csrf::generar();
        $error = null;
        $donaciones = [];
        try {
            $donaciones = $this->donaciones->historialPorUsuario($usuario->getId());
            foreach ($donaciones as &$donacion) {
                // Solo imágenes locales dentro de assets/imagenes; sin rutas externas ni ascendentes.
                $imagen = $donacion['imagen'] ?? '';
                if (!preg_match('~^[A-Za-z0-9_/-][A-Za-z0-9_./-]*\.(?:webp|png|jpe?g)$~iD', $imagen)
                    || str_contains($imagen, '..') || str_starts_with($imagen, '/')
                    || !is_file(__DIR__ . '/../../public_php/assets/imagenes/' . $imagen)) {
                    $donacion['imagen'] = null;
                }
            }
            unset($donacion);
        } catch (PDOException $e) {
            http_response_code(503);
            $error = 'No pudimos consultar tus donaciones. Intentá nuevamente.';
        }
        require __DIR__ . '/../vistas/mis_donaciones.php';
    }
}
