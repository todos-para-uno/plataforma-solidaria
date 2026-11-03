<?php
$tituloPagina = 'Diagnóstico de conexión con Mercado Pago';
$paginaActual = 'mercadopago';
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion"><div class="contenedor">
    <div class="seccion-encabezado">
        <h1>Diagnóstico de conexión con Mercado Pago</h1>
        <p class="bajada">Estos datos corresponden al intento que vas a enviar. Podés compartir una captura de esta tabla.</p>
    </div>
    <div class="tarjeta">
        <table>
            <caption>Parámetros y comprobaciones del enlace de autorización</caption>
            <tbody>
                <?php foreach ($diagnostico as $campo => $valor): ?>
                    <tr><th scope="row"><?= escapar($campo) ?></th><td style="overflow-wrap:anywhere"><?= escapar($valor) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p>La coincidencia del callback se comprueba contra la configuración local. Esta pantalla no consulta la configuración guardada en Mercado Pago ni confirma que la aplicación esté habilitada allí.</p>
        <form action="<?= escapar($rutas['mp_conectar']) ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
            <input type="hidden" name="continuar_oauth" value="1">
            <button class="boton" type="submit">Continuar a Mercado Pago</button>
        </form>
        <p>Continuar envía el mismo intento revisado. Vence a los 10 minutos.</p>
        <a href="<?= escapar($rutas['mercadopago']) ?>">Volver</a>
    </div>
</div></section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
