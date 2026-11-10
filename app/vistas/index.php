<?php
/**
 * Página de inicio que carga public_php/index.php.
 * Muestra hasta tres campañas activas obtenidas como entidades desde la base.
 */

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../repositorios/CampRepository.php';
require_once __DIR__ . '/../repositorios/CatCampRepository.php';
require_once __DIR__ . '/../controladores/BuscadorController.php';

$pdo = conectarBD();

// Instanciamos los repositorios con la conexión PDO
$campRepository = new CampRepository($pdo);
$catCampRepository = new CatCampRepository($pdo);

// Instanciamos el controlador que maneja la lógica de búsqueda
$buscadorController = new BuscadorController($campRepository, $catCampRepository);

// Obtenemos las campañas activas desde la base de datos (por ejemplo, limitando a 3 para la home)
$todasLasActivas = $campRepository->findByEstado(EstadoCamp::Activa);
$nombresCategorias = array_column($catCampRepository->listarParaBuscador(), 'nombre', 'id');
$campanas = array_slice($todasLasActivas, 0, 3); // Tomamos solo tres campañas

$portadas = (new CampanaImagenesRepository($pdo))->portadas(array_map(fn($c) => $c->getId(), $campanas));

$tituloPagina  = 'Todospara.uno — Campañas solidarias';
$paginaActual  = 'inicio';
$usuarioActual = $usuarioActual ?? null;
$estilosPagina = ['inicio.css', 'index.css']; // Se cargan en este orden, después de base.css.
require __DIR__ . '/parciales/cabecera.php';
?>

<section class="seccion portada" aria-labelledby="titulo-inicio">
    <div class="contenedor portada-interior">
        <div class="portada-mensaje">
            <p class="portada-marca">Todospara.uno · Solidaridad que conecta</p>
            <h1 id="titulo-inicio">Un pequeño gesto puede cambiar una historia.</h1>
            <p class="bajada">Conectamos a quienes necesitan una mano con quienes quieren ayudar. Encontrá campañas solidarias en Argentina y sumá tu aporte con dinero o insumos.</p>
            <div class="portada-acciones">
                <a href="<?= escapar($rutas['explorar']) ?>" class="boton">Explorar campañas</a>
            </div>
        </div>
    </div>
</section>

<section class="seccion seccion-ayudar" aria-labelledby="titulo-pasos">
    <div class="contenedor crear-invitacion ayudar-invitacion">
        <div class="crear-invitacion-mensaje">
            <p class="etiqueta-seccion">Para quienes quieren dar una mano</p>
            <h2 id="titulo-pasos">Un pequeño gesto, en tres pasos</h2>
            <p>Hay muchas formas de dar una mano. Encontrá la tuya.</p>
            <a class="boton" href="<?= escapar($rutas['explorar']) ?>">Explorar campañas</a>
        </div>
        <ol class="pasos pasos-tarjetas pasos-ayudar">
            <li><span class="paso-numero" aria-hidden="true">1</span><div><h3>Elegí una campaña</h3><p>Conocé las campañas y encontrá una historia con la que quieras colaborar.</p></div></li>
            <li><span class="paso-numero" aria-hidden="true">2</span><div><h3>Sumá tu aporte</h3><p>Podés ayudar con dinero o con insumos que otra persona necesita.</p></div></li>
            <li><span class="paso-numero" aria-hidden="true">3</span><div><h3>Seguí el progreso</h3><p>Mirá cómo avanza la campaña y lo que se logra entre todos.</p></div></li>
        </ol>
    </div>
</section>
<section class="seccion seccion-crear" aria-labelledby="titulo-crear">
    <div class="contenedor crear-invitacion">
        <div class="crear-invitacion-mensaje">
            <p class="etiqueta-seccion">Para quienes necesitan una mano</p>
            <h2 id="titulo-crear">Tu causa también puede reunirnos.</h2>
            <p>Compartí tu historia y lo que necesitás. El primer paso para recibir ayuda es darla a conocer.</p>
            <a class="boton" href="<?= escapar($rutaCrearCampana) ?>">Crear una campaña</a>
        </div>
        <ol class="pasos pasos-tarjetas">
            <li><span class="paso-numero" aria-hidden="true">1</span><div><h3>Creá tu cuenta</h3><p>Registrate para empezar a compartir tu causa.</p></div></li>
            <li><span class="paso-numero" aria-hidden="true">2</span><div><h3>Contá tu historia</h3><p>Explicá qué necesitás y definí los objetivos de tu campaña.</p></div></li>
            <li><span class="paso-numero" aria-hidden="true">3</span><div><h3>Invitá a colaborar</h3><p>Compartí el enlace para que más personas puedan ayudar.</p></div></li>
        </ol>
    </div>
</section>
<section class="seccion seccion-papel seccion-campanas" id="campanas" aria-labelledby="titulo-campanas">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <p class="etiqueta-seccion">Historias que nos acercan</p>
            <h2 id="titulo-campanas">Una causa, muchas manos.</h2>
            <p class="bajada">Conocé algunas de las campañas activas de la comunidad.</p>
        </div>
        <div class="grilla-campanas">
            <?php if (empty($campanas)): ?>
                <div class="estado-vacio">
                    <h3>Las próximas historias empiezan acá.</h3>
                    <p>Todavía no hay campañas activas. Si necesitás ayuda, podés compartir tu causa con la comunidad.</p>
                    <a class="boton" href="<?= escapar($rutaCrearCampana) ?>">Crear una campaña</a>
                </div>
            <?php else: ?>
                <?php foreach ($campanas as $c): ?>
                    <article class="tarjeta campana">
                        <!-- Imagen representativa o contenedor visual alineado con resultados_buscar.php -->
                        <?php if (!empty($portadas[$c->getId()])): ?><img class="campana-imagen" src="<?= escapar($rutaBase . '/' . $portadas[$c->getId()]) ?>" alt="" loading="lazy">
                        <?php else: ?><div class="campana-imagen" role="img" aria-label="Campaña sin fotos"></div><?php endif; ?>
                        <div class="campana-cuerpo">
                            <div class="campana-datos">
                                <span class="insignia"><?= escapar($nombresCategorias[$c->getCategoria()] ?? 'Sin categoría') ?></span>
                                <span class="insignia"><?= $c->getMonetaria() ? 'Dinero' : 'Insumos' ?></span>
                            </div>
                            <h3><a href="<?= escapar($rutas['campana'] . '&id=' . $c->getId()) ?>"><?= escapar($c->getNombre()) ?></a></h3>
                            <p class="texto-secundario">
                                Estado: <strong>Activa</strong>
                            </p>
                            <div>
                                <p class="resultado-desc">
                                    <?= escapar(trim($c->getDescripcion() ?? '') !== '' ? $c->getDescripcion() : 'Esta campaña todavía no tiene una descripción detallada.') ?>
                                </p>
                            </div>
                            <a class="boton boton-donar boton-ancho" href="<?= escapar($rutas['campana'] . '&id=' . $c->getId()) ?>">Donar a esta campaña</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($campanas)): ?>
            <div class="campanas-ver-todas"><a class="boton boton-secundario" href="<?= escapar($rutas['explorar']) ?>">Ver todas las campañas</a></div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/parciales/pie.php'; ?>
