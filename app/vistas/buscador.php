<?php
/**
 * Vista de la página de búsqueda de campañas y recursos en todospara.uno
 */

$tituloPagina     = 'Buscar campañas — todospara.uno';
$paginaActual     = 'buscador';
$usuarioActual    = null; // Cambiar según corresponda si hay sesión iniciada
$estilosPagina    = ['buscador.css']; // Hoja propia para el layout del buscador

// Nota técnica: $categoriasCampanas es provista por el controlador consultando al repositorio correspondiente.
// Si no está definida previamente, se inicializa como arreglo vacío para evitar errores.
/*$categoriasCampanas = $categoriasCampanas ?? [];*/

require __DIR__ . '/parciales/cabecera.php';

require_once __DIR__ . '/conexion.php';

$categoriasCampanas = mysqli_query($conexion, "SELECT * FROM cat_camp");
?>


<section class="seccion">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h1>Buscar campañas</h1>
            <p class="bajada">Encontrá la campaña solidaria que querés apoyar con dinero o insumos.</p>
        </div>

        <form class="tarjeta" action="resultados_buscar.php" method="post">
            
            <!-- Campo de búsqueda con lupa integrada -->
            <div class="campo">
                <label for="buscar">Palabra clave</label>
                <div class="buscador-contenedor-input">
                    <svg class="buscador-icono" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="search" id="buscar" name="buscar" placeholder="Ej. Alimentos, abrigo..." />
                </div>
            </div>

            <!-- Selector de categorías de campanas cargado desde la base de datos -->
            <div class="campo">
                <label for="id_categoria_campana">Categoría de Campaña</label>
                <select id="id_categoria_campana" name="id_categoria_campana">
                    <option value="">Todas las categorías</option>
                     <?php foreach ($categoriasCampanas as $categoria): ?>
                        <option value="<?= escapar($categoria['id']) ?>"><?= escapar($categoria['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="boton">Buscar campaña</button>
        </form>
    </div>
</section>


<?php require __DIR__ . '/parciales/pie.php'; ?>