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
            <h1 id="titulo-confirmacion">Cuenta creada correctamente</h1>
            <div class="alerta alerta-aviso" role="status">
                <p>Tu cuenta fue registrada correctamente.</p>
            </div>
            <p>
                Ya podés volver al inicio.
                Tu cuenta todavía no está verificada.
            </p>
            <a class="boton" href="<?= escapar($rutas['inicio']) ?>">Volver al inicio</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
