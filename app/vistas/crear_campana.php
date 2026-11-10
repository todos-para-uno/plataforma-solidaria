<?php
$tituloPagina = 'Crear campaña'; $paginaActual = 'crear_campana'; $estilosPagina = ['crear_campana.css', 'punto_entrega.css'];
require __DIR__ . '/parciales/cabecera.php';
$dibujarInsumo = static function ($indice, array $fila) use ($categoriasInsumos): void { ?>
<fieldset class="crear-insumo-fila"><legend>Insumo</legend>
<div class="campo"><label for="insumo-categoria-<?= $indice ?>">Categoría del insumo</label><select id="insumo-categoria-<?= $indice ?>" name="insumos[<?= $indice ?>][categoria]" required><option value="">Elegí una categoría</option>
<?php foreach ($categoriasInsumos as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (string) ($fila['categoria'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>><?= escapar($cat['nombre']) ?></option><?php endforeach; ?></select></div>
<div class="campo"><label for="insumo-nombre-<?= $indice ?>">Insumo y unidad</label><input id="insumo-nombre-<?= $indice ?>" name="insumos[<?= $indice ?>][nombre]" maxlength="100" list="insumos-sugeridos" placeholder="Ej.: Paquetes de arroz (1 kg)" value="<?= escapar($fila['nombre'] ?? '') ?>" required></div>
<div class="campo"><label for="insumo-cantidad-<?= $indice ?>">Cantidad que necesitás</label><input type="number" id="insumo-cantidad-<?= $indice ?>" name="insumos[<?= $indice ?>][cantidad]" min="0.01" max="9999999999.99" step="0.01" value="<?= escapar($fila['cantidad'] ?? '') ?>" required></div>
<button type="button" class="boton boton-secundario boton-chico" data-quitar-insumo>Quitar insumo</button>
</fieldset>
<?php };
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<script src="<?= escapar($rutaAssets) ?>/js/crear_campana_imagenes.js" defer></script>
<section class="portada-cuenta" aria-labelledby="titulo-crear-campana">
    <div class="contenedor">
        <div class="tarjeta portada-cuenta-tarjeta">
            <h1 id="titulo-crear-campana">Crear campaña</h1>
            <p class="bajada">Contá tu historia, elegí qué ayuda necesitás y definí tus objetivos.</p>
        </div>
    </div>
</section>
<section class="seccion" aria-label="Formulario de creación de campaña"><div class="contenedor">
<?php if ($errores): ?><div class="alerta alerta-error crear-editor-errores" role="alert"><p>No pudimos publicar la campaña:</p><ul><?php foreach ($errores as $error): ?><li><?= escapar($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="crear-editor">
<nav class="crear-editor-menu tarjeta" aria-label="Secciones de tu campaña" hidden>
    <p class="etiqueta-seccion">Tu campaña</p>
    <p class="campo-ayuda">Completá las secciones en el orden que prefieras.</p>
    <ol>
        <li><a href="#crear-historia" data-editor-enlace="historia" aria-current="location">Historia <span data-editor-estado="historia">Pendiente</span></a></li>
        <li><a href="#crear-fotos" data-editor-enlace="fotos">Fotos <span data-editor-estado="fotos">Opcional</span></a></li>
        <li><a href="#crear-objetivos" data-editor-enlace="objetivos">Objetivos <span data-editor-estado="objetivos">Pendiente</span></a></li>
        <li><a href="#bloque-ubicacion" data-editor-enlace="entrega">Entrega <span data-editor-estado="entrega">Pendiente</span></a></li>
        <li><a href="#crear-revision" data-editor-enlace="revision">Revisión <span>Antes de publicar</span></a></li>
    </ol>
    <p class="campo-ayuda" data-editor-progreso aria-live="polite" aria-atomic="true"></p>
</nav>

<form class="crear-formulario" data-editor-errores="<?= $errores ? 'true' : 'false' ?>" action="<?= escapar($rutas['crear_campana']) ?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>"><input type="hidden" name="clave" value="<?= escapar($clave) ?>">
<section class="tarjeta crear-tarjeta crear-panel" id="crear-historia" data-editor-panel="historia" aria-labelledby="crear-historia-titulo"><h2 id="crear-historia-titulo" tabindex="-1">Contá tu historia</h2>
<div class="campo"><label for="crear-nombre">Nombre de la campaña</label><input id="crear-nombre" name="nombre" maxlength="100" value="<?= escapar($valor('nombre')) ?>" required></div>
<div class="campo"><label for="crear-descripcion">Descripción</label><textarea id="crear-descripcion" name="descripcion" maxlength="5000" required><?= escapar($valor('descripcion')) ?></textarea></div>
<div class="campo"><label for="crear-categoria">Categoría de la campaña</label><select id="crear-categoria" name="categoria" required><option value="">Elegí una categoría</option>
<?php foreach ($categorias as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= $valor('categoria') === (string)$cat['id'] ? 'selected' : '' ?>><?= escapar($cat['nombre']) ?></option><?php endforeach; ?></select></div>
<div class="campo"><label for="crear-tipo">¿Qué necesitás?</label><select id="crear-tipo" name="tipo_ayuda" required><option value="insumos" <?= $tipoAyuda === 'insumos' ? 'selected' : '' ?>>Insumos</option><option value="dinero" <?= $tipoAyuda === 'dinero' ? 'selected' : '' ?>>Dinero</option></select></div>
</section>
                <!-- Imágenes de la campaña: arrastrar o elegir, con vista previa -->
                <section class="tarjeta crear-tarjeta crear-panel" id="crear-fotos" data-editor-panel="fotos" aria-labelledby="crear-imagenes-titulo">
                    <h2 id="crear-imagenes-titulo" tabindex="-1">Imágenes</h2>
                    <div class="campo campo-imagenes" data-carga-imagenes
                         data-max-imagenes="<?= (int) $maxImagenes ?>"
                         data-max-bytes="<?= (int) $tamanoMaximoImagen ?>"
                         data-max-total="<?= (int) $tamanoMaximoTotal ?>">
                        <span class="campo-imagenes-etiqueta" id="crear-imagenes-etiqueta">Fotos de la campaña <span class="texto-secundario">(opcional)</span></span>

                        <label class="zona-carga" for="crear-imagenes" data-zona-carga>
                            <svg class="zona-carga-icono" viewBox="0 0 48 48" width="48" height="48" aria-hidden="true" focusable="false">
                                <rect x="5" y="9" width="38" height="30" rx="4" fill="none" stroke="currentColor" stroke-width="2.5"/>
                                <circle cx="16" cy="19" r="3.5" fill="var(--sol)" stroke="currentColor" stroke-width="2"/>
                                <path d="M8 35l10-10 7 7 5-5 10 10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
                            </svg>
                            <span class="zona-carga-titulo" data-zona-titulo>Arrastrá tus fotos acá</span>
                            <span class="zona-carga-texto">o <span class="zona-carga-enlace">elegilas desde tu dispositivo</span></span>
                            <span class="zona-carga-formatos">PNG, JPG o WEBP · hasta <?= escapar($textoTamanoImagen) ?> cada una · máximo <?= (int) $maxImagenes ?></span>
                        </label>
                        <input id="crear-imagenes" class="solo-lectores" name="imagenes[]" type="file" multiple
                               accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                               aria-labelledby="crear-imagenes-etiqueta"
                               aria-describedby="crear-imagenes-ayuda crear-imagenes-error">

                        <div class="imagenes-estado">
                            <span class="imagenes-puntos" aria-hidden="true">
                                <?php for ($i = 0; $i < (int) $maxImagenes; $i++): ?><span></span><?php endfor; ?>
                            </span>
                            <span class="imagenes-contador" data-contador aria-live="polite">0 de <?= (int) $maxImagenes ?> imágenes</span>
                        </div>

                        <ul class="imagenes-previa" data-previa aria-label="Imágenes seleccionadas"></ul>

                        <span class="campo-error" id="crear-imagenes-error" data-error-imagenes role="alert" hidden></span>
                        <span class="campo-ayuda" id="crear-imagenes-ayuda">La primera foto se muestra como portada. Evitá publicar datos personales o rostros sin permiso.</span>
                    </div>
                </section>


<section class="crear-panel" id="crear-objetivos" data-editor-panel="objetivos" aria-label="Objetivos de tu campaña">
<fieldset id="bloque-insumos" class="tarjeta crear-tarjeta" aria-labelledby="titulo-insumos" <?= $tipoAyuda !== 'insumos' ? 'hidden disabled' : '' ?>><h2 id="titulo-insumos">Insumos que necesitás</h2>
<p class="crear-insumos-ayuda">Agregá cada insumo con su cantidad. Indicá la unidad en el nombre, por ejemplo “Leche (litros)” o “Frazadas (unidades)”.</p>
<div id="lista-insumos"><?php foreach ($filas as $indice => $fila) $dibujarInsumo($indice,$fila); ?></div>
<button type="button" class="boton boton-secundario" id="agregar-insumo">Agregar otro insumo</button><p class="campo-ayuda" id="estado-insumos" role="status">Podés agregar hasta 20 insumos.</p>
</fieldset>
<fieldset id="bloque-dinero" class="tarjeta crear-tarjeta" aria-labelledby="titulo-dinero" <?= $tipoAyuda !== 'dinero' ? 'hidden disabled' : '' ?>><h2 id="titulo-dinero">Objetivo de dinero</h2>
<div class="campo"><label for="crear-objetivo">Monto total en pesos</label><input type="number" id="crear-objetivo" name="objetivo" min="0.01" max="9999999999.99" step="0.01" value="<?= escapar($valor('objetivo')) ?>" required></div>
<div class="campo"><label for="crear-cbu">CBU o CVU</label><input id="crear-cbu" name="cbu" inputmode="numeric" pattern="[0-9]{22}" maxlength="22" value="<?= escapar($valor('cbu')) ?>" required></div>
</fieldset>
</section>
<fieldset id="bloque-ubicacion" data-editor-panel="entrega" class="tarjeta crear-tarjeta crear-panel" aria-labelledby="titulo-ubicacion" <?= $tipoAyuda !== 'insumos' ? 'hidden disabled' : '' ?>><h2 id="titulo-ubicacion">Punto único de entrega</h2><p>Todos los insumos pedidos se reciben en este lugar. La ubicación será pública.</p>
<div class="campo"><label for="provincia">Provincia</label><select id="provincia" name="provincia" required><option value="">Elegí una provincia</option><?php foreach ($catalogo as $prov => $localidades): ?><option value="<?= escapar($prov) ?>" <?= $valor('provincia') === $prov ? 'selected' : '' ?>><?= escapar($prov) ?></option><?php endforeach; ?></select></div>
<div class="campo"><label for="localidad">Localidad</label><select id="localidad" name="localidad" required><option value="">Elegí una localidad</option><?php foreach ($catalogo[$valor('provincia')] ?? [] as $loc): ?><option value="<?= escapar($loc) ?>" <?= $valor('localidad') === $loc ? 'selected' : '' ?>><?= escapar($loc) ?></option><?php endforeach; ?></select></div>
<?php require __DIR__ . '/parciales/buscar_punto_entrega.php'; ?>
<div class="campo"><label for="crear-direccion">Dirección, horarios o referencia</label><input id="crear-direccion" name="ubicacion_texto" maxlength="150" value="<?= escapar($valor('ubicacion_texto')) ?>"></div>
<p>El punto seleccionado será el lugar de entrega para todos los insumos.</p><div id="mapa-entrega" class="mapa-entrega" aria-label="Elegir punto de entrega"></div>
<input type="hidden" id="latitud" name="lat" value="<?= escapar($valor('lat')) ?>">
<input type="hidden" id="longitud" name="lng" value="<?= escapar($valor('lng')) ?>">
</fieldset>
<section class="tarjeta crear-tarjeta crear-panel" id="crear-revision" data-editor-panel="revision" aria-labelledby="crear-revision-titulo">
    <h2 id="crear-revision-titulo" tabindex="-1">Revisá tu campaña</h2>
    <p>Comprobá tu historia, los objetivos y las fotos antes de compartirlos con la comunidad.</p>
    <div data-editor-revision hidden>
        <dl class="crear-revision-datos" data-editor-resumen></dl>
        <div class="alerta alerta-aviso" data-editor-pendientes>
            <p>Te falta completar:</p>
            <ul data-editor-faltantes></ul>
        </div>
        <p class="alerta alerta-exito" data-editor-listo hidden>Los datos obligatorios están completos. Ya podés publicar tu campaña.</p>
    </div>
    <button class="boton boton-ancho" id="crear-publicar" type="submit">Publicar campaña</button>
</section>
<div class="crear-editor-acciones" hidden>
    <button class="boton boton-secundario" type="button" data-editor-anterior>Anterior</button>
    <button class="boton" type="button" data-editor-siguiente>Continuar</button>
</div>
</form>
<aside class="crear-editor-previa" aria-label="Vista previa de tu campaña" hidden>
    <details class="crear-previa-panel" open>
        <summary>Vista previa de tu campaña</summary>
        <article class="tarjeta campana">
            <img class="campana-imagen" data-previa-imagen alt="Portada de tu campaña" hidden>
            <div class="campana-imagen crear-previa-sin-foto" data-previa-sin-foto><span>Tu foto de portada</span></div>
            <div class="campana-cuerpo">
                <div class="campana-datos"><span class="insignia" data-previa-tipo>Insumos</span><span class="insignia" data-previa-categoria hidden></span></div>
                <h3 data-previa-nombre>Nombre de tu campaña</h3>
                <p class="texto-secundario crear-previa-descripcion" data-previa-descripcion>Tu historia aparecerá acá mientras la escribís.</p>
                <p class="crear-previa-objetivo" data-previa-objetivo>Definí los insumos que necesitás.</p>
                <p class="campo-ayuda" data-previa-entrega>Elegí el punto de entrega.</p>
            </div>
        </article>
        <p class="campo-ayuda">Esta es una vista previa. Tu campaña se hará pública cuando la publiques.</p>
    </details>
</aside>
<datalist id="insumos-sugeridos"><?php foreach ($sugerencias as $sugerencia): ?><option value="<?= escapar($sugerencia) ?>"><?php endforeach; ?></datalist>
<template id="plantilla-insumo"><?php $dibujarInsumo('__INDICE__',[]); ?></template>
<script type="application/json" id="catalogo-entrega"><?= json_encode($catalogo,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<script src="<?= escapar($rutaAssets) ?>/js/punto-entrega.js" defer></script>
<script src="<?= escapar($rutaAssets) ?>/js/crear-campana.js" defer></script>
<script src="<?= escapar($rutaAssets) ?>/js/crear-campana-editor.js" defer></script>
</div></div></section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
