<?php
/*
 * Página de inicio de prueba, para ver la guía con contenido real.
 * Textos tomados de la maqueta "pruebas_todosparauno". Datos ficticios.
 */
$tituloPagina = null;
$paginaActual = 'inicio';
$usuarioActual = null;
$estilosPagina = ['inicio.css'];

$campanas = [
    ['titulo' => 'Una mesa para compartir', 'categoria' => 'Alimentos', 'autor' => 'Ana Pérez', 'nivel' => 'azul',
     'logrado' => 150000, 'objetivo' => 250000, 'monetaria' => true],
    ['titulo' => 'Mochilas llenas de futuro', 'categoria' => 'Educación', 'autor' => 'Luis Gómez', 'nivel' => 'celeste',
     'logrado' => 30, 'objetivo' => 60, 'monetaria' => false, 'insumo' => 'kits escolares'],
    ['titulo' => 'Abriguemos juntos', 'categoria' => 'Comunidad', 'autor' => 'Carla Ruiz', 'nivel' => 'violeta',
     'logrado' => 80, 'objetivo' => 100, 'monetaria' => false, 'insumo' => 'prendas'],
];
$nombresNivel = ['blanco' => 'Blanco', 'celeste' => 'Celeste', 'azul' => 'Azul', 'violeta' => 'Violeta'];

require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion portada" aria-labelledby="titulo-inicio">
    <div class="contenedor portada-interior">
        <div>
            <h1 id="titulo-inicio">Lo que para vos es poco, para alguien es un montón.</h1>
            <p class="bajada">Conectamos a quienes necesitan una mano con quienes quieren ayudar, con dinero o con insumos.</p>
            <div class="portada-acciones">
                <a class="boton boton-donar" href="#campanas">Donar dinero</a>
                <a class="boton boton-secundario" href="#campanas">Donar insumos</a>
            </div>
        </div>
        <div class="tarjeta portada-nota">
            <h2>Entre todos, hacemos la diferencia.</h2>
            <p class="texto-secundario">Somos el puente entre una necesidad y las ganas de ayudar. Cada campaña muestra quién la crea y cuánto lleva reunido.</p>
        </div>
    </div>
</section>

<section class="seccion seccion-papel" aria-labelledby="titulo-pasos">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h2 id="titulo-pasos">Un pequeño gesto, en tres pasos</h2>
            <p class="bajada">Ayudar puede ser simple.</p>
        </div>
        <ol class="pasos">
            <li><span class="paso-numero" aria-hidden="true">1</span><h3>Elegí una campaña</h3><p>Conocé las campañas y encontrá una historia con la que quieras colaborar.</p></li>
            <li><span class="paso-numero" aria-hidden="true">2</span><h3>Sumá tu aporte</h3><p>Podés ayudar con dinero o con insumos que otra persona necesita.</p></li>
            <li><span class="paso-numero" aria-hidden="true">3</span><h3>Seguí el progreso</h3><p>Mirá cómo avanza la campaña y lo que se logra entre todos.</p></li>
        </ol>
    </div>
</section>

<section class="seccion" id="campanas" aria-labelledby="titulo-campanas">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h2 id="titulo-campanas">Una mano puede cambiar el día</h2>
            <p class="bajada">Campañas de ejemplo. Los datos son ficticios.</p>
        </div>
        <div class="grilla-campanas">
            <?php foreach ($campanas as $c): $pct = porcentajeProgreso($c['logrado'], $c['objetivo']); ?>
                <article class="tarjeta campana">
                    <div class="campana-imagen" role="img" aria-label="Foto de la campaña (pendiente)"></div>
                    <div class="campana-cuerpo">
                        <div class="campana-datos">
                            <span class="insignia"><?= escapar($c['categoria']) ?></span>
                            <span class="insignia"><?= $c['monetaria'] ? 'Dinero' : 'Insumos' ?></span>
                        </div>
                        <h3><a href="#"><?= escapar($c['titulo']) ?></a></h3>
                        <p class="campana-autor">Creada por <?= escapar($c['autor']) ?>
                            <span class="insignia nivel-<?= $c['nivel'] ?>"><?= $nombresNivel[$c['nivel']] ?></span></p>
                        <div>
                            <div class="progreso" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso de <?= escapar($c['titulo']) ?>">
                                <span class="progreso-relleno" style="width: <?= $pct ?>%"></span>
                            </div>
                            <p class="progreso-cifras">
                                <?php if ($c['monetaria']): ?>
                                    <strong><?= formatearPesos($c['logrado']) ?></strong> de <?= formatearPesos($c['objetivo']) ?> (<?= $pct ?>%)
                                <?php else: ?>
                                    <strong><?= (int) $c['logrado'] ?></strong> de <?= (int) $c['objetivo'] ?> <?= escapar($c['insumo']) ?> (<?= $pct ?>%)
                                <?php endif; ?>
                            </p>
                        </div>
                        <a class="boton boton-donar boton-ancho" href="#">Donar</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/parciales/pie.php'; ?>
