<?php
/** Respuestas sobre el funcionamiento de la plataforma solidaria. */
$tituloPagina = 'Cómo funciona — todospara.uno';
$paginaActual = 'como_funciona';
$usuarioActual = $usuarioActual ?? null;
$estilosPagina = ['preguntas_frecuentes.css'];

// Ilustraciones de contorno decorativas: cada pregunta conserva su título textual.
$preguntas = [
    [
        'tema' => 'Formas de ayudar',
        'titulo' => '¿Qué diferencia hay entre campañas de dinero y de insumos?',
        'respuesta' => 'Las campañas de dinero buscan recaudar fondos a través de transferencias bancarias informadas mediante un CBU. Las campañas de insumos buscan recaudar elementos físicos específicos, como ropa, alimentos o útiles escolares, detallados en la publicación.',
        'dibujo' => '<path d="M25 51h78v48H25zM20 37h88v14H20zM64 37v62"/><path d="M64 37C44 39 34 31 38 23c5-10 21-2 26 14ZM64 37c20 2 30-6 26-14-5-10-21-2-26 14Z"/><path class="trazo-acento" d="m44 73 8 8 14-14M84 72v12m-6-6h12"/>',
    ],
    [
        'tema' => 'Confianza en comunidad',
        'titulo' => '¿Cómo se definen los niveles de confianza de los usuarios?',
        'respuesta' => 'Los niveles de confianza (Blanco, Celeste, Azul y Violeta) indican el grado de validación del usuario en la plataforma. Se van actualizando a medida que ingresás tu documentación oficial y completás con éxito donaciones o campañas.',
        'dibujo' => '<path d="M64 16c13 10 27 14 38 16v29c0 25-17 42-38 52-21-10-38-27-38-52V32c11-2 25-6 38-16Z"/><path class="trazo-acento" d="m45 62 13 13 26-28"/>',
    ],
    [
        'tema' => 'Solidaridad sin costo',
        'titulo' => '¿Tiene algún costo utilizar la plataforma?',
        'respuesta' => 'No, todospara.uno es un proyecto solidario sin fines de lucro desarrollado como iniciativa académica. Su uso es completamente gratuito para creadores y colaboradores.',
        'dibujo' => '<path d="M64 86 31 55c-21-22 9-47 33-23 24-24 54 1 33 23L64 86Z"/><path d="M17 82h16l17 13h30l21-16c10-7 18 4 10 12l-26 21H48L17 98Z"/><path class="trazo-acento" d="M64 7v8M16 29l7 5m89-5-7 5"/>',
    ],
    [
        'tema' => 'Cerca de quienes necesitan',
        'titulo' => '¿Se permiten realizar campañas por fuera de la Argentina?',
        'respuesta' => 'Actualmente, todospara.uno facilita la creación de campañas solo dentro de la Argentina.',
        'dibujo' => '<path d="M64 105S28 72 28 48a36 36 0 0 1 72 0c0 24-36 57-36 57Z"/><circle class="trazo-acento" cx="64" cy="48" r="13"/><path d="M34 97c-17 3-21 9-13 14 16 10 70 10 86 0 8-5 4-11-13-14"/>',
    ],
    [
        'tema' => 'Sobre las transferencias',
        'titulo' => '¿Las transferencias de dinero son manejadas por la plataforma?',
        'respuesta' => 'No, todospara.uno no procesa el intercambio de dinero necesario para realizar donaciones. Para eso, utilizamos los servicios de Mercado Pago.',
        'dibujo' => '<rect x="15" y="35" width="98" height="62" rx="9"/><path d="M15 52h98M29 80h19"/><path class="trazo-acento" d="M38 18h49m-9-9 9 9-9 9M90 113H41m9-9-9 9 9 9"/>',
    ],
];
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion ayuda-portada" aria-labelledby="titulo-ayuda">
    <div class="contenedor">
        <p class="ayuda-etiqueta">Todospara.uno · Una mano, muchas formas de ayudar</p>
        <h1 id="titulo-ayuda">Ayudar empieza por entender cómo.</h1>
        <p class="bajada">Encontrá las respuestas a las preguntas más comunes y conocé cómo funciona nuestra comunidad solidaria.</p>
    </div>
</section>

<section class="seccion ayuda-preguntas" aria-labelledby="titulo-preguntas">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h2 id="titulo-preguntas">Tus preguntas, paso a paso</h2>
            <p class="bajada">Lo que necesitás saber para empezar a participar.</p>
        </div>
        <div class="ayuda-lista">
            <?php foreach ($preguntas as $numero => $pregunta): ?>
                <article class="ayuda-pregunta" aria-labelledby="pregunta-<?= $numero + 1 ?>">
                    <div class="ayuda-ilustracion" aria-hidden="true">
                        <svg viewBox="0 0 128 128" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <?= $pregunta['dibujo'] /* SVG estático definido en esta vista. */ ?>
                        </svg>
                    </div>
                    <div class="ayuda-respuesta">
                        <p class="ayuda-etiqueta"><?= escapar($pregunta['tema']) ?></p>
                        <h3 id="pregunta-<?= $numero + 1 ?>"><?= escapar($pregunta['titulo']) ?></h3>
                        <p><?= escapar($pregunta['respuesta']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="seccion ayuda-contacto" aria-labelledby="titulo-ayuda-contacto">
    <div class="contenedor ayuda-contacto-interior">
        <div>
            <h2 id="titulo-ayuda-contacto">Cada pregunta también nos acerca.</h2>
            <p>Si te quedó alguna duda, escribinos. Estamos para ayudarte.</p>
        </div>
        <a class="boton boton-secundario" href="<?= escapar($rutas['contacto']) ?>">Contactanos</a>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
