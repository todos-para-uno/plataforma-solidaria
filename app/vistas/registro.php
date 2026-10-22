<?php
/*
 * Formulario de registro. Recibe datos para mostrar; su envío va al controlador.
 *
 * Al integrar la validación, el controlador podrá pasarle:
 *   $errores  string[]  Mensajes de validación. Con un arreglo vacío, no se muestran errores.
 *   $nombre   string    Nombre ingresado, para conservarlo si hay que corregir el formulario.
 *   $mail     string    Correo ingresado, con el mismo propósito.
 * Las contraseñas no se vuelven a mostrar en los campos.
 *
 * Hoy el POST muestra una confirmación de prueba. Falta validar los datos y guardar
 * el usuario desde RegistroController; este formulario todavía no crea una cuenta.
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

            <!-- required, type y minlength ayudan a completar el formulario en el navegador.
                 Al integrar el registro, validá también en el servidor: se puede enviar un POST
                 sin pasar por esta pantalla. Comprobá además que las contraseñas coincidan. -->
            <form class="tarjeta" action="<?= escapar($rutas['registro']) ?>" method="post">
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
