<?php

require_once __DIR__ . '/../seguridad/Csrf.php';
require_once __DIR__ . '/../repositorios/EstadoAutenticacionRepository.php';

class IngresoController
{
    public function __construct(
        private UsuariosRepository $usuarios,
        private BaneoRepository $baneos,
        private ?EstadoAutenticacionRepository $estadoAutenticacion = null
    ) {}

    public function mostrarFormulario(
        string $rutaBase,
        string $rutaAssets
    ): void {
        $errores = [];
        $mail = '';
        $csrfToken = Csrf::generar();

        require __DIR__ . '/../vistas/ingresar.php';
    }

    public function procesarIngreso(
        string $rutaBase,
        string $rutaAssets
    ): void {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo 'La solicitud no es válida. Recargá el formulario e intentá nuevamente.';
            return;
        }

        $csrfToken = Csrf::generar();
        $mail = $_POST['mail'] ?? '';
        $password = $_POST['pass'] ?? '';
        $errores = [];

        if (!is_string($mail) || !is_string($password)) {
            http_response_code(400);
            $mail = is_string($mail) ? $mail : '';
            $errores[] = 'Los datos enviados no son válidos.';
        } else {
            $mail = trim($mail);
            if ($mail === '' || $password === '') {
                http_response_code(422);
                $errores[] = 'Ingresá tu correo electrónico y contraseña.';
            } else {
                $usuario = $this->usuarios->findByMail($mail);
                if ($usuario !== null && $this->estadoAutenticacion?->estaBloqueado($usuario->getId())) {
                    http_response_code(403);
                    $errores[] = 'La cuenta está bloqueada temporalmente. Intentá nuevamente más tarde.';
                } elseif ($usuario === null || !$usuario->verificarPassword($password)) {
                    if ($usuario !== null) {
                        $this->estadoAutenticacion?->registrarFallo($usuario->getId());
                    }
                    http_response_code(422);
                    $errores[] = 'El correo o la contraseña son incorrectos.';
                } elseif ($this->baneos->estaBaneado($usuario->getId())) {
                    http_response_code(403);
                    $errores[] = 'Tu cuenta se encuentra baneada.';
                } else {
                    $this->estadoAutenticacion?->limpiar($usuario->getId());
                    session_regenerate_id(true);
                    // Conservar solo la identidad; los datos se consultan en cada petición.
                    $_SESSION = ['usuario_id' => $usuario->getId()];
                    Csrf::generar();
                    header('Location: ' . $rutaBase . '/index.php', true, 303);
                    return;
                }
            }
        }

        require __DIR__ . '/../vistas/ingresar.php';
    }

    public function cerrarSesion(string $rutaBase): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo 'La solicitud no es válida. Recargá la página e intentá nuevamente.';
            return;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $parametros['path'],
                'domain' => $parametros['domain'],
                'secure' => $parametros['secure'],
                'httponly' => $parametros['httponly'],
                'samesite' => $parametros['samesite'],
            ]);
        }
        session_destroy();
        header('Location: ' . $rutaBase . '/index.php', true, 303);
    }
}
