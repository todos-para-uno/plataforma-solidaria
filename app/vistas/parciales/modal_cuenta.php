<?php
/**
 * Parcial: modal "Mi cuenta"
 * Ubicación sugerida: app/vistas/parciales/modal_cuenta.php
 *
 * Solo presentación. Espera (opcional) un array $usuario con:
 *   nombre, apellido, mail, avatar (ruta interna opcional)
 * El formulario compartido guarda el perfil de la sesión con CSRF.
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
$avatar = dato_cuenta($usuarioCuenta, 'avatar') ? escapar($rutas['imagen_perfil']) : '';
$inicial  = htmlspecialchars(mb_strtoupper(mb_substr($cuentaNombre !== '' ? $cuentaNombre : '?', 0, 1)), ENT_QUOTES, 'UTF-8');
?>
<dialog class="modal-cuenta" id="modal-cuenta" aria-labelledby="modal-cuenta-titulo">
  <div class="modal-cuenta__caja">

    <!-- Columna izquierda: título y navegación -->
    <aside class="modal-cuenta__lateral">
      <h2 class="modal-cuenta__titulo" id="modal-cuenta-titulo">Cuenta</h2>
      <p class="modal-cuenta__bajada">Gestioná la información de tu cuenta.</p>

      <nav class="modal-cuenta__nav" role="tablist" aria-label="Secciones de la cuenta">
        <button type="button" class="boton boton-secundario boton-chico modal-cuenta__pestana" role="tab"
                id="pestana-perfil" aria-controls="seccion-perfil" aria-selected="true">
          <span class="modal-cuenta__icono" aria-hidden="true">●</span> Perfil
        </button>
      </nav>
    </aside>

    <!-- Columna derecha: contenido -->
    <div class="modal-cuenta__contenido">
      <div class="modal-cuenta__cabecera"><button type="button" class="boton boton-secundario boton-chico" data-cerrar-cuenta aria-label="Cerrar Mi cuenta">Cerrar <span aria-hidden="true">×</span></button></div>

      <!-- ===== PERFIL ===== -->
      <section class="modal-cuenta__seccion" id="seccion-perfil" role="tabpanel" aria-labelledby="pestana-perfil">
        <h3 class="modal-cuenta__seccion-titulo">Perfil</h3>
        <?php if (!empty($confianzaCuenta)):
            $claseNivelCuenta = [1 => 'nivel-blanco', 2 => 'nivel-celeste', 3 => 'nivel-azul', 4 => 'nivel-violeta'][(int) $confianzaCuenta['nivel']] ?? 'nivel-blanco';
        ?>
        <div class="fila-cuenta">
          <span class="fila-cuenta__etiqueta">Nivel de confianza</span>
          <div class="fila-cuenta__valor">
            <p class="cuenta-dato"><span class="insignia <?= escapar($claseNivelCuenta) ?>"><?= escapar($confianzaCuenta['nombre']) ?></span> <?= escapar($confianzaCuenta['descripcion']) ?></p>
            <?php if (!$confianzaCuenta['tiene_documento']): ?>
              <div class="alerta alerta-aviso"><p>Podés cargar tu DNI o Pasaporte para pasar a los siguientes niveles.</p></div>
              <a class="boton boton-secundario boton-chico" href="<?= escapar($rutaPerfil) ?>">Cargar documento</a>
            <?php else: ?><a href="<?= escapar($rutaPerfil) ?>">Ver mi nivel y mis donaciones</a><?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

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
              <button type="button" class="boton boton-secundario boton-chico" data-perfil-editar>Actualizar perfil</button>
            </div>

            <?php
            $cuentaApellido = (string) dato_cuenta($usuarioCuenta, 'apellido');
            $tieneAvatar = (bool) dato_cuenta($usuarioCuenta, 'avatar');
            $inicialPerfil = mb_strtoupper(mb_substr($cuentaNombre !== '' ? $cuentaNombre : '?', 0, 1));
            $idFormularioPerfil = 'cuenta';
            $formularioOculto = true;
            require __DIR__ . '/formulario_perfil.php';
            ?>
          </div>
        </div>

        <div class="fila-cuenta">
          <span class="fila-cuenta__etiqueta">Correo electrónico</span>
          <div class="fila-cuenta__valor">
            <p class="cuenta-dato">
              <?= $mail ?>
              <span class="insignia">Principal</span>
            </p>
          </div>
        </div>
        <div class="fila-cuenta">
          <span class="fila-cuenta__etiqueta">Mercado Pago</span>
          <div class="fila-cuenta__valor">
            <p>Conectá tu cuenta para recibir los aportes de tus campañas de dinero.</p>
            <?php if (!empty($rutaCuentaMp)): ?>
              <a class="boton boton-secundario boton-chico" href="<?= escapar($rutaCuentaMp) ?>" aria-describedby="cuenta-mercadopago-ayuda">Conectar Mercado Pago</a>
              <p class="campo-ayuda" id="cuenta-mercadopago-ayuda">Continuarás a la vinculación de tu cuenta.</p>
            <?php else: ?>
              <button type="button" class="boton boton-secundario boton-chico" disabled aria-describedby="cuenta-mercadopago-ayuda">Conectar Mercado Pago</button>
              <p class="campo-ayuda" id="cuenta-mercadopago-ayuda">La conexión estará disponible próximamente.</p>
            <?php endif; ?>
          </div>
        </div>
        <div class="fila-cuenta">
          <span class="fila-cuenta__etiqueta">Baja de cuenta</span>
          <div class="fila-cuenta__valor">
            <p>Podés desactivar tu cuenta y reactivarla cuando vuelvas a ingresar.</p>
            <details>
              <summary class="boton boton-secundario boton-chico" style="cursor:pointer">Solicitar baja de cuenta</summary>
              <form action="<?= escapar(strtok($rutas['perfil'], '?') . '?ruta=baja_cuenta') ?>" method="post" style="margin-top:1rem">
                <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken ?? '') ?>">
                <p>La cuenta se desactivará inmediatamente.</p>
                <label><input type="checkbox" name="eliminar_datos" value="1"> Eliminar además mis datos personales (irreversible). Las campañas abiertas se cerrarán y se conservará el historial de donaciones.</label>
                <div class="campo" style="margin-top:1rem">
                  <label for="clave_baja_modal">Confirmá tu contraseña</label>
                  <input id="clave_baja_modal" type="password" name="pass" required autocomplete="current-password">
                </div>
                <button class="boton" type="submit" style="margin-top:1rem">Confirmar baja</button>
              </form>
            </details>
          </div>
        </div>
      </section>
    </div>
  </div>
</dialog>