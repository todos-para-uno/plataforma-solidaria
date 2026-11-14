<?php
// Compartido por la página de perfil y el modal. El guardado funciona sin JavaScript.
$idFormularioPerfil = $idFormularioPerfil ?? 'perfil';
$formularioOculto = $formularioOculto ?? false;
$valorNombrePerfil = is_string($datosPerfil['nombre'] ?? null) ? $datosPerfil['nombre'] : $cuentaNombre;
$valorApellidoPerfil = is_string($datosPerfil['apellido'] ?? null) ? $datosPerfil['apellido'] : $cuentaApellido;
require_once __DIR__ . '/../../servicios/ImagenPerfil.php';
$maximoAvatar = ImagenPerfil::tamanoMaximoPorImagen();
?>
<form class="tarjeta tarjeta-edicion" method="post" enctype="multipart/form-data"
      action="<?= escapar($rutas['actualizar_perfil']) ?>" data-perfil-formulario <?= $formularioOculto ? 'hidden' : '' ?>>
  <h3>Actualizar perfil</h3>
  <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
  <input type="hidden" name="MAX_FILE_SIZE" value="<?= $maximoAvatar ?>">
  <div class="imagen-perfil">
    <?php if ($tieneAvatar): ?>
      <img class="avatar" src="<?= escapar($rutas['imagen_perfil']) ?>" alt="" width="72" height="72" data-avatar-vista>
    <?php else: ?>
      <span class="avatar avatar--inicial" aria-hidden="true" data-avatar-vista><?= escapar($inicialPerfil) ?></span>
    <?php endif; ?>
    <div class="campo">
      <label for="<?= $idFormularioPerfil ?>-avatar">Foto de perfil (opcional)</label>
      <input id="<?= $idFormularioPerfil ?>-avatar" type="file" name="avatar" accept=".jpg,.jpeg,.webp,image/jpeg,image/webp"
             data-avatar-input data-maximo-bytes="<?= $maximoAvatar ?>" aria-describedby="<?= $idFormularioPerfil ?>-foto-ayuda <?= $idFormularioPerfil ?>-foto-estado">
      <p class="campo-ayuda" id="<?= $idFormularioPerfil ?>-foto-ayuda">JPG, JPEG o WEBP. Máximo <?= escapar(ImagenPerfil::formatearTamano($maximoAvatar)) ?> y 12 megapíxeles. La foto se recorta desde el centro a 250 × 250 píxeles.</p>
      <p id="<?= $idFormularioPerfil ?>-foto-estado" role="status" data-avatar-estado></p>
      <?php if ($tieneAvatar): ?>
        <label class="perfil-quitar-foto"><input type="checkbox" name="eliminar_avatar" value="1"> Quitar mi foto actual</label>
      <?php endif; ?>
    </div>
  </div>
  <div class="campos-dobles">
    <div class="campo">
      <label for="<?= $idFormularioPerfil ?>-nombre">Nombre</label>
      <input type="text" id="<?= $idFormularioPerfil ?>-nombre" name="nombre" value="<?= escapar($valorNombrePerfil) ?>" maxlength="100" autocomplete="given-name" required>
    </div>
    <div class="campo">
      <label for="<?= $idFormularioPerfil ?>-apellido">Apellido (opcional)</label>
      <input type="text" id="<?= $idFormularioPerfil ?>-apellido" name="apellido" value="<?= escapar($valorApellidoPerfil) ?>" maxlength="100" autocomplete="family-name">
    </div>
  </div>
  <div class="cuenta-acciones">
    <?php if ($formularioOculto): ?><button type="button" class="boton boton-secundario boton-chico" data-perfil-cancelar>Cancelar</button><?php endif; ?>
    <button type="submit" class="boton boton-chico">Guardar perfil</button>
  </div>
</form>
