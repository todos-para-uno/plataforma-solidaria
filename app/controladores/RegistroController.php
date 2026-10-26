<?php
require_once __DIR__ . '/../modelos/NivelValidacion.php';
require_once __DIR__ . '/../seguridad/Csrf.php';

class RegistroController
{
    public function __construct(
        private UsuariosRepository $usuarios,
        private ValidacionesRepository $validaciones
    ) {}

    public function mostrarFormulario(
        string $rutaBase,
        string $rutaAssets
    ): void {
        $csrfToken = Csrf::generar();

        require __DIR__ . '/../vistas/registro.php';
    }

    public function procesarRegistro(
        string $rutaBase,
        string $rutaAssets
    ): void {

        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo 'La solicitud no es válida. Recargá el formulario e intentá nuevamente.';
            return;
        }

        $csrfToken = Csrf::generar();
        $errores = [];

        $nombre = $_POST['nombre'] ?? '';
        $mail = $_POST['mail'] ?? '';
        $password = $_POST['pass'] ?? '';
        $confirmacion = $_POST['confirmar_pass'] ?? '';

        // Rechazar valores que no sean cadenas.
        foreach ([$nombre, $mail, $password, $confirmacion] as $valor) {
            if (!is_string($valor)) {
                http_response_code(400);
                $errores[] = 'Los datos enviados no son válidos.';
                $nombre = '';
                $mail = '';

                require __DIR__ . '/../vistas/registro.php';
                return;
            }
        }

        $nombre = trim($nombre);
        $mail = trim($mail);

        if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 100) {
            $errores[] = 'El nombre debe tener entre 1 y 100 caracteres.';
        }

        if (
            mb_strlen($mail, 'UTF-8') > 150 ||
            filter_var($mail, FILTER_VALIDATE_EMAIL) === false
        ) {
            $errores[] = 'Ingresá un correo electrónico válido.';
        }

        if (strlen($password) > 72 || str_contains($password, "\0")) {
            $errores[] = 'La contraseña debe ocupar como máximo 72 bytes y no contener bytes nulos.';
        } elseif (trim($password) === '' || mb_strlen($password, 'UTF-8') < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if ($password !== $confirmacion) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        if ($errores !== []) {
            http_response_code(422);
            require __DIR__ . '/../vistas/registro.php';
            return;
        }

        // Comprobar si el correo ya está registrado.
        if ($this->usuarios->findByMail($mail) !== null) {
            $errores[] = 'Ya existe una cuenta con ese correo electrónico.';

            http_response_code(422);
            require __DIR__ . '/../vistas/registro.php';
            return;
        }

        // Obtener la validación inicial por nivel, no por ID.
        $validacionInicial = $this->validaciones->findByNivel(
            NivelValidacion::DEFAULT_NIVEL_USUARIO
        );

        if ($validacionInicial === null) {
            error_log('Registro: no se encontró el nivel de validación inicial.');

            http_response_code(500);
            echo 'No fue posible completar el registro.';
            return;
        }

        try {
            // Crear la entidad con los privilegios iniciales definidos
            // por la factoría Usuario::crear().
            $usuario = Usuario::crear(
                $nombre,
                $mail,
                $password,
                $validacionInicial->getId()
            );

            // Persistir la entidad.
            $this->usuarios->insertarUsuario($usuario);

        } catch (PDOException $e) {
            // MySQL: 1062 corresponde a una clave duplicada.
            // Solo tratamos como correo duplicado una violación de
            // la restricción UNIQUE del campo mail.
            $codigoMysql = (int) ($e->errorInfo[1] ?? 0);
            $detalleMysql = (string) ($e->errorInfo[2] ?? '');

            if (
                $codigoMysql === 1062 &&
                str_contains($detalleMysql, 'mail')
            ) {
                $errores[] = 'Ya existe una cuenta con ese correo electrónico.';

                http_response_code(422);
                require __DIR__ . '/../vistas/registro.php';
                return;
            }

            error_log('Error de persistencia durante el registro.');

            http_response_code(500);
            echo 'No fue posible completar el registro.';
            return;
        }

        require __DIR__ . '/../vistas/registro-confirmacion.php';
    }
}
