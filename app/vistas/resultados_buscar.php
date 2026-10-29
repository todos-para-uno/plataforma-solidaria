<?php
/** Resultados públicos preparados por BuscadorController; la vista no consulta la base. */
$tituloPagina = 'Resultados de búsqueda — todospara.uno';
$paginaActual = 'explorar';
$usuarioActual = $usuarioActual ?? null;
$estilosPagina = ['buscador.css', 'resultados_buscar.css'];
$buscar = $buscar ?? '';
$idCategoria = $idCategoria ?? null;
$categoriasCampanas = $categoriasCampanas ?? [];
$errores = $errores ?? [];
$resultados = $resultados ?? [];
$nros = count($resultados);
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion" aria-labelledby="titulo-resultados">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h1 id="titulo-resultados">Encontrá una causa para acompañar</h1>
            <p class="bajada">Explorá las campañas activas o ajustá los filtros para encontrar dónde sumar tu ayuda.</p>
        </div>
        <?php require __DIR__ . '/parciales/formulario_busqueda.php'; ?>

        <?php if ($errores === []): ?>
            <div class="resultados-encabezado">
                <h2>Resultados de búsqueda</h2>
                <p><strong><?= $nros ?></strong> <?= $nros === 1 ? 'campaña encontrada' : 'campañas encontradas' ?><?= $buscar !== '' ? ' para ' : '' ?><?php if ($buscar !== ''): ?><em>“<?= escapar($buscar) ?>”</em><?php endif; ?>.</p>
            </div>
            <?php if ($nros === 0): ?>
                <div class="tarjeta resultados-vacios" role="region" aria-label="Sin resultados">
                    <h3>Todavía no encontramos una campaña para esta búsqueda.</h3>
                    <p>Probá con otra palabra o elegí todas las categorías para ampliar los resultados.</p>
                    <a href="<?= escapar($rutas['resultados_buscar']) ?>" class="boton boton-secundario">Ver todas las campañas activas</a>
                </div>
            <?php else: ?>
                <div class="grilla-campanas">
                    <?php foreach ($resultados as $resultado): ?>
                        <article class="tarjeta campana resultado-campana">
                            <a class="resultado-enlace" href="<?= escapar($rutas['campana'] . '&id=' . $resultado->getId()) ?>">
                                <div class="resultado-categoria"><?= escapar($nombresCategorias[$resultado->getCategoria()] ?? 'Sin categoría') ?></div>
                                <div class="campana-cuerpo">
                                    <span class="insignia"><?= $resultado->getMonetaria() ? 'Dinero' : 'Insumos' ?></span>
                                    <h3><?= escapar($resultado->getNombre()) ?></h3>
                                    <p class="texto-secundario">Estado: <strong>Activa</strong></p>
                                    <p class="resultado-desc"><?= escapar(trim($resultado->getDescripcion() ?? '') !== '' ? $resultado->getDescripcion() : 'Esta campaña todavía no tiene una descripción.') ?></p>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
