<?php
require_once __DIR__ . '/../repositorios/ContactoRepository.php';
require_once __DIR__ . '/../modelos/Usuarios.php';
require_once __DIR__ . '/../seguridad/Csrf.php';

class ContactoController
{
    private Closure $enviarCorreo;

    public function __construct(
        private ContactoRepository $contactos,
        ?Closure $enviarCorreo = null
    ) {
        // La función se puede sustituir en las pruebas para no enviar correos reales.
        $this->enviarCorreo = $enviarCorreo ?? static fn ($destino, $asunto, $mensaje, $cabeceras): bool =>
            @mail($destino, $asunto, $mensaje, $cabeceras);
    }

    public function mostrarFormulario(string $rutaBase, string $rutaAssets, ?Usuario $usuario = null): void
    {
        $csrfToken = Csrf::generar();
        $nombre = $usuario?->getNombre() ?? '';
        $mail = $usuario?->getEmail() ?? '';
        $enviado = !empty($_SESSION['contacto_enviado']);
        unset($_SESSION['contacto_enviado']);
        $usuarioActual = $usuario === null ? null : ['nombre' => $usuario->getNombre(), 'admin' => $usuario->getAdmin()];

        require __DIR__ . '/../vistas/contacto.php';
    }

    public function procesarContacto(string $rutaBase, string $rutaAssets, ?Usuario $usuario = null): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo 'La solicitud no es válida. Recargá el formulario e intentá nuevamente.';
            return;
        }

        $csrfToken = Csrf::generar();
        $enviado = false;
        $errores = [];
        $usuarioActual = $usuario === null ? null : ['nombre' => $usuario->getNombre(), 'admin' => $usuario->getAdmin()];
        $datos = [];
        foreach (['nombre', 'mail', 'asunto', 'contenido'] as $campo) {
            $valor = $_POST[$campo] ?? '';
            if (!is_string($valor)) {
                $errores[] = 'Los datos enviados no son válidos.';
            }
            $datos[$campo] = is_string($valor) ? trim($valor) : '';
        }
        $nombre = $datos['nombre'];
        $mail = $datos['mail'];
        $asunto = $datos['asunto'];
        $contenido = $datos['contenido'];
        if ($errores !== []) {
            http_response_code(400);
            require __DIR__ . '/../vistas/contacto.php';
            return;
        }

        try {
            // La asociación al usuario sale de la sesión, no del formulario.
            $contacto = new Contacto($nombre, $mail, $asunto, $contenido, $usuario?->getId());
        } catch (InvalidArgumentException $excepcion) {
            http_response_code(422);
            $errores[] = $excepcion->getMessage();
            require __DIR__ . '/../vistas/contacto.php';
            return;
        }

        try {
            $id = $this->contactos->insertarContacto($contacto);
        } catch (PDOException $excepcion) {
            error_log('Contacto: no se pudo guardar la consulta.');
            http_response_code(500);
            $errores[] = 'No pudimos recibir tu consulta. Intentá nuevamente más tarde.';
            require __DIR__ . '/../vistas/contacto.php';
            return;
        }

        // Guardar primero: una falla del correo no debe perder la consulta.
        $cuerpo = "Nombre: $nombre\nCorreo: $mail\n\n$contenido";
        $cabeceras = [
            'From' => 'no-responder@todosparauno.test',
            'Reply-To' => $mail,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
        ];
        try {
            $enviadoPorCorreo = ($this->enviarCorreo)(
                'contacto@todosparauno.test',
                mb_encode_mimeheader('Contacto: ' . Contacto::ASUNTOS[$asunto], 'UTF-8'),
                $cuerpo,
                $cabeceras
            );
            if ($enviadoPorCorreo) {
                $this->contactos->save(['id' => $id, 'correo_enviado' => 1]);
            } else {
                error_log('Contacto: consulta guardada; el transporte de correo no aceptó el envío.');
            }
        } catch (Throwable $excepcion) {
            error_log('Contacto: consulta guardada; no se pudo completar el aviso por correo.');
        }

        $_SESSION['contacto_enviado'] = true;
        $rutas = require __DIR__ . '/../vistas/parciales/rutas.php';
        header('Location: ' . $rutas['contacto'], true, 303);
    }
}
