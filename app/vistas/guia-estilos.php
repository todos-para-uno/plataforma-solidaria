<?php
/*
 * Ejemplos de los componentes compartidos de base.css para consultar al armar una pantalla.
 * Para verlos, ejecutá desde la raíz del proyecto:
 *   php -S localhost:8001 -t .
 * Después abrí http://localhost:8001/app/vistas/guia-estilos.php.
 * Agregá ?sesion=1 para simular el menú de usuario o ?sesion=admin para el de administrador.
 * Estos parámetros cambian la presentación; no inician sesión ni otorgan permisos.
 * Es una herramienta local del equipo. Al agregar un componente compartido, sumá su ejemplo acá.
 */
$tituloPagina = 'Guía de estilos';
$paginaActual = 'explorar';
$estilosPagina = ['guia-estilos.css'];

$modo = $_GET['sesion'] ?? '';
$usuarioActual = $modo === ''
    ? null
    : ['nombre' => 'Ana Pérez', 'admin' => $modo === 'admin', 'mensajesSinLeer' => 2];

$campanasEjemplo = [
    ['titulo' => 'Comedor comunitario Barrio Sur', 'categoria' => 'Alimentos', 'autor' => 'Ana Pérez', 'nivel' => 'azul',
     'logrado' => 320000, 'objetivo' => 500000, 'monetaria' => true],
    ['titulo' => 'Útiles para la escuela rural N° 12', 'categoria' => 'Educación', 'autor' => 'Luis Gómez', 'nivel' => 'celeste',
     'logrado' => 38, 'objetivo' => 100, 'monetaria' => false, 'insumo' => 'mochilas'],
    ['titulo' => 'Abrigo para el refugio Patitas', 'categoria' => 'Animales', 'autor' => 'Carla Ruiz', 'nivel' => 'blanco',
     'logrado' => 0, 'objetivo' => 150000, 'monetaria' => true],
];
$colores = [
    ['variable' => 'azul', 'hex' => '#153a6b', 'uso' => 'Marca, texto, botón principal'],
    ['variable' => 'corazon', 'hex' => '#b8472b', 'uso' => 'Solo para donar'],
    ['variable' => 'sol', 'hex' => '#f4b223', 'uso' => 'Resaltado y progreso'],
    ['variable' => 'hueso', 'hex' => '#f7f5f0', 'uso' => 'Fondo de página'],
    ['variable' => 'papel', 'hex' => '#fffefa', 'uso' => 'Tarjetas y secciones alternadas'],
    ['variable' => 'grafito', 'hex' => '#4d4b46', 'uso' => 'Trazo de bordes'],
];
$nombresNivel = ['blanco' => 'Blanco', 'celeste' => 'Celeste', 'azul' => 'Azul', 'violeta' => 'Violeta'];

require __DIR__ . '/parciales/cabecera.php';
?>
<div class="contenedor">
    <section class="guia-seccion">
        <h1>Guía de estilos</h1>
        <p class="bajada">Todo lo que aparece en esta página sale de <code>base.css</code>. Si una pantalla necesita algo que no está acá, primero se agrega acá.</p>
    </section>

    <section class="guia-seccion" aria-labelledby="g-colores">
        <h2 id="g-colores">Colores</h2>
        <ul class="guia-muestras">
            <?php foreach ($colores as $color): ?>
                <li>
                    <span class="guia-color guia-color-<?= $color['variable'] ?>"></span>
                    <strong>--<?= $color['variable'] ?></strong> <code><?= $color['hex'] ?></code><br>
                    <span class="texto-secundario"><?= $color['uso'] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="guia-seccion" aria-labelledby="g-tipo">
        <h2 id="g-tipo">Tipografía</h2>
        <p class="guia-h1">Título de página (h1)</p>
        <p class="guia-h2">Título de sección (h2)</p>
        <p class="guia-h3">Título de tarjeta (h3)</p>
        <p class="bajada">Bajada: una o dos oraciones que explican la pantalla.</p>
        <p>Texto común. Hablamos de vos, con tildes y en oraciones cortas. Los títulos van en Georgia; el resto, en la fuente del sistema.</p>
        <p class="texto-secundario"><small>Texto secundario y ayudas.</small></p>
    </section>

    <section class="guia-seccion" aria-labelledby="g-botones">
        <h2 id="g-botones">Botones</h2>
        <div class="guia-fila">
            <a class="boton" href="#">Crear campaña</a>
            <a class="boton boton-secundario" href="#">Ver detalles</a>
            <a class="boton boton-donar" href="#">Donar</a>
            <button class="boton" type="button" disabled>Guardando…</button>
        </div>
        <p class="texto-secundario">Un solo botón principal por pantalla. El rojo corazón es exclusivo de donar.</p>
    </section>

    <section class="guia-seccion guia-dos" aria-labelledby="g-form">
        <div>
            <h2 id="g-form">Formularios</h2>
            <form class="tarjeta" action="#" method="post" >
                <div class="campo">
                    <label for="g-nombre">Nombre de la campaña</label>
                    <input id="g-nombre" name="nombre" type="text" value="Comedor comunitario Barrio Sur">
                </div>
                <div class="campo">
                    <label for="g-categoria">Categoría</label>
                    <select id="g-categoria" name="categoria"><option>Alimentos</option><option>Educación</option></select>
                </div>
                <div class="campo">
                    <label for="g-cbu">CBU</label>
                    <input id="g-cbu" name="cbu" type="text" inputmode="numeric" value="12345" aria-invalid="true" aria-describedby="g-cbu-error">
                    <span class="campo-error" id="g-cbu-error">El CBU tiene 22 números. Revisá que esté completo.</span>
                </div>
                <div class="campo">
                    <label for="g-desc">Descripción</label>
                    <textarea id="g-desc" name="descripcion" aria-describedby="g-desc-ayuda"></textarea>
                    <span class="campo-ayuda" id="g-desc-ayuda">Contá para qué es la ayuda y cómo se va a usar.</span>
                </div>
                <button class="boton boton-ancho" type="submit">Publicar campaña</button>
            </form>
        </div>
        <div>
            <h2>Mensajes del sistema</h2>
            <div class="alerta alerta-exito" role="status"><p>Campaña publicada. Ya aparece en Explorar campañas.</p></div>
            <div class="alerta alerta-aviso"><p>Tu campaña termina en 3 días.</p></div>
            <div class="alerta alerta-error" role="alert">
                <p>No pudimos publicar la campaña:</p>
                <ul><li>Falta el objetivo a recaudar.</li><li>El CBU tiene 22 números.</li></ul>
            </div>

            <h2>Niveles de confianza</h2>
            <div class="guia-fila">
                <?php foreach ($nombresNivel as $clase => $nombre): ?>
                    <span class="insignia nivel-<?= $clase ?>"><?= $nombre ?></span>
                <?php endforeach; ?>
            </div>
            <p class="texto-secundario">Se muestran junto al nombre del creador de una campaña.</p>
        </div>
    </section>

    <section class="guia-seccion" aria-labelledby="g-campanas">
        <h2 id="g-campanas">Tarjeta de campaña</h2>
        <div class="grilla-campanas">
            <?php foreach ($campanasEjemplo as $c):
                $pct = porcentajeProgreso($c['logrado'], $c['objetivo']); ?>
                <article class="tarjeta campana">
                    <div class="campana-imagen" role="img" aria-label="Foto de la campaña (pendiente)"></div>
                    <div class="campana-cuerpo">
                        <div class="campana-datos">
                            <span class="insignia"><?= escapar($c['categoria']) ?></span>
                            <span class="insignia"><?= $c['monetaria'] ? 'Dinero' : 'Insumos' ?></span>
                        </div>
                        <h3><a href="#"><?= escapar($c['titulo']) ?></a></h3>
                        <p class="campana-autor">
                            Creada por <?= escapar($c['autor']) ?>
                            <span class="insignia nivel-<?= $c['nivel'] ?>"><?= $nombresNivel[$c['nivel']] ?></span>
                        </p>
                        <div>
                            <div class="progreso" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"
                                 aria-label="Progreso de la campaña">
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
    </section>
</div>

<?php require __DIR__ . '/parciales/pie.php'; ?>
