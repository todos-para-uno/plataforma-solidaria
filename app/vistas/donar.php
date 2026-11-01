<?php
$tituloPagina = 'Aportar a una campaña';
$paginaActual = null;
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion"><div class="contenedor">
    <div class="seccion-encabezado"><h1>Sumá tu aporte</h1><p class="bajada"><?= escapar($campana['nombre']) ?></p></div>
    <?php if ($error): ?><div class="alerta alerta-error" role="alert"><?= escapar($error) ?></div><?php endif; ?>
    <?php if (!$config['produccion']): ?><p class="alerta alerta-aviso">Estás en el entorno de pruebas. Usá los medios de pago de prueba de Mercado Pago.</p><?php endif; ?>
    <form class="tarjeta" action="<?= escapar($rutas['donar'] . '&id=' . $campana['id']) ?>" method="post">
        <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
        <input type="hidden" name="referencia" value="<?= escapar($referencia) ?>">
        <?php if ($usuario === null): ?>
            <div class="campo">
                <label for="aporte-mail">Correo electrónico</label>
                <input id="aporte-mail" name="mail" type="email" autocomplete="email" maxlength="254" value="<?= escapar($mailDonante) ?>" required aria-describedby="aporte-mail-ayuda">
                <span class="campo-ayuda" id="aporte-mail-ayuda">Podés donar sin crear una cuenta. Usaremos este correo para iniciar el pago.</span>
            </div>
        <?php endif; ?>
        <div class="campo"><label for="aporte-monto">Monto en pesos argentinos</label>
            <input type="number" id="aporte-monto" name="monto" min="0.01" max="9999999999.99" step="0.01" value="<?= escapar($monto) ?>" required aria-describedby="aporte-ayuda">
            <span class="campo-ayuda" id="aporte-ayuda">Continuarás en Mercado Pago para completar el pago. El aporte se sumará cuando se confirme.</span>
        </div>
        <button class="boton boton-donar" type="submit">Continuar a Mercado Pago</button>
    </form>
    <p style="margin-top:var(--e-8)"><a href="<?= escapar($rutas['campana'] . '&id=' . $campana['id']) ?>">Volver a la campaña</a></p>
</div></section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
