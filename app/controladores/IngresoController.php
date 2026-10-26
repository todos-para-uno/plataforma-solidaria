<?php

class IngresoController
{
    public function __construct(
        private UsuariosRepository $usuarios
    ) {}

    public function mostrarFormulario(
        string $rutaBase,
        string $rutaAssets
    ): void {
        $errores = [];
        $mail = '';

        require __DIR__ . '/../vistas/ingresar.php';
    }

    public function procesarIngreso(
        string $rutaBase,
        string $rutaAssets
    ): void {
        // Pendiente: validar el envío y el token CSRF, comprobar las credenciales
        // con findByMail() y verificarPassword(), e iniciar la sesión del usuario.
        throw new LogicException('El procesamiento del ingreso todavía no está implementado.');
    }
}
