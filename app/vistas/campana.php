<?php
/**
 * Vista de detalle de una campaña solidaria en todospara.uno
 */

// CampanaController entrega la entidad y sus datos relacionados; no hay datos de ejemplo.
$tituloPagina = $campana->getNombre() . ' — todospara.uno';
$paginaActual = 'explorar';
$usuarioActual = $usuarioActual ?? null;
$estilosPagina = ['campana.css'];
$coloresNivel = [1 => 'blanco', 2 => 'celeste', 3 => 'azul', 4 => 'violeta'];
$colorNivel = $coloresNivel[$validacion?->getNivel() ?? 1] ?? 'blanco';
require __DIR__ . '/parciales/cabecera.php';

// Calculamos el porcentaje general si es monetaria
$pctMonetaria = ($campana->getMonetaria() && $objetivoMonetario > 0)
    ? porcentajeProgreso($campana->getLogrado(), $objetivoMonetario)
    : 0;
?>

    <section class="seccion">
        <div class="contenedor contenedor-detalle">

            <!-- Tarjeta principal de detalle -->
            <article class="tarjeta campana tarjeta-detalle">
                <div class="campana-imagen imagen-detalle" role="img" aria-label="Fotografía ilustrativa de la campaña"></div>

                <div class="campana-cuerpo cuerpo-detalle">

                    <!-- Insignias de categoría y tipo de donación -->
                    <div class="campana-datos">
                        <span class="insignia"><?= escapar(($categoria?->getNombre() ?? 'Sin categoría')) ?></span>
                        <span class="insignia"><?= $campana->getMonetaria() ? 'Dinero' : 'Insumos' ?></span>
                    </div>

                    <!-- Título principal -->
                    <h1><?= escapar($campana->getNombre()) ?></h1>

                    <!-- Creador y nivel de validación -->
                    <p class="campana-autor">
                        Creada por <?= escapar(($creador?->getNombre() ?? 'Usuario no disponible')) ?>
                        <span class="insignia nivel-<?= escapar($colorNivel) ?>">
                            <?= escapar(($validacion?->getNombre() ?? 'Sin nivel de validación')) ?>
                        </span>
                    </p>

                    <!-- Descripción de la campaña -->
                    <div class="detalle-seccion-bloque">
                        <h3>Sobre esta campaña</h3>
                        <p><?= nl2br(escapar(($campana->getDescripcion() ?? 'Esta campaña todavía no tiene una descripción.'))) ?></p>
                    </div>

                    <!-- Progreso de la campaña -->
                    <div class="detalle-seccion-bloque detalle-progreso-contenedor">
                        <h3>Progreso de la campaña</h3>

                        <?php if ($campana->getMonetaria()): ?>
                            <div class="progreso" role="progressbar" aria-valuenow="<?= $pctMonetaria ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso de <?= escapar($campana->getNombre()) ?>">
                                <span class="progreso-relleno" style="width: <?= $pctMonetaria ?>%"></span>
                            </div>
                            <p class="progreso-cifras">
                                Recaudado: <strong><?= formatearPesos($campana->getLogrado()) ?></strong> de <?= formatearPesos($objetivoMonetario) ?> (<strong><?= $pctMonetaria ?>%</strong>)
                            </p>
                        <?php else: ?>
                            <?php if (!empty($objetivos)): ?>
                                <div class="lista-objetivos-insumos">
                                    <?php foreach ($objetivos as $obj):
                                        $pctInsumo = porcentajeProgreso($obj['parcial'], $obj['objetivo']);
                                    ?>
                                        <div class="item-objetivo-insumo">
                                            <p class="texto-insumo-nombre"><strong><?= escapar($obj['subcategoria_nombre'] ?? $obj['categoria_insumos_nombre'] ?? 'Insumos') ?></strong></p>
                                            <div class="progreso" role="progressbar" aria-valuenow="<?= $pctInsumo ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso de insumo">
                                                <span class="progreso-relleno" style="width: <?= $pctInsumo ?>%"></span>
                                            </div>
                                            <p class="progreso-cifras">
                                                Recaudado: <strong><?= (int) $obj['parcial'] ?></strong> de <?= (int) $obj['objetivo'] ?> (<strong><?= $pctInsumo ?>%</strong>)
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="texto-secundario">Esta campaña de insumos no registra objetivos específicos detallados.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones -->
                    <div class="detalle-acciones">
                        <button type="button" class="boton boton-donar boton-ancho" disabled aria-describedby="donaciones-pendientes">Donar a esta campaña</button>
                        <p id="donaciones-pendientes" class="texto-secundario">Las donaciones desde el sitio todavía no están disponibles.</p>
                        <a href="<?= escapar($rutas['resultados_buscar']) ?>" class="boton boton-secundario boton-ancho">Volver al buscador</a>
                    </div>

                </div>
            </article>

        </div>
    </section>

<?php require __DIR__ . '/parciales/pie.php'; ?>
