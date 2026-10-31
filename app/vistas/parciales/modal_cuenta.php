<?php
/**
 * Parcial: modal "Mi cuenta"
 * Ubicación sugerida: app/vistas/parciales/modal_cuenta.php
 *
 * Solo presentación. Espera (opcional) un array $usuario con:
 *   nombre, apellido, mail, avatar (URL), dispositivos (array)
 * Los "action" de los formularios son placeholders: reemplazalos
 * por las rutas reales del controlador de cuenta.
 *
 * Uso:
 *   <button type="button" data-abrir-cuenta>Mi cuenta</button>
 *   <?php require __DIR__ . '/parciales/modal_cuenta.php'; ?>
 */

/*
 * $usuario puede llegar como array o como objeto Usuario.
 * Con objeto, se busca primero un getter (getNombre(), getMail()...)
 * y si no existe, una propiedad pública ($usuario->nombre).
 */
if (!function_exists('dato_cuenta')) {
    function dato_cuenta($usuario, string $campo, $porDefecto = '')
    {
        if (is_array($usuario)) {
            return $usuario[$campo] ?? $porDefecto;
        }
        if (is_object($usuario)) {
            $getter = 'get' . ucfirst($campo);
            if (method_exists($usuario, $getter)) {
                return $usuario->$getter() ?? $porDefecto;
            }
            if (isset($usuario->$campo)) {
                return $usuario->$campo;
            }
        }
        return $porDefecto;
    }
}

$usuarioCuenta = $usuario ?? [];
$cuentaNombre   = (string) dato_cuenta($usuarioCuenta, 'nombre');
$nombre   = htmlspecialchars($cuentaNombre, ENT_QUOTES, 'UTF-8');
$apellido = htmlspecialchars((string) dato_cuenta($usuarioCuenta, 'apellido'), ENT_QUOTES, 'UTF-8');
// El modelo Usuario expone el correo con getEmail(); en array viene como 'mail'.
$mail     = htmlspecialchars((string) (dato_cuenta($usuarioCuenta, 'mail') ?: dato_cuenta($usuarioCuenta, 'email')), ENT_QUOTES, 'UTF-8');
$avatar   = htmlspecialchars((string) dato_cuenta($usuarioCuenta, 'avatar'), ENT_QUOTES, 'UTF-8');
$inicial  = mb_strtoupper(mb_substr($cuentaNombre !== '' ? $cuentaNombre : '?', 0, 1));
$dispositivos = dato_cuenta($usuarioCuenta, 'dispositivos', []);
if (!is_array($dispositivos)) { $dispositivos = []; }
?>
<dialog class="modal-cuenta" id="modal-cuenta" aria-labelledby="modal-cuenta-titulo">
  <div class="modal-cuenta__caja">

    <!-- Columna izquierda: título y navegación -->
    <aside class="modal-cuenta__lateral">
      <h2 class="modal-cuenta__titulo" id="modal-cuenta-titulo">Cuenta</h2>
      <p class="modal-cuenta__bajada">Gestioná la información de tu cuenta.</p>

      <nav class="modal-cuenta__nav" role="tablist" aria-label="Secciones de la cuenta">
        <button type="button" class="modal-cuenta__pestana" role="tab"
                id="pestana-perfil" aria-controls="seccion-perfil" aria-selected="true">
          <span class="modal-cuenta__icono" aria-hidden="true">●</span> Perfil
        </button>
        <button type="button" class="modal-cuenta__pestana" role="tab"
                id="pestana-seguridad" aria-controls="seccion-seguridad" aria-selected="false" tabindex="-1">
          <span class="modal-cuenta__icono" aria-hidden="true">■</span> Seguridad
        </button>
      </nav>
    </aside>

    <!-- Columna derecha: contenido -->
    <div class="modal-cuenta__contenido">
      <button type="button" class="modal-cuenta__cerrar" data-cerrar-cuenta aria-label="Cerrar">✕</button>

      <!-- ===== PERFIL ===== -->
      <section class="modal-cuenta__seccion" id="seccion-perfil" role="tabpanel" aria-labelledby="pestana-perfil">
        <h3 class="modal-cuenta__seccion-titulo">Perfil</h3>

        <div class="fila-cuenta">
          <span class="fila-cuenta__etiqueta">Datos personales</span>
          <div class="fila-cuenta__valor">
            <!-- Vista de lectura -->
            <div class="perfil-resumen" data-perfil-lectura>
              <?php if ($avatar): ?>
                <img class="avatar" src="<?= $avatar ?>" alt="">
              <?php else: ?>
                <span class="avatar avatar--inicial" aria-hidden="true"><?= $inicial ?></span>
              <?php endif; ?>
              <span class="perfil-resumen__nombre"><?= $nombre ?> <?= $apellido ?></span>
              <button type="button" class="boton-texto" data-perfil-editar>Actualizar perfil</button>
            </div>

            <!-- Vista de edición (oculta hasta tocar "Actualizar perfil") -->
            <form class="tarjeta-edicion" data-perfil-formulario hidden
                  action="/cuenta/actualizar" method="post" enctype="multipart/form-data">
              <p class="tarjeta-edicion__titulo">Actualizar perfil</p>

              <div class="imagen-perfil">
                <?php if ($avatar): ?>
                  <img class="avatar" src="<?= $avatar ?>" alt="" data-avatar-vista>
                <?php else: ?>
                  <span class="avatar avatar--inicial" aria-hidden="true" data-avatar-vista><?= $inicial ?></span>
                <?php endif; ?>
                <div>
                  <label class="boton boton--secundario boton--chico">
                    Cargar imagen
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" hidden data-avatar-input>
                  </label>
                  <button type="submit" name="eliminar_avatar" value="1" class="boton-texto boton-texto--peligro">Eliminar imagen</button>
                  <p class="ayuda">JPG, PNG, GIF o WEBP de menos de 10 MB.</p>
                </div>
              </div>

              <div class="campos-dobles">
                <label class="campo">
                  <span class="campo__etiqueta">Nombre</span>
                  <input class="campo__input" type="text" name="nombre" value="<?= $nombre ?>" required>
                </label>
                <label class="campo">
                  <span class="campo__etiqueta">Apellido</span>
                  <input class="campo__input" type="text" name="apellido" value="<?= $apellido ?>">
                </label>
              </div>

              <div class="acciones">
                <button type="button" class="boton boton--secundario" data-perfil-cancelar>Cancelar</button>
                <button type="submit" class="boton boton--primario">Guardar</button>
              </div>
            </form>
          </div>
        </div>

        <div class="fila-cuenta">
          <span class="fila-cuenta__etiqueta">Correo electrónico</span>
          <div class="fila-cuenta__valor">
            <p class="dato">
              <?= $mail ?>
              <span class="insignia">Principal</span>
            </p>
            <a class="boton-texto" href="/cuenta/correo">+ Agregar otro correo</a>
          </div>
        </div>
      </section>

      <!-- ===== SEGURIDAD ===== -->
      <section class="modal-cuenta__seccion" id="seccion-seguridad" role="tabpanel" aria-labelledby="pestana-seguridad" hidden>
        <h3 class="modal-cuenta__seccion-titulo">Seguridad</h3>

        <div class="fila-cuenta">
          <span class="fila-cuenta__etiqueta">Contraseña</span>
          <div class="fila-cuenta__valor">
            <a class="boton-texto" href="/cuenta/contrasena">Cambiar contraseña</a>
          </div>
        </div>

        <div class="fila-cuenta">
          <span class="fila-cuenta__etiqueta">Dispositivos activos</span>
          <div class="fila-cuenta__valor">
            <?php if (!$dispositivos): ?>
              <p class="ayuda">No hay otros dispositivos con la sesión abierta.</p>
            <?php endif; ?>
            <?php foreach ($dispositivos as $d): ?>
              <div class="dispositivo">
                <p class="dato">
                  <?= htmlspecialchars($d['sistema'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  <?php if (!empty($d['actual'])): ?><span class="insignia">Este dispositivo</span><?php endif; ?>
                </p>
                <p class="ayuda"><?= htmlspecialchars($d['navegador'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="ayuda"><?= htmlspecialchars($d['ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="ayuda">Último acceso: <?= htmlspecialchars($d['ultimo_acceso'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="fila-cuenta fila-cuenta--peligro">
          <span class="fila-cuenta__etiqueta">Eliminar cuenta</span>
          <div class="fila-cuenta__valor">
            <p class="ayuda">Se borran tu perfil y tus datos. Las campañas activas quedan cerradas.</p>
            <form action="/cuenta/eliminar" method="post"
                  onsubmit="return confirm('¿Seguro que querés eliminar tu cuenta? No se puede deshacer.');">
              <button type="submit" class="boton boton--peligro">Eliminar cuenta</button>
            </form>
          </div>
        </div>
      </section>
    </div>
  </div>
</dialog>