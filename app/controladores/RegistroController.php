<?php
class RegistroController
{
    public function mostrarFormulario(string $rutaBase, string $rutaAssets): void
    {
        require __DIR__ . '/../vistas/registro.php';
    }

    public function procesarRegistro(string $rutaBase, string $rutaAssets): void
    {
        // Demostración del recorrido HTTP. La validación, el guardado y la sesión
        // se integrarán después; por ahora no se leen ni conservan contraseñas.
        require __DIR__ . '/../vistas/registro-confirmacion.php';
    }
}
