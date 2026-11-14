<?php
$tituloPagina = 'No pudimos completar la operación';
$paginaActual = null;
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion"><div class="contenedor">
    <div class="seccion-encabezado"><h1>No pudimos completar la operación</h1></div>
    <div class="alerta alerta-error" role="alert"><?= escapar($error) ?></div>
    <a class="boton boton-secundario" href="<?= escapar($rutas['mercadopago']) ?>">Ver conexión con Mercado Pago</a>
    <a class="boton boton-secundario" href="<?= escapar($rutas['mis_donaciones']) ?>">Ver mis donaciones</a>
</div></section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
