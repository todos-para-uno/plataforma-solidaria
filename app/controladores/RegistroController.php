<?php
class RegistroController
{
    public function mostrarFormulario(string $rutaBase, string $rutaAssets): void
    {
        require __DIR__ . '/../vistas/registro.php';
    }

    public function procesarRegistro(string $rutaBase, string $rutaAssets): void
    {
        // Por ahora comprobamos que el envío del formulario llegue a esta pantalla.
        // Falta validar los datos, guardar el usuario e iniciar la sesión desde acá.
        // En esta demostración no leemos ni conservamos las contraseñas enviadas.
        require __DIR__ . '/../vistas/registro-confirmacion.php';
    }
}
