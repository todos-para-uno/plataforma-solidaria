<?php
/*
 * Vista de contacto: formulario para escribirle al equipo de todospara.uno.
 *
 * Recibe de ContactoController:
 *   - $errores: arreglo de textos con los errores del servidor.
 *   - $enviado: true si el mensaje se guardó bien; muestra la confirmación.
 *   - $nombre, $mail, $asunto y $contenido: lo que escribió la persona,
 *     para no hacerle tipear de nuevo si hubo errores.
 *   - $csrfToken: protección del envío del formulario.
 *   - Si hay sesión, nombre y mail se completan con los datos del usuario.
 */
$tituloPagina = 'Contacto';
$paginaActual = 'contacto';
$usuarioActual = $usuarioActual ?? null;
$estilosPagina = ['contacto.css'];

// Valores por defecto para que la vista funcione sola, antes de que exista el controlador.
$errores = $errores ?? [];
$enviado = $enviado ?? false;
$nombre = $nombre ?? '';
$mail = $mail ?? '';
$asunto = $asunto ?? '';
$contenido = $contenido ?? '';

require_once __DIR__ . '/../modelos/Contacto.php';
$asuntos = Contacto::ASUNTOS;

require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion" aria-labelledby="titulo-contacto">
    <div class="contenedor">
        <div class="seccion-encabezado">
            <h1 id="titulo-contacto">Escribinos</h1>
            <p class="bajada">Si tenés una duda, un problema con una campaña o querés contarnos algo, dejanos tu mensaje.</p>
        </div>

        <div class="contacto-interior">
            <div>
                <?php if ($enviado): ?>
                    <div class="alerta alerta-exito" role="status">
                        <p>Recibimos tu consulta. Te vamos a responder al correo que nos dejaste.</p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errores)): ?>
                    <div class="alerta alerta-error" role="alert">
                        <p>No pudimos enviar tu mensaje:</p>
                        <ul>
                            <?php foreach ($errores as $error): ?>
                                <li><?= escapar($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Las validaciones HTML5 solo ayudan al navegador; el servidor valida todo de nuevo. -->
                <form class="tarjeta contacto-formulario" action="<?= escapar($rutas['contacto']) ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken ?? '') ?>">
                    <div class="campo">
                        <label for="contacto-nombre">Nombre</label>
                        <input id="contacto-nombre" name="nombre" type="text" autocomplete="name"
                               maxlength="100" value="<?= escapar($nombre) ?>" required>
                    </div>

                    <div class="campo">
                        <label for="contacto-mail">Correo electrónico</label>
                        <input id="contacto-mail" name="mail" type="email" autocomplete="email"
                               maxlength="150" value="<?= escapar($mail) ?>" required
                               aria-describedby="contacto-mail-ayuda">
                        <span class="campo-ayuda" id="contacto-mail-ayuda">Te respondemos a este correo.</span>
                    </div>

                    <div class="campo">
                        <label for="contacto-asunto">Asunto</label>
                        <select id="contacto-asunto" name="asunto" required>
                            <option value="">Elegí un asunto</option>
                            <?php foreach ($asuntos as $clave => $texto): ?>
                                <option value="<?= escapar($clave) ?>"<?php if ($clave === $asunto): ?> selected<?php endif; ?>><?= escapar($texto) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="contacto-contenido">Mensaje</label>
                        <textarea id="contacto-contenido" name="contenido" maxlength="1000" required
                                  aria-describedby="contacto-contenido-ayuda"><?= escapar($contenido) ?></textarea>
                        <span class="campo-ayuda" id="contacto-contenido-ayuda">Si es sobre una campaña, contanos su nombre.</span>
                    </div>

                    <button class="boton boton-ancho" type="submit">Enviar mensaje</button>
                </form>
            </div>

            <aside class="contacto-ayuda" aria-labelledby="titulo-ayuda">
                <h2 id="titulo-ayuda">Antes de escribirnos</h2>
                <p>Muchas dudas ya tienen respuesta en las <a href="<?= escapar($rutas['preguntas']) ?>">preguntas frecuentes</a>.</p>
                <p>Si querés hablar con quien creó una campaña, escribile desde la campaña: los mensajes le llegan directo.</p>
                <p class="texto-secundario">Si ves una campaña que te parece falsa, elegí el asunto "Denunciar una campaña" y contanos cuál es.</p>
            </aside>
        </div>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
