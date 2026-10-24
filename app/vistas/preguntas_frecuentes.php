<?php
/**
 * Vista de preguntas frecuentes (FAQ) en todospara.uno
 */

$tituloPagina  = 'Preguntas frecuentes — todospara.uno';
$paginaActual  = 'ayuda';
$usuarioActual = null;
$estilosPagina = ['preguntas_frecuentes.css'];

require __DIR__ . '/parciales/cabecera.php';
?>


<!-- Encabezado general -->
<section class="seccion">
    <div class="contenedor seccion-encabezado">
        <h1>Preguntas frecuentes</h1>
        <p class="bajada">Encontrá las respuestas a las dudas más comunes sobre cómo funciona nuestra plataforma solidaria.</p>
    </div>
</section>

<section class="seccion seccion-papel">
    <div class="contenedor">
        <h2>1. ¿Qué diferencia hay entre campañas de dinero y de insumos?</h2>
        <p>Las campañas de dinero buscan recaudar fondos a través de transferencias bancarias informadas mediante un CBU. Las campañas de insumos buscan recaudar elementos físicos específicos (como ropa, alimentos o útiles escolares) detallados en la publicación.</p>
    </div>
</section>

<section class="seccion">
    <div class="contenedor">
        <h2>2. ¿Cómo se definen los niveles de confianza de los usuarios?</h2>
        <p>Los niveles de confianza (Blanco, Celeste, Azul y Violeta) indican el grado de validación del usuario en la plataforma. Se van actualizando a medida que ingresás tu documentación oficial y completás con éxito donaciones o campañas.</p>
    </div>
</section>

<section class="seccion seccion-papel">
    <div class="contenedor">
        <h2>3. ¿Tiene algún costo utilizar la plataforma?</h2>
        <p>No, todospara.uno es un proyecto solidario sin fines de lucro desarrollado como iniciativa académica. Su uso es completamente gratuito para creadores y colaboradores.</p>
    </div>
</section>

<section class="seccion">
    <div class="contenedor">
        <h2>4. ¿Se permiten realizar campañas por fuera de la Argentina?</h2>
        <p>Actualmente, Todospara.uno facilita la creacion de campañas solo dentro de la Argentina.</p>
    </div>
</section>

<section class="seccion seccion-papel">
    <div class="contenedor">
        <h2>5. Las transferenciasde dinero ¿Son manejadas por la plataforma?</h2>
        <p>No, Todospara.uno no procesa el intercambio de dinero necesario para realizar donaciones. Para eso, utilizamos los servicios de Mercado Pago.</p>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>