<?php
/*
 * Formulario de registro.
 *
 * Recibe del controlador:
 *   $errores    string[] Mensajes de validación.
 *   $nombre     string   Nombre ingresado.
 *   $mail       string   Correo ingresado.
 *   $csrfToken  string   Token de protección CSRF.
 *
 * El envío del formulario es procesado por RegistroController.
 * Las contraseñas no se vuelven a mostrar después de un error.
 */

$tituloPagina = 'Crear cuenta';
$paginaActual = 'registro';
$usuarioActual = null;
$estilosPagina = ['registro.css'];

// Con estos valores el formulario también puede abrirse antes de recibir datos del controlador.
$errores = $errores ?? [];
$nombre = $nombre ?? '';
$mail = $mail ?? '';

require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion" aria-labelledby="titulo-registro">
    <div class="contenedor">
        <div class="registro-interior">
            <div class="seccion-encabezado">
                <h1 id="titulo-registro">Creá tu cuenta</h1>
                <p class="bajada">Con tu cuenta podés donar y crear campañas.</p>
            </div>

            <?php if (!empty($errores)): ?>
                <div class="alerta alerta-error" role="alert">
                    <p>No pudimos crear tu cuenta:</p>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?= escapar($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="tarjeta" action="<?= escapar($rutas['registro']) ?>" method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                >
                <div class="campo">
                    <label for="registro-nombre">Nombre</label>
                    <input id="registro-nombre" name="nombre" type="text" autocomplete="name"
                           value="<?= escapar($nombre) ?>" required>
                </div>

                <div class="campo">
                    <label for="registro-mail">Correo electrónico</label>
                    <input id="registro-mail" name="mail" type="email" autocomplete="email"
                           value="<?= escapar($mail) ?>" required>
                </div>

                <div class="campo">
                    <label for="registro-pass">Contraseña</label>
                    <input id="registro-pass" name="pass" type="password" autocomplete="new-password"
                           minlength="8" required aria-describedby="registro-pass-ayuda">
                    <span class="campo-ayuda" id="registro-pass-ayuda">Usá al menos 8 caracteres.</span>
                </div>

                <div class="campo">
                    <label for="registro-confirmar">Confirmar contraseña</label>
                    <input id="registro-confirmar" name="confirmar_pass" type="password" autocomplete="new-password"
                           minlength="8" required>
                </div>

                <button class="boton boton-ancho" type="submit">Crear cuenta</button>
            </form>

            <p class="registro-ingresar">
                ¿Ya tenés cuenta? <a href="<?= escapar($rutas['ingresar']) ?>">Ingresar</a>
            </p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
