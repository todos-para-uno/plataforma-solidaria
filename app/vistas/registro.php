<?php
/*
 * Vista de registro de usuarios: solo la presentación del formulario.
 *
 * Integración pendiente (la hace el controlador, no esta vista):
 *   - $errores: arreglo de textos con los errores de validación del servidor.
 *     Si no existe o está vacío, no se muestra ningún mensaje.
 *   - $nombre y $mail: lo que escribió la persona, para no hacerle tipear de nuevo
 *     si hubo errores. Las contraseñas nunca se vuelven a mostrar.
 * La ruta de 'registro' ya recibe el POST y muestra una confirmación de prueba.
 * La validación y el guardado siguen pendientes.
 */
$tituloPagina = 'Crear cuenta';
$paginaActual = 'registro';
$usuarioActual = null;
$estilosPagina = ['registro.css'];

// Valores iniciales; el controlador podrá conservarlos cuando se integre la validación.
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

            <!-- Las validaciones HTML5 (required, type, minlength) solo ayudan al navegador.
                 El servidor tiene que validar todo de nuevo, incluso que las dos contraseñas coincidan. -->
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
