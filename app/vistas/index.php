<?php
/**
 * Página de inicio que carga public_php/index.php.
 * Las campañas de este arreglo son ficticias; todavía no se consultan en la base.
 */

$tituloPagina  = 'Todospara.uno — Campañas solidarias';
$paginaActual  = 'inicio';
$usuarioActual = $usuarioActual ?? null;
$estilosPagina = ['inicio.css', 'index.css']; // Se cargan en este orden, después de base.css.

$campanas = [
    ['titulo' => 'Una mesa para compartir', 'estado' => 'Activa', 'descripcion' => 'Ayudanos a recaudar dinero para nuestro comedor!'],
    ['titulo' => 'Mochilas llenas de futuro', 'estado' => 'Activa', 'descripcion' => 'Estamos buscando recolectar suficientes kits escolares 
    para comenzar el año. Entra a nuestra pagina para saber como ayudar!'],
    ['titulo' => 'Abriguemos juntos', 'estado' => 'Activa', 'descripcion' => 'Recolectando prendas para ayudar a quienes más las necesitan.'],
];
$nombresNivel = ['blanco' => 'Blanco', 'celeste' => 'Celeste', 'azul' => 'Azul', 'violeta' => 'Violeta'];

require __DIR__ . '/parciales/cabecera.php';
?>

<section class="seccion portada">
    <div class="contenedor portada-interior">
        <h1>Todospara.uno</h1>
        <p class="bajada">
            Todospara.uno es una plataforma solidaria cuyo objetivo es conectar a las almas solidarias de la 
            argentina con quienes más las necesitan, a través del poder del internet<br>
            Buscamos agrupar campañas solidarias en una plataforma confiable, y facilitar su propagación a través de las redes sociales.
        </p>
        <div>
            <a href="<?= escapar($rutas['explorar']) ?>" class="boton">Explorar campañas</a>
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
            <?php foreach ($campanas as $c):  ?>
                <article class="tarjeta campana">
                    <div class="campana-imagen" role="img" aria-label="Foto de la campaña (pendiente)"></div>
                    <div class="campana-cuerpo">
                        <h3><a href="#"><?= escapar($c['titulo']) ?></a></h3>
                        <p class="texto-secundario">
                            Estado: <strong><?= escapar($c['estado']) ?></strong>
                        </p>
                        <div>
                            <p class="resultado-desc">
                                    <?= escapar($c['descripcion']) ?>
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
