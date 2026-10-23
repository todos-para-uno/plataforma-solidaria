<?php
/*
 * Vista de inicio de sesión: solo la presentación del formulario.
 *
 * Integración pendiente (la hace el controlador, no esta vista):
 *   - $errores: arreglo de textos con los errores del servidor.
 *     Si no existe o está vacío, no se muestra ningún mensaje.
 *   - $mail: lo que escribió la persona, para no hacerle tipear de nuevo
 *     si hubo un error. La contraseña nunca se vuelve a mostrar.
 *   - La ruta real de 'ingresar' en rutas.php. Mientras sea '#', el formulario
 *     todavía no tiene a dónde enviarse.
 */
$tituloPagina = 'Ingresar';
$paginaActual = 'ingresar';
$usuarioActual = null;
$estilosPagina = ['ingresar.css'];

// Valores por defecto para que la vista funcione sola, antes de que exista el controlador.
$errores = $errores ?? [];
$mail = $mail ?? '';

require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion" aria-labelledby="titulo-ingresar">
    <div class="contenedor">
        <div class="ingresar-interior">
            <div class="seccion-encabezado">
                <h1 id="titulo-ingresar">Ingresá a tu cuenta</h1>
                <p class="bajada">Seguí tus campañas, tus donaciones y tus mensajes.</p>
            </div>

            <?php if (!empty($errores)): ?>
                <div class="alerta alerta-error" role="alert">
                    <p>No pudimos iniciar tu sesión:</p>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?= escapar($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Si el correo o la contraseña no coinciden, el controlador muestra un solo
                 mensaje para los dos casos, así no se revela qué correos están registrados. -->
            <form class="tarjeta" action="<?= escapar($rutas['ingresar']) ?>" method="post">
                <div class="campo">
                    <label for="ingresar-mail">Correo electrónico</label>
                    <input id="ingresar-mail" name="mail" type="email" autocomplete="email"
                           value="<?= escapar($mail) ?>" required>
                </div>

                <div class="campo">
                    <label for="ingresar-pass">Contraseña</label>
                    <input id="ingresar-pass" name="pass" type="password" autocomplete="current-password"
                           required>
                </div>

                <button class="boton boton-ancho" type="submit">Ingresar</button>
            </form>

            <p class="ingresar-registro">
                ¿Todavía no tenés cuenta? <a href="<?= escapar($rutas['registro']) ?>">Creá tu cuenta</a>
            </p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
