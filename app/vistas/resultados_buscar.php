<?php
/**
 * Vista de resultados de búsqueda de campañas utilizando la conexión PDO común.
 * Estilo de listado alternativo (diferente a la grilla de tarjetas de la home).
 */

$tituloPagina  = 'Resultados de búsqueda — todospara.uno';
$paginaActual  = 'explorar';
$usuarioActual = null; // Cambiar según corresponda si hay sesión iniciada
$estilosPagina = ['resultados_buscar.css']; // Hoja propia para el layout de este listado

require __DIR__ . '/parciales/cabecera.php';

require_once __DIR__ . '/conexion.php';

$buscar = is_string($_POST['buscar'] ?? null) ? $_POST['buscar'] : '';
$consulta = $conexion->prepare('SELECT * FROM camp WHERE nombre LIKE :buscar');
$consulta->execute(['buscar' => '%' . $buscar . '%']);
$resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
$nros = count($resultados);
?>

<main>
    <section class="seccion">
        <div class="contenedor">
            <div class="seccion-encabezado">
                <h1>Resultados de búsqueda</h1>
                <p class="bajada">
                    Búsqueda: <em><?= escapar($buscar) ?></em> 
                    (<strong><?= $nros ?></strong> <?= $nros === 1 ? 'resultado encontrado' : 'resultados encontrados' ?>)
                </p>
            </div>

            <?php if ($nros === 0): ?>
                <div class="alerta alerta-aviso" role="region" aria-label="Sin resultados">
                    <p>No se encontraron campañas que coincidan con tu búsqueda. Probá buscando con otras palabras o explorá las campañas activas.</p>
                </div>
                <div style="margin-top: var(--e-6);">
                    <a href="<?= escapar($rutas['explorar']) ?>" class="boton boton-secundario">Nueva búsqueda</a>
                </div>
            <?php else: ?>
                <div class="grilla-campanas">
                    <?php foreach ($resultados as $resultado): ?>
                        <article class="tarjeta campana">
                            <div class="campana-imagen" role="img" aria-label="Foto de la campaña"></div>
                            <div class="campana-cuerpo">
                                <h3><a href="#"><?= escapar($resultado['nombre']) ?></a></h3>
                                <p class="texto-secundario">
                                    Estado: <strong><?= escapar(ucfirst($resultado['estado'])) ?></strong>
                                </p>
                                <p class="resultado-desc">
                                    <?= escapar($resultado['descripcion'] ?? 'Sin descripción detallada.') ?>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </section>
</main>

<?php require __DIR__ . '/parciales/pie.php'; ?>
