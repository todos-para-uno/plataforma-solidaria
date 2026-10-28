<?php
/** El BuscadorController consulta categorías y valida los filtros. */
$tituloPagina = 'Buscar campañas — todospara.uno';
$paginaActual = 'explorar';
$usuarioActual = $usuarioActual ?? null;
$estilosPagina = ['buscador.css'];
$buscar = $buscar ?? '';
$idCategoria = $idCategoria ?? null;
$categoriasCampanas = $categoriasCampanas ?? [];
$errores = $errores ?? [];
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion" aria-labelledby="titulo-buscador">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h1 id="titulo-buscador">Buscar campañas</h1>
            <p class="bajada">Encontrá la campaña solidaria que querés apoyar con dinero o insumos.</p>
        </div>
        <?php require __DIR__ . '/parciales/formulario_busqueda.php'; ?>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
