<?php
$tituloPagina = 'Insumos recibidos'; $paginaActual = 'mis_campanas'; $estilosPagina = ['campana.css'];
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion"><div class="contenedor contenedor-detalle"><article class="tarjeta cuerpo-detalle">
<h1>Insumos recibidos</h1><h2><?= escapar($campana->getNombre()) ?></h2>
<p>Registrá los insumos que ya llegaron al punto de entrega. La cantidad se sumará al objetivo seleccionado.</p>
<?php if ($error): ?><div class="alerta alerta-error" role="alert"><?= escapar($error) ?></div><?php endif; ?>
<?php if ($aviso): ?><div class="alerta alerta-aviso" role="status"><?= escapar($aviso) ?></div><?php endif; ?>
<?php if ($campana->getEstado() === EstadoCamp::Activa && $objetivos): ?>
<form method="post" action="<?= escapar($rutaBase) ?>/index.php?ruta=recepcion_insumos&amp;id=<?= $id ?>">
<input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>"><input type="hidden" name="clave" value="<?= escapar($clave) ?>">
<div class="campo"><label for="correo">Correo de la cuenta del donante</label><input type="email" id="correo" name="correo" required maxlength="150" value="<?= escapar(is_string($_POST['correo'] ?? null) ? $_POST['correo'] : '') ?>"><p>El donante debe estar registrado en la plataforma.</p></div>
<div class="campo"><label for="id_objetivo">Insumo recibido</label><select id="id_objetivo" name="id_objetivo" required>
<?php foreach ($objetivos as $o): if (!$o['id_categoria_insumos']) continue; ?><option value="<?= (int) $o['id'] ?>" <?= (is_string($_POST['id_objetivo'] ?? null) ? $_POST['id_objetivo'] : '') === (string) $o['id'] ? 'selected' : '' ?>><?= escapar($o['subcategoria_nombre'] ?? $o['categoria_insumos_nombre'] ?? 'Insumos') ?> — <?= escapar((string) $o['parcial']) ?> de <?= escapar((string) $o['objetivo']) ?></option><?php endforeach; ?>
</select></div>
<div class="campo"><label for="cantidad">Cantidad recibida</label><input type="number" id="cantidad" name="cantidad" min="0.01" max="9999999999.99" step="0.01" required value="<?= escapar(is_string($_POST['cantidad'] ?? null) ? $_POST['cantidad'] : '') ?>"><p>Usá la misma unidad indicada en el objetivo.</p></div>
<div class="campo"><label><input type="checkbox" name="confirmado" value="1" required> Confirmo que ya recibí estos insumos.</label></div>
<button type="submit" class="boton">Registrar recepción</button>
</form>
<?php else: ?><p>No se pueden registrar recepciones sin objetivos o en una campaña que no está activa.</p><?php endif; ?>
<h2>Recepciones registradas</h2>
<?php if (!$recepciones): ?><p>Todavía no se registraron entregas.</p><?php else: ?>
<ul><?php foreach ($recepciones as $r): ?><li><strong><?= escapar($r['cantidad']) ?> de <?= escapar($r['insumo']) ?></strong> · <?= escapar($r['donante']) ?> · <?= escapar($r['fecha_hora']) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<p><a class="boton boton-secundario" href="<?= escapar($rutaBase) ?>/index.php?ruta=campana&amp;id=<?= $id ?>">Ver progreso de la campaña</a></p>
</article></div></section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
