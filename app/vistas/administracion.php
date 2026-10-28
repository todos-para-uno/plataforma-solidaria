<?php
/*
 * Vista del panel de administración: por ahora, tres números de la plataforma.
 *
 * Datos que recibe de AdministracionController:
 *   $estadisticas  array  usuarios, activas y finalizadas (enteros).
 *   $usuarioActual array  nombre y admin, para la cabecera.
 *   $csrfToken     string Para el botón Salir de la cabecera.
 */
$tituloPagina = 'Administración';
$paginaActual = 'administracion';
$estilosPagina = ['administracion.css'];

require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion" aria-labelledby="titulo-administracion">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h1 id="titulo-administracion">Administración</h1>
            <p class="bajada">Así está hoy la plataforma.</p>
        </div>

        <ul class="administracion-estadisticas" aria-label="Estadísticas de la plataforma">
            <li class="tarjeta dato">
                <span class="dato-cifra"><?= (int) $estadisticas['usuarios'] ?></span>
                <span class="dato-etiqueta">Usuarios registrados</span>
            </li>
            <li class="tarjeta dato">
                <span class="dato-cifra"><?= (int) $estadisticas['activas'] ?></span>
                <span class="dato-etiqueta">Campañas activas</span>
            </li>
            <li class="tarjeta dato">
                <span class="dato-cifra"><?= (int) $estadisticas['finalizadas'] ?></span>
                <span class="dato-etiqueta">Campañas finalizadas</span>
            </li>
        </ul>
    </div>
</section>

<?php require __DIR__ . '/parciales/pie.php'; ?>
