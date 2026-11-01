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
<section class="seccion"><div class="contenedor"><div class="crear-interior">
<div class="seccion-encabezado"><h1>Crear campaña</h1><p class="bajada">Contá qué necesitás y definí un objetivo para cada insumo.</p></div>
<?php if ($errores): ?><div class="alerta alerta-error" role="alert"><p>No pudimos publicar la campaña:</p><ul><?php foreach ($errores as $error): ?><li><?= escapar($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="crear-formulario" action="<?= escapar($rutas['crear_campana']) ?>" method="post">
<input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>"><input type="hidden" name="clave" value="<?= escapar($clave) ?>">
<section class="tarjeta crear-tarjeta"><h2>Información básica</h2>
<div class="campo"><label for="crear-nombre">Nombre de la campaña</label><input id="crear-nombre" name="nombre" maxlength="100" value="<?= escapar($valor('nombre')) ?>" required></div>
<div class="campo"><label for="crear-descripcion">Descripción</label><textarea id="crear-descripcion" name="descripcion" maxlength="5000" required><?= escapar($valor('descripcion')) ?></textarea></div>
<div class="campo"><label for="crear-categoria">Categoría de la campaña</label><select id="crear-categoria" name="categoria" required><option value="">Elegí una categoría</option>
<?php foreach ($categorias as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= $valor('categoria') === (string)$cat['id'] ? 'selected' : '' ?>><?= escapar($cat['nombre']) ?></option><?php endforeach; ?></select></div>
<div class="campo"><label for="crear-tipo">¿Qué necesitás?</label><select id="crear-tipo" name="tipo_ayuda" required><option value="insumos" <?= $tipoAyuda === 'insumos' ? 'selected' : '' ?>>Insumos</option><option value="dinero" <?= $tipoAyuda === 'dinero' ? 'selected' : '' ?>>Dinero</option></select></div>
</section>
<fieldset id="bloque-insumos" class="tarjeta crear-tarjeta" aria-labelledby="titulo-insumos" <?= $tipoAyuda !== 'insumos' ? 'hidden disabled' : '' ?>><h2 id="titulo-insumos">Insumos que necesitás</h2>
<p class="crear-insumos-ayuda">Agregá cada insumo con su cantidad. Indicá la unidad en el nombre, por ejemplo “Leche (litros)” o “Frazadas (unidades)”.</p>
<div id="lista-insumos"><?php foreach ($filas as $indice => $fila) $dibujarInsumo($indice,$fila); ?></div>
<button type="button" class="boton boton-secundario" id="agregar-insumo">Agregar otro insumo</button><p class="campo-ayuda" id="estado-insumos" role="status">Podés agregar hasta 20 insumos.</p>
</fieldset>
<fieldset id="bloque-dinero" class="tarjeta crear-tarjeta" aria-labelledby="titulo-dinero" <?= $tipoAyuda !== 'dinero' ? 'hidden disabled' : '' ?>><h2 id="titulo-dinero">Objetivo de dinero</h2>
<div class="campo"><label for="crear-objetivo">Monto total en pesos</label><input type="number" id="crear-objetivo" name="objetivo" min="0.01" max="9999999999.99" step="0.01" value="<?= escapar($valor('objetivo')) ?>" required></div>
<div class="campo"><label for="crear-cbu">CBU o CVU</label><input id="crear-cbu" name="cbu" inputmode="numeric" pattern="[0-9]{22}" maxlength="22" value="<?= escapar($valor('cbu')) ?>" required></div>
</fieldset>
<fieldset id="bloque-ubicacion" class="tarjeta crear-tarjeta" aria-labelledby="titulo-ubicacion" <?= $tipoAyuda !== 'insumos' ? 'hidden disabled' : '' ?>><h2 id="titulo-ubicacion">Punto único de entrega</h2><p>Todos los insumos pedidos se reciben en este lugar. La ubicación será pública.</p>
<div class="campo"><label for="provincia">Provincia</label><select id="provincia" name="provincia" required><option value="">Elegí una provincia</option><?php foreach ($catalogo as $prov => $localidades): ?><option value="<?= escapar($prov) ?>" <?= $valor('provincia') === $prov ? 'selected' : '' ?>><?= escapar($prov) ?></option><?php endforeach; ?></select></div>
<div class="campo"><label for="localidad">Localidad</label><select id="localidad" name="localidad" required><option value="">Elegí una localidad</option><?php foreach ($catalogo[$valor('provincia')] ?? [] as $loc): ?><option value="<?= escapar($loc) ?>" <?= $valor('localidad') === $loc ? 'selected' : '' ?>><?= escapar($loc) ?></option><?php endforeach; ?></select></div>
<?php require __DIR__ . '/parciales/buscar_punto_entrega.php'; ?>
<div class="campo"><label for="crear-direccion">Dirección, horarios o referencia</label><input id="crear-direccion" name="ubicacion_texto" maxlength="150" value="<?= escapar($valor('ubicacion_texto')) ?>"></div>
<p>El punto seleccionado será el lugar de entrega para todos los insumos.</p><div id="mapa-entrega" class="mapa-entrega" aria-label="Elegir punto de entrega"></div>
<input type="hidden" id="latitud" name="lat" value="<?= escapar($valor('lat')) ?>">
<input type="hidden" id="longitud" name="lng" value="<?= escapar($valor('lng')) ?>">
</fieldset>
<button class="boton boton-ancho" type="submit">Publicar campaña</button>
</form>
<datalist id="insumos-sugeridos"><?php foreach ($sugerencias as $sugerencia): ?><option value="<?= escapar($sugerencia) ?>"><?php endforeach; ?></datalist>
<template id="plantilla-insumo"><?php $dibujarInsumo('__INDICE__',[]); ?></template>
<script type="application/json" id="catalogo-entrega"><?= json_encode($catalogo,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<script src="<?= escapar($rutaAssets) ?>/js/punto-entrega.js" defer></script>
<script src="<?= escapar($rutaAssets) ?>/js/crear-campana.js" defer></script>
</div></div></section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
