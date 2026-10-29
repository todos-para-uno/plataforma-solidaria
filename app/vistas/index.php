<?php
/**
 * Página de inicio que carga public_php/index.php.
 * Las campañas de este arreglo son ficticias; todavía no se consultan en la base.
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
$todasLasActivas = $campRepository->buscarActivas();
$campanas = array_slice($todasLasActivas, 0, 3); // Tomamos solo tres campañas

$tituloPagina  = 'Todospara.uno — Campañas solidarias';
$paginaActual  = 'inicio';
$usuarioActual = $usuarioActual ?? null;
$estilosPagina = ['inicio.css', 'index.css']; // Se cargan en este orden, después de base.css.
/*
$campanas = [
    ['titulo' => 'Una mesa para compartir', 'estado' => 'Activa', 'descripcion' => 'Ayudanos a recaudar dinero para nuestro comedor!',
     'imagen' => 'donaciones-mesa-compartir.webp', 'imagen_alt' => 'Escena ficticia de voluntarios compartiendo una comida con una vecina en un comedor comunitario.', 'ancho' => 960, 'alto' => 640],
    ['titulo' => 'Mochilas llenas de futuro', 'estado' => 'Activa', 'descripcion' => 'Estamos buscando recolectar suficientes kits escolares 
    para comenzar el año. Entra a nuestra pagina para saber como ayudar!',
     'imagen' => 'donaciones-mochilas-futuro.webp', 'imagen_alt' => 'Escena ficticia de una voluntaria entregando una mochila con útiles a una niña acompañada por su mamá.', 'ancho' => 960, 'alto' => 640],
    ['titulo' => 'Abriguemos juntos', 'estado' => 'Activa', 'descripcion' => 'Recolectando prendas para ayudar a quienes más las necesitan.',
     'imagen' => 'donaciones-abriguemos-juntos.webp', 'imagen_alt' => 'Guantes, gorro de lana y prendas de abrigo.', 'ancho' => 751, 'alto' => 500],
];
$nombresNivel = ['blanco' => 'Blanco', 'celeste' => 'Celeste', 'azul' => 'Azul', 'violeta' => 'Violeta'];
*/
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
<section class="seccion seccion-crear" aria-labelledby="titulo-crear">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h2 id="titulo-crear">Cómo crear una campaña</h2>
            <p class="bajada">Recaudar nunca fue tan simple.</p>
        </div>
        <ol class="pasos">
            <li><span class="paso-numero" aria-hidden="true">1</span><h3>Registrate</h3><p>Creá tu cuenta para empezar a compartir tu causa.</p></li>
            <li><span class="paso-numero" aria-hidden="true">2</span><h3>Creá tu campaña</h3><p>Contá qué necesitás y completá los datos de tu campaña.</p></li>
            <li><span class="paso-numero" aria-hidden="true">3</span><h3>Compartí tu campaña</h3><p>Difundí el enlace en tus redes para que más personas puedan ayudar.</p></li>
        </ol>
    </div>
</section>
<section class="seccion seccion-papel" id="campanas" aria-labelledby="titulo-campanas">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h2 id="titulo-campanas">Mirá algunas de las campañas ya creadas</h2>
        </div>
        <div class="grilla-campanas">
            <?php if (empty($campanas)): ?>
                <p class="texto-secundario">Todavía no hay campañas activas cargadas en la base de datos.</p>
            <?php else: ?>
                <?php foreach ($campanas as $c): ?>
                    <article class="tarjeta campana">
                        <!-- Imagen representativa o contenedor visual alineado con resultados_buscar.php -->
                        <div class="campana-imagen" role="img" aria-label="Foto de la campaña"></div>
                        <div class="campana-cuerpo">
                            <div class="campana-datos">
                                <span class="insignia"><?= escapar($c['nombre_categoria']) ?></span>
                                <span class="insignia"><?= $c['monetaria'] ? 'Dinero' : 'Insumos' ?></span>
                            </div>
                            <h3><a href="#"><?= escapar($c['nombre']) ?></a></h3>
                            <p class="texto-secundario">
                                Estado: <strong>Activa</strong>
                            </p>
                            <div>
                                <p class="resultado-desc">
                                    <?= escapar(trim($c['descripcion'] ?? '') !== '' ? $c['descripcion'] : 'Esta campaña todavía no tiene una descripción detallada.') ?>
                                </p>
                            </div>
                            <a class="boton boton-donar boton-ancho" href="index.php?ruta=campana&id=<?= (int) $c['id'] ?>">Donar a esta campaña</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>



<?php require __DIR__ . '/parciales/pie.php'; ?>
