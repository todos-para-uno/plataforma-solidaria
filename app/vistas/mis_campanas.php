<?php
/*
 * Vista "Mis campañas": saludo, resumen en cifras y la lista de campañas del usuario.
 *
 * Datos que recibe de MisCampanasController:
 *   $campanas      array  Filas de CampRepository::buscarPorCreador().
 *   $resumen       array  creadas, activas, finalizadas, suspendidas y dinero.
 *   $usuarioActual array  nombre, admin y mensajesSinLeer, para la cabecera.
 */
$tituloPagina = 'Mis campañas';
$paginaActual = 'mis_campanas';
$estilosPagina = ['mis_campanas.css'];

require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion-papel mis-campanas-portada" aria-labelledby="titulo-saludo">
    <div class="contenedor">
        <h1 id="titulo-saludo">Hola, <?= escapar($usuarioActual['nombre']) ?></h1>
        <p class="bajada">Acá seguís cómo avanzan las campañas que creaste.</p>
    </div>
</section>

<div class="contenedor">
    <ul class="mis-campanas-resumen" aria-label="Resumen de tus campañas">
        <li class="tarjeta dato">
            <span class="dato-cifra"><?= (int) $resumen['creadas'] ?></span>
            <span class="dato-etiqueta">Campañas creadas</span>
        </li>
        <li class="tarjeta dato">
            <span class="dato-cifra"><?= (int) $resumen['activas'] ?></span>
            <span class="dato-etiqueta">Activas</span>
        </li>
        <li class="tarjeta dato">
            <span class="dato-cifra"><?= (int) $resumen['finalizadas'] ?></span>
            <span class="dato-etiqueta">Finalizadas</span>
        </li>
        <li class="tarjeta dato">
            <span class="dato-cifra"><?= (int) $resumen['suspendidas'] ?></span>
            <span class="dato-etiqueta">Suspendidas</span>
        </li>
        <li class="tarjeta dato">
            <span class="dato-cifra"><?= formatearPesos($resumen['dinero']) ?></span>
            <span class="dato-etiqueta">Dinero recaudado</span>
        </li>
    </ul>
</div>

<section class="seccion" aria-labelledby="titulo-mis-campanas">
    <div class="contenedor">
        <div class="mis-campanas-encabezado">
            <h2 id="titulo-mis-campanas">Mis campañas</h2>
            <a class="boton boton-secundario" href="<?= escapar($rutas['crear_campana']) ?>">Crear campaña</a>
        </div>

        <?php if (empty($campanas)): ?>
            <div class="tarjeta mis-campanas-vacio">
                <h3>Todavía no creaste campañas</h3>
                <p>Creá tu primera campaña y empezá a recibir donaciones de dinero o insumos.</p>
                <a class="boton" href="<?= escapar($rutas['crear_campana']) ?>">Crear campaña</a>
            </div>
        <?php else: ?>
            <div class="grilla-campanas">
                <?php foreach ($campanas as $campana): ?>
                    <?php
                    // En dinero, lo recaudado está en camp.logrado; en insumos, en objetivos.parcial.
                    if ($campana['monetaria']) {
                        $recaudado = (float) $campana['logrado'];
                    } else {
                        $recaudado = (float) $campana['parcial'];
                    }
                    $objetivo = (float) $campana['objetivo'];
                    $porcentaje = porcentajeProgreso($recaudado, $objetivo);
                    ?>
                    <article class="tarjeta campana">
                        <div class="campana-cuerpo">
                            <div class="campana-datos">
                                <span class="insignia"><?= escapar($campana['nombre_categoria']) ?></span>
                                <?php if ($campana['monetaria']): ?>
                                    <span class="insignia">Dinero</span>
                                <?php else: ?>
                                    <span class="insignia">Insumos</span>
                                <?php endif; ?>
                                <span class="insignia"><?= escapar(ucfirst($campana['estado'])) ?></span>
                            </div>

                            <h3><?= escapar($campana['nombre']) ?></h3>

                            <?php if ($objetivo > 0): ?>
                                <div>
                                    <div class="progreso" role="progressbar" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100"
                                         aria-label="Progreso de <?= escapar($campana['nombre']) ?>">
                                        <span class="progreso-relleno" style="width: <?= $porcentaje ?>%"></span>
                                    </div>
                                    <p class="progreso-cifras">
                                        <?php if ($campana['monetaria']): ?>
                                            <strong><?= formatearPesos($recaudado) ?></strong> recaudado de <?= formatearPesos($objetivo) ?> (<?= $porcentaje ?>%)
                                        <?php else: ?>
                                            <strong><?= (int) $recaudado ?></strong> insumos recaudados de <?= (int) $objetivo ?> (<?= $porcentaje ?>%)
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <p class="progreso-cifras">Todavía no cargaste el objetivo de esta campaña.</p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/parciales/pie.php'; ?>
