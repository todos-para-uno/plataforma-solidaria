<?php
$tituloPagina = 'Punto de entrega'; $paginaActual = 'mis_campanas'; $estilosPagina = ['campana.css'];
require __DIR__ . '/parciales/cabecera.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<section class="seccion"><div class="contenedor contenedor-detalle"><article class="tarjeta cuerpo-detalle">
<h1>Punto de entrega</h1><h2><?= escapar($campana->getNombre()) ?></h2>
<p>Elegí un único lugar para recibir todos los insumos de esta campaña. La ubicación y la referencia serán públicas.</p>
<?php if ($error): ?><div class="alerta alerta-error" role="alert"><?= escapar($error) ?></div><?php endif; ?>
<form method="post" action="<?= escapar($rutaBase) ?>/index.php?ruta=punto_entrega&amp;id=<?= $id ?>">
<input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
<div class="campo"><label for="provincia">Provincia</label><select id="provincia" name="provincia" required><option value="">Seleccioná una provincia</option>
<?php foreach ($catalogo as $prov => $localidades): ?><option <?= ($punto['provincia'] ?? '') === $prov ? 'selected' : '' ?> value="<?= escapar($prov) ?>"><?= escapar($prov) ?></option><?php endforeach; ?></select></div>
<div class="campo"><label for="localidad">Localidad</label><select id="localidad" name="localidad" required><option value="">Seleccioná una localidad</option>
<?php foreach ($catalogo[$punto['provincia'] ?? ''] ?? [] as $loc): ?><option <?= ($punto['localidad'] ?? '') === $loc ? 'selected' : '' ?> value="<?= escapar($loc) ?>"><?= escapar($loc) ?></option><?php endforeach; ?></select></div>
<div class="campo"><label for="referencia">Dirección, horarios o referencia de entrega</label><input id="referencia" name="referencia" maxlength="150" value="<?= escapar($punto['referencia'] ?? '') ?>"></div>
<p>Hacé clic en el mapa para elegir el lugar. También podés ingresar sus coordenadas.</p>
<div id="mapa-entrega" class="mapa-entrega" aria-label="Mapa del punto de entrega"></div>
<div class="campo"><label for="latitud">Latitud</label><input type="number" step="0.000001" min="-90" max="90" id="latitud" name="latitud" required value="<?= escapar((string) ($punto['latitud'] ?? '')) ?>"></div>
<div class="campo"><label for="longitud">Longitud</label><input type="number" step="0.000001" min="-180" max="180" id="longitud" name="longitud" required value="<?= escapar((string) ($punto['longitud'] ?? '')) ?>"></div>
<button class="boton" type="submit">Guardar punto de entrega</button>
<a class="boton boton-secundario" href="<?= escapar($rutaBase) ?>/index.php?ruta=campana&amp;id=<?= $id ?>">Volver a la campaña</a>
</form></article></div></section>
<script type="application/json" id="catalogo-entrega"><?= json_encode($catalogo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script src="<?= escapar($rutaAssets) ?>/js/punto-entrega.js" defer></script>
<?php require __DIR__ . '/parciales/pie.php'; ?>
