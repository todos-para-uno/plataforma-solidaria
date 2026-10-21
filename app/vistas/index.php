<?php
/**
 * Vista de la página principal (Home) de todospara.uno
 */

$tituloPagina  = 'Todospara.uno — Campañas solidarias';
$paginaActual  = 'inicio';
$usuarioActual = null; // Podés cambiar esto para pruebas según corresponda
$estilosPagina = ['inicio.css', 'index.css']; // Opcional, si requiere layout específico

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

<section class="seccion portada">
    <div class="contenedor portada-interior">
        <h1>Todospara.uno</h1>
        <p class="bajada">
            Todospara.uno es una plataforma solidaria cuyo objetivo es conectar a las almas solidarias de la 
            argentina con quienes más las necesitan, a traves del poder del internet<br>
            Buscamos agrupar campañas solidarias en una plataforma confiable, y facilitar su propagacion a traves de las redes sociales.
        </p>
        <div>
            <a href="#" class="boton">Explorar campañas</a>
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
<section class="seccion" aria-labelledby="titulo-pasos">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h2 id="titulo-pasos">Como crear una campaña</h2>
            <p class="bajada">Recaudar nunca fue tan simple.</p>
        </div>
        <ol class="pasos">
            <li><span class="paso-numero" aria-hidden="true">1</span><h3>Registrate</h3><p>Clickea el boton de arriba a la de derecha, y crea tu cuenta.</p></li>
            <li><span class="paso-numero" aria-hidden="true">2</span><h3>Crea tu campaña</h3><p>Ingresa los datos importantes en el formulario.</p></li>
            <li><span class="paso-numero" aria-hidden="true">3</span><h3>Compartí tu campaña</h3><p>Postee el link a su campaña en sus redes sociales y vea como aumentan las donaciones.</p></li>
        </ol>
    </div>
</section>
<section class="seccion seccion-papel" id="campanas" aria-labelledby="titulo-campanas">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h2 id="titulo-campanas">Mira algunas de las campañas ya creadas</h2>
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