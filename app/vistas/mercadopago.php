<?php
$tituloPagina = 'Conectar Mercado Pago';
$paginaActual = 'perfil';
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion"><div class="contenedor">
    <div class="seccion-encabezado"><h1>Recibí aportes en tu cuenta</h1><p class="bajada">Conectá Mercado Pago una vez para recibir las donaciones de tus campañas de dinero.</p></div>
    <?php if ($mensaje): ?><div class="alerta alerta-exito" role="status"><?= escapar($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alerta alerta-aviso" role="status"><?= escapar($error) ?></div><?php endif; ?>
    <div class="tarjeta">
        <?php if ($cuenta): ?>
            <h2>Mercado Pago conectado</h2>
            <p>Las donaciones se cobrarán en la cuenta que autorizaste. No compartís tu contraseña con todospara.uno.</p>
            <p class="texto-secundario">Podés renovar la autorización de esa misma cuenta cuando lo necesites.</p>
        <?php else: ?>
            <h2>Conectá tu cuenta</h2>
            <p>Vas a continuar en Mercado Pago para autorizar la conexión y después volverás a este sitio.</p>
        <?php endif; ?>
        <?php if ($configurada): ?>
            <?php if (!$config['produccion']): ?><p class="alerta alerta-aviso">Entorno de pruebas: conectá una cuenta de prueba.</p><?php endif; ?>
            <form action="<?= escapar($rutas['mp_conectar']) ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
                <button class="boton" type="submit"><?= $cuenta ? 'Renovar conexión' : 'Conectar Mercado Pago' ?></button>
            </form>
        <?php endif; ?>
    </div>
    <p style="margin-top:var(--e-8)"><a href="<?= escapar($rutas['crear_campana']) ?>">Volver a crear una campaña</a></p>
</div></section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
