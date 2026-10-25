<?php
/**
 * Vista de detalle de una campaña solidaria en todospara.uno
 */

$tituloPagina  = isset($campana['nombre']) ? escapar($campana['nombre']) . ' — todospara.uno' : 'Detalle de campaña — todospara.uno';
$paginaActual  = 'explorar';
$usuarioActual = $usuarioActual ?? null;
$estilosPagina = ['campana.css']; // Hoja de estilos propia para el layout ancho

// Nota técnica: $campana es provista por el controlador mediante los repositorios correspondientes.
$campana = $campana ?? [
    'id'                   => 1,
    'nombre'               => 'Una mesa para compartir',
    'categoria_nombre'     => 'Alimentos',
    'monetaria'            => true,
    'creador_nombre'       => 'Ana Pérez',
    'creador_nivel'        => 'azul',
    'creador_nivel_nombre' => 'Azul',
    'descripcion'          => 'Estamos juntando fondos y alimentos para armar cajas navideñas y asegurar una cena digna para más de 50 familias del barrio San José. Cada aporte cuenta para llenar una mesa.',
    'logrado'              => 150000,
    'objetivo'             => 250000,
    'objetivos_insumos'    => []
];

$nombresNivel = ['blanco' => 'Blanco', 'celeste' => 'Celeste', 'azul' => 'Azul', 'violeta' => 'Violeta'];
require __DIR__ . '/parciales/cabecera.php';

// Calculamos el porcentaje general si es monetaria
$pctMonetaria = ($campana['monetaria'] && $campana['objetivo'] > 0) 
    ? porcentajeProgreso($campana['logrado'], $campana['objetivo']) 
    : 0;
?>

<main>
    <section class="seccion">
        <div class="contenedor contenedor-detalle">
                
            <!-- Tarjeta principal de detalle -->
            <article class="tarjeta campana tarjeta-detalle">
                <div class="campana-imagen imagen-detalle" role="img" aria-label="Fotografía ilustrativa de la campaña"></div>
                    
                <div class="campana-cuerpo cuerpo-detalle">
                        
                    <!-- Insignias de categoría y tipo de donación -->
                    <div class="campana-datos">
                        <span class="insignia"><?= escapar($campana['categoria_nombre']) ?></span>
                        <span class="insignia"><?= $campana['monetaria'] ? 'Dinero' : 'Insumos' ?></span>
                    </div>

                    <!-- Título principal -->
                    <h1><?= escapar($campana['nombre']) ?></h1>

                    <!-- Creador y nivel de validación -->
                    <p class="campana-autor">
                        Creada por <?= escapar($campana['creador_nombre']) ?>
                        <span class="insignia nivel-<?= escapar($campana['creador_nivel']) ?>">
                            <?= escapar($campana['creador_nivel_nombre']) ?>
                        </span>
                    </p>

                    <!-- Descripción de la campaña -->
                    <div class="detalle-seccion-bloque">
                        <h3>Sobre esta campaña</h3>
                        <p><?= nl2br(escapar($campana['descripcion'])) ?></p>
                    </div>

                    <!-- Progreso de la campaña -->
                    <div class="detalle-seccion-bloque detalle-progreso-contenedor">
                        <h3>Progreso de la campaña</h3>

                        <?php if ($campana['monetaria']): ?>
                            <div class="progreso" role="progressbar" aria-valuenow="<?= $pctMonetaria ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso de <?= escapar($campana['nombre']) ?>">
                                <span class="progreso-relleno" style="width: <?= $pctMonetaria ?>%"></span>
                            </div>
                            <p class="progreso-cifras">
                                Recaudado: <strong><?= formatearPesos($campana['logrado']) ?></strong> de <?= formatearPesos($campana['objetivo']) ?> (<strong><?= $pctMonetaria ?>%</strong>)
                            </p>
                        <?php else: ?>
                            <?php if (!empty($campana['objetivos_insumos'])): ?>
                                <div class="lista-objetivos-insumos">
                                    <?php foreach ($campana['objetivos_insumos'] as $obj): 
                                        $pctInsumo = porcentajeProgreso($obj['parcial'], $obj['objetivo']);
                                    ?>
                                        <div class="item-objetivo-insumo">
                                            <p class="texto-insumo-nombre"><strong><?= escapar($obj['subcategoria_nombre'] ?? $obj['categoria_insumos_nombre']) ?></strong></p>
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
                        <button type="button" class="boton boton-donar boton-ancho btn-abrir-modal-donar">Donar a esta campaña</button>
                        <a href="#" class="boton boton-secundario boton-ancho">Volver al buscador</a>
                    </div>

                </div>
            </article>

        </div>
    </section>
</main>

<!-- MODAL / FORMULARIO FLOTANTE DE DONACIÓN -->
<div id="modal-donar" class="modal-overlay" hidden>
    <div class="tarjeta modal-contenido" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-donar">
        <div class="modal-encabezado">
            <h2 id="titulo-modal-donar">Donar a esta campaña</h2>
            <button type="button" id="cerrar-modal" class="modal-cerrar" aria-label="Cerrar ventana">&times;</button>
        </div>
        
        <p class="texto-secundario modal-subtitulo"><?= escapar($campana['nombre']) ?></p>

        <!-- Formulario estándar con método POST -->
        <form action="procesar_donacion.php" method="post">
            <input type="hidden" name="id_camp" value="<?= escapar($campana['id'] ?? 1) ?>">
            
            <?php if ($campana['monetaria']): ?>
                <div class="campo">
                    <label for="cantidad">Monto a donar ($)</label>
                    <input type="number" id="cantidad" name="cantidad" min="1" step="any" placeholder="Ej. 5000" required />
                </div>
                
                <div class="campo">
                    <label for="cbu">CBU de tu cuenta (origen de los fondos)</label>
                    <input type="text" id="cbu" name="cbu" maxlength="22" placeholder="Ingresá los 22 números de tu CBU o CVU" required />
                    <span class="campo-ayuda">El CBU de la cuenta desde la cual realizarás la transferencia.</span>
                </div>
            <?php else: ?>
                <div class="campo">
                    <label for="cantidad">Cantidad de insumos a donar</label>
                    <input type="number" id="cantidad" name="cantidad" min="1" placeholder="Ej. 5" required />
                </div>
                <p class="campo-ayuda">Estás colaborando con insumos físicos para esta campaña.</p>
            <?php endif; ?>

            <div class="modal-acciones">
                <button type="submit" class="boton boton-donar boton-ancho">Confirmar donación</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/parciales/pie.php'; ?>