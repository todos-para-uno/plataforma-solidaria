<?php
$tituloPagina = 'Estado de tu aporte';
$paginaActual = 'mis_donaciones';
require_once __DIR__ . '/parciales/estados_pago.php';
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion"><div class="contenedor">
    <div class="seccion-encabezado"><h1>Estado de tu aporte</h1><p class="bajada"><?= escapar(estadoPago($pago['estado'], $pago['detalle'])) ?></p></div>
    <div class="tarjeta">
        <p>Monto del aporte: <strong><?= formatearPesos($pago['monto']) ?></strong></p>
        <p>Importe confirmado para el progreso: <strong><?= formatearPesos($pago['acreditado']) ?></strong></p>
        <p>El estado se confirma con Mercado Pago. Volver del checkout no significa que el pago esté aprobado.</p>
        <form action="<?= escapar($rutas['mp_verificar']) ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
            <input type="hidden" name="referencia" value="<?= escapar($pago['referencia']) ?>">
            <button class="boton boton-secundario" type="submit">Consultar estado</button>
        </form>
    </div>
    <?php if ($pago['id_usuario'] !== null): ?>
        <p style="margin-top:var(--e-8)"><a href="<?= escapar($rutas['mis_donaciones']) ?>">Ver mis donaciones</a></p>
    <?php else: ?>
        <p class="campo-ayuda" style="margin-top:var(--e-8)">Podés consultar este aporte desde el navegador con el que lo hiciste mientras se conserve tu sesión.</p>
        <p><a href="<?= escapar($rutas['explorar']) ?>">Explorar campañas</a></p>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
