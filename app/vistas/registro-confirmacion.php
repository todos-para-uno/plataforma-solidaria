<?php
$tituloPagina = 'Registro';
$paginaActual = 'registro';
$usuarioActual = null;
$estilosPagina = ['registro.css'];

require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion" aria-labelledby="titulo-confirmacion">
    <div class="contenedor">
        <div class="registro-interior">
            <h1 id="titulo-confirmacion">Formulario recibido</h1>
            <div class="alerta alerta-aviso" role="status">
                <p>acá iría iniciar la sesión</p>
            </div>
            <p>Esta es una demostración del registro. Todavía no se creó una cuenta.</p>
            <a class="boton" href="<?= escapar($rutas['inicio']) ?>">Volver al inicio</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
