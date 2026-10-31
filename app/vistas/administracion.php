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

        <section class="admin-panel" id="gestion-usuarios" aria-labelledby="usuarios-titulo"
                 data-api="<?= escapar($rutaBase) ?>/index.php"
                 data-csrf="<?= escapar($csrfToken) ?>">
            <div class="admin-tabs" role="tablist" aria-label="Secciones administrativas">
                <button type="button" role="tab" id="tab-usuarios" aria-selected="true" aria-controls="panel-usuarios">Usuarios</button>
                <button type="button" role="tab" id="tab-mensajes" aria-selected="false" aria-controls="panel-mensajes">Mensajes</button>
            </div>
            <div id="panel-usuarios" role="tabpanel" aria-labelledby="tab-usuarios">
                <div class="admin-controles">
                    <label>Buscar por nombre o correo
                        <input id="admin-busqueda" type="search" maxlength="150" autocomplete="off" placeholder="Nombre o correo electrónico">
                    </label>
                    <label>Usuarios por página
                        <select id="admin-limite"><option>10</option><option selected>15</option><option>25</option><option>50</option></select>
                    </label>
                </div>
                <p id="admin-estado" role="status" aria-live="polite">Cargando usuarios…</p>
                <div id="admin-lista" class="admin-lista" aria-label="Listado de usuarios"></div>
                <nav class="admin-paginacion" aria-label="Páginas de usuarios">
                    <span id="admin-pagina" aria-live="polite">Página 1 de 1</span>
                    <div class="admin-paginacion-botones">
                        <button type="button" id="admin-primera" title="Primera página" aria-label="Primera página" hidden>«</button>
                        <button type="button" id="admin-salto-atras" title="Retroceder cinco páginas" aria-label="Retroceder cinco páginas" hidden>−5</button>
                        <button type="button" id="admin-anterior">Anterior</button>
                        <button type="button" id="admin-siguiente">Siguiente</button>
                        <button type="button" id="admin-salto-adelante" title="Avanzar cinco páginas" aria-label="Avanzar cinco páginas" hidden>+5</button>
                        <button type="button" id="admin-ultima" title="Última página" aria-label="Última página" hidden>»</button>
                    </div>
                </nav>
            </div>
            <div id="panel-mensajes" role="tabpanel" aria-labelledby="tab-mensajes" hidden>
                <p>La gestión de mensajes se incorporará en el siguiente incremento.</p>
            </div>
        </section>
        <dialog id="admin-dialogo" aria-labelledby="admin-dialogo-titulo">
            <form id="admin-formulario" method="dialog">
                <h2 id="admin-dialogo-titulo">Actualizar contraseña</h2>
                <p id="admin-dialogo-descripcion"></p>
                <input type="hidden" name="id_usuario" id="admin-id-usuario">
                <label>Nueva contraseña <input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
                <label>Confirmar contraseña <input type="password" name="confirmacion" minlength="8" autocomplete="new-password" required></label>
                <p id="admin-dialogo-error" role="alert"></p>
                <div class="admin-dialogo-acciones">
                    <button type="button" id="admin-cancelar">Cancelar</button>
                    <button type="submit" id="admin-confirmar">Confirmar</button>
                </div>
            </form>
        </dialog>
        <dialog id="admin-baneo-dialogo" aria-labelledby="admin-baneo-titulo">
            <form id="admin-baneo-formulario" method="dialog">
                <h2 id="admin-baneo-titulo">Gestionar baneo</h2>
                <p id="admin-baneo-descripcion"></p>
                <input type="hidden" name="id_usuario" id="admin-baneo-usuario">
                <input type="hidden" name="id_baneo" id="admin-baneo-id">
                <label id="admin-baneo-motivo-label">Motivo
                    <textarea id="admin-baneo-motivo" name="motivo" maxlength="500" rows="3" required></textarea>
                </label>
                <label id="admin-baneo-fecha-label">Finalización opcional
                    <input id="admin-baneo-fecha" type="datetime-local" name="fecha_fin">
                </label>
                <p id="admin-baneo-error" role="alert"></p>
                <div class="admin-dialogo-acciones">
                    <button type="button" id="admin-baneo-cancelar">Cancelar</button>
                    <button type="submit" id="admin-baneo-confirmar">Confirmar</button>
                </div>
            </form>
        </dialog>
        <dialog id="admin-actividad-dialogo" aria-labelledby="admin-actividad-titulo">
            <h2 id="admin-actividad-titulo">Actividad del usuario</h2>
            <p id="admin-actividad-estado" role="status" aria-live="polite"></p>
            <div id="admin-actividad-contenido"></div>
            <div class="admin-dialogo-acciones"><button type="button" id="admin-actividad-cerrar">Cerrar</button></div>
        </dialog>
        <script src="<?= escapar($rutaAssets) ?>/js/administracion.js" defer></script>
    </div>
</section>

<?php require __DIR__ . '/parciales/pie.php'; ?>
