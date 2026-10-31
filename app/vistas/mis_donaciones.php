<?php
/**
 * Vista del historial de donaciones del usuario en todospara.uno
 * Muestra las campañas a las que donó, indicando si fue dinero o insumos y la cantidad.
 */

$tituloPagina  = 'Mis donaciones — todospara.uno';
$paginaActual  = 'mis_donaciones';
$usuarioActual = $usuarioActual ?? null; 
$estilosPagina = ['mis_donaciones.css'];

$donaciones = $donaciones ?? [];
require_once __DIR__ . '/parciales/estados_pago.php';

require __DIR__ . '/parciales/cabecera.php';
?>


<section class="seccion">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h1>Mis donaciones</h1>
            <p class="bajada">Acá podés ver el historial de todas las campañas solidarias en las que colaboraste.</p>
        </div>

        <?php if (empty($donaciones)): ?>
            <div class="alerta alerta-aviso" role="region" aria-label="Sin donaciones">
                <p>Todavía no realizaste ninguna donación. Explorá las campañas activas y sumá tu aporte.</p>
            </div>
            <div style="margin-top: var(--e-6);">
                <a href="<?= escapar($rutas['explorar']) ?>" class="boton">Explorar campañas</a>
            </div>
        <?php else: ?>
            <div class="grilla-campanas">
                <?php foreach ($donaciones as $donacion): ?>
                    <article class="tarjeta campana">
                        <?php if (!empty($donacion['imagen'])): ?>
                            <img class="campana-imagen"
                                 src="<?= escapar($rutaAssets . '/imagenes/' . $donacion['imagen']) ?>"
                                 alt="<?= escapar($donacion['imagen_alt'] ?? 'Imagen ilustrativa de la campaña') ?>"
                                 width="960" height="640" loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="campana-imagen" role="img" aria-label="Foto de la campaña"></div>
                        <?php endif; ?>
                        <div class="campana-cuerpo">
                            <div class="campana-datos">
                                <span class="insignia">
                                    <?= $donacion['monetaria'] ? 'Dinero' : 'Insumos' ?>
                                </span>
                            </div>
                            <h3><?php if ($donacion['campana_estado'] === 'activa'): ?><a href="<?= escapar($rutas['campana'] . '&id=' . $donacion['id_camp']) ?>"><?= escapar($donacion['campaña_nombre']) ?></a><?php else: ?><?= escapar($donacion['campaña_nombre']) ?><?php endif; ?></h3>
                            <p><?= escapar(estadoPago($donacion['estado'], $donacion['detalle'])) ?></p>
                            <?php if ($donacion['referencia'] !== null): ?>
                                <p><a href="<?= escapar($rutaBase . '/index.php?ruta=pago_resultado&referencia=' . $donacion['referencia']) ?>">Consultar el aporte</a></p>
                            <?php endif; ?>
                                
                            <p class="texto-secundario">
                                Aporte realizado: 
                                <strong>
                                    <?php if ($donacion['monetaria']): ?>
                                        <?= formatearPesos($donacion['cantidad']) ?>
                                    <?php else: ?>
                                        <?= (int) $donacion['cantidad'] ?> unidades
                                    <?php endif; ?>
                                </strong>
                            </p>

                            <p class="resultado-desc" style="font-size: var(--t-chico); color: var(--texto-secundario); margin: 0;">
                                Fecha: <?= escapar($donacion['fecha_hora']) ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>


<?php require __DIR__ . '/parciales/pie.php'; ?>
