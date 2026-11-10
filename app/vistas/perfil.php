<?php
/**
 * Vista de perfil de usuario en todospara.uno
 * Muestra los datos del usuario, alerta si falta el documento e incluye un formulario para cargarlo.
 *
 * Datos que recibe del controlador:
 *   $usuario           ?array   nombre, mail, telefono, tipo_documento, documento y validacion_nombre.
 *   $errores           string[] Mensajes de validación del documento.
 *   $documentoGuardado bool     true después de guardar correctamente en la base.
 *   $tipoDocumento     string   Tipo ingresado, para conservarlo si hay errores.
 *   $documento         string   Número ingresado, para conservarlo si hay errores.
 * Los nombres de los campos enviados son tipo_documento y documento.
 */

$tituloPagina  = 'Mi perfil — todospara.uno';
$paginaActual  = 'perfil';
$usuarioActual = $usuarioActual ?? null; 
$estilosPagina = ['perfil.css']; // Hoja propia para el layout del perfil

// El controlador carga los datos reales; sin usuario se muestra el estado de error.
$usuario = $usuario ?? null;
$errores = $errores ?? [];
$documentoGuardado = $documentoGuardado ?? false;
$tipoDocumento = $tipoDocumento ?? ($usuario['tipo_documento'] ?? 'DNI');
$documento = $documento ?? ($usuario['documento'] ?? '');

// Mapeo para asignar la clase de color correspondiente a la insignia de nivel de confianza
$clasesNivel = [
    'Blanco'   => 'nivel-blanco',
    'Celeste'  => 'nivel-celeste',
    'Azul'     => 'nivel-azul',
    'Violeta'  => 'nivel-violeta'
];
$claseInsignia = $clasesNivel[$usuario['validacion_nombre'] ?? ''] ?? 'nivel-blanco';

// Verificamos si el usuario ya tiene cargado su número de documento
$tieneDocumento = trim($usuario['documento'] ?? '') !== '';

require __DIR__ . '/parciales/cabecera.php';
// Destino acordado para el backend, hasta incorporarlo al mapa de rutas común.
$rutas['actualizar_documento'] ??= ($rutaBase ?? '../../public_php') . '/index.php?ruta=actualizar_documento';
?>

    <section class="seccion">
        <div class="contenedor">
            <div class="seccion-encabezado encabezado-trama">
                <h1>Mi perfil</h1>
                <p class="bajada">Administrá la información de tu cuenta y tu nivel de confianza en la plataforma.</p>
            </div>

            <?php if (empty($usuario)): ?>
                <div class="alerta alerta-error" role="alert">
                    <p>No se pudieron cargar los datos del usuario. Intentá nuevamente más tarde.</p>
                </div>
            <?php else: ?>
                <?php if ($perfilGuardado ?? false): ?>
                    <div class="alerta alerta-exito" role="status"><p>Tu perfil se guardó correctamente.</p></div>
                <?php endif; ?>
                <?php if ($erroresPerfil ?? []): ?>
                    <div class="alerta alerta-error" role="alert">
                        <p>No pudimos guardar tu perfil:</p>
                        <ul><?php foreach ($erroresPerfil as $errorPerfil): ?><li><?= escapar($errorPerfil) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <?php if ($documentoGuardado): ?>
                    <div class="alerta alerta-exito" role="status">
                        <p>Tu documento se guardó correctamente.</p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errores)): ?>
                    <div class="alerta alerta-error" role="alert">
                        <p>No pudimos guardar tu documento:</p>
                        <ul>
                            <?php foreach ($errores as $error): ?>
                                <li><?= escapar($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Cartel de aviso si el usuario no tiene su documento ingresado -->
                <?php if (!$tieneDocumento): ?>
                    <div class="alerta alerta-aviso" role="region" aria-label="Aviso de documento pendiente">
                        <p><strong>Aviso importante:</strong> Todavía no ingresaste tu número de documento. Podés cargar tu DNI o Pasaporte para pasar a los siguientes niveles. Tus donaciones realizadas y recibidas se tendrán en cuenta al cargarlo.</p>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta principal con la información del usuario -->
                <div class="tarjeta perfil-tarjeta">
                    
                    <!-- Nombre y nivel de validación en una sola línea -->
                    <div class="perfil-encabezado-linea">
                        <h2><?= escapar(trim($usuario['nombre'] . ' ' . ($usuario['apellido'] ?? ''))) ?></h2>
                        <span class="insignia <?= escapar($claseInsignia) ?>">
                            Nivel <?= escapar($usuario['validacion_nombre']) ?>
                        </span>
                    </div>

                    <?php if (isset($confianza)): ?>
                    <div class="perfil-campo">
                        <span class="perfil-etiqueta">Estado de confianza</span>
                        <p class="perfil-valor"><span class="insignia <?= escapar($claseInsignia) ?>"><?= escapar($confianza['nombre']) ?></span> <?= escapar($confianza['descripcion']) ?></p>
                        <p>Donaciones realizadas: <?= (int) $confianza['realizadas'] ?> · Recibidas: <?= (int) $confianza['recibidas'] ?></p>
                        <p class="campo-ayuda">Con documento cargado: Celeste sin donaciones computables; Azul con 1 o 2; Violeta con 3 o más. Se suman las donaciones realizadas con tu cuenta y las recibidas de otros usuarios registrados. Las entregas recibidas sin cuenta no cuentan para el nivel.</p>
                    </div>
                    <?php endif; ?>
                    <!-- Correo electrónico -->
                    <div class="perfil-campo">
                        <span class="perfil-etiqueta">Correo electrónico</span>
                        <p class="perfil-valor"><?= escapar($usuario['mail']) ?></p>
                    </div>

                    <!-- Teléfono o mensaje alternativo -->
                    <div class="perfil-campo">
                        <span class="perfil-etiqueta">Teléfono</span>
                        <p class="perfil-valor">
                            <?php if (!empty($usuario['telefono'])): ?>
                                <?= escapar($usuario['telefono']) ?>
                            <?php else: ?>
                                <span class="texto-secundario">Todavía no cargaste un número de teléfono.</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- Documento -->
                    <div class="perfil-campo">
                        <span class="perfil-etiqueta">Documento</span>
                        <p class="perfil-valor">
                            <?php if ($tieneDocumento): ?>
                                <?= escapar($usuario['tipo_documento'] ?? 'DNI') ?>: <?= escapar($usuario['documento']) ?>
                            <?php else: ?>
                                <span class="texto-secundario">No cargado</span>
                            <?php endif; ?>
                        </p>
                    </div>

                </div>

                <div class="tarjeta" style="margin-top:2rem;padding:1.5rem">
                    <h2>Dar de baja mi cuenta</h2>
                    <p>Podés desactivar tu cuenta y reactivarla cuando vuelvas a ingresar.</p>
                    <details data-baja-cuenta>
                        <summary class="boton" style="cursor:pointer">Solicitar baja de cuenta</summary>
                        <form action="<?= escapar($rutaBase) ?>/index.php?ruta=baja_cuenta" method="post" style="margin-top:1rem">
                            <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
                            <p>La cuenta se desactivará inmediatamente.</p>
                            <label>
                                <input type="checkbox" name="eliminar_datos" value="1" data-eliminar-datos>
                                Eliminar además mis datos personales (irreversible).
                                Las campañas abiertas se cerrarán y se conservará el historial de donaciones.
                            </label>

                            <div data-advertencia-eliminacion hidden style="margin-top:1rem">
                                <p>
                                    <strong>Atención: esta acción es irreversible.</strong>
                                    Tus datos personales serán eliminados o anonimizados
                                    y no podrás recuperar esta cuenta.
                                    Tus campañas abiertas se cerrarán y se conservará
                                    el historial de donaciones.
                                </p>

                                <label>
                                    <input
                                        type="checkbox"
                                        name="confirmar_eliminacion"
                                        value="1"
                                        data-confirmar-eliminacion
                                        disabled
                                    >
                                    Entiendo que esta eliminación es irreversible
                                    y que no podré recuperar mi cuenta.
                                </label>
                            </div>
                            <div class="campo"><label for="clave_baja">Confirmá tu contraseña</label><input id="clave_baja" type="password" name="pass" required autocomplete="current-password"></div>
                            <button class="boton" type="submit">Confirmar baja</button>
                        </form>
                    </details>
                </div>

                <?php
                (static function (array $usuario, array $rutas, string $csrfToken, array $datosPerfil): void {
                    $cuentaNombre = $usuario['nombre'];
                    $cuentaApellido = $usuario['apellido'] ?? '';
                    $tieneAvatar = !empty($usuario['avatar']);
                    $inicialPerfil = mb_strtoupper(mb_substr($cuentaNombre, 0, 1));
                    require __DIR__ . '/parciales/formulario_perfil.php';
                })($usuario, $rutas, $csrfToken, $datosPerfil ?? []);
                ?>

                <!-- Formulario para ingresar el documento (aparece abajo solo si no lo tiene) -->
                <?php if (!$tieneDocumento): ?>
                    <div class="tarjeta perfil-form-tarjeta">
                        <h3>Completá tu documento</h3>
                        <p class="perfil-form-descripcion">Ingresá tu tipo y número de documento para avanzar en los niveles de confianza.</p>
                        
                        <form action="<?= escapar($rutas['actualizar_documento']) ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
                            <div class="campo">
                                <label for="tipo_documento">Tipo de documento</label>
                                <select id="tipo_documento" name="tipo_documento" required>
                                    <option value="DNI"<?= $tipoDocumento === 'DNI' ? ' selected' : '' ?>>DNI</option>
                                    <option value="Pasaporte"<?= $tipoDocumento === 'Pasaporte' ? ' selected' : '' ?>>Pasaporte</option>
                                </select>
                            </div>

                            <div class="campo">
                                <label for="documento">Número de documento</label>
                                <input type="text" id="documento" name="documento" placeholder="Ej. 35123456"
                                       maxlength="30" value="<?= escapar($documento) ?>" required />
                            </div>

                            <button type="submit" class="boton">Guardar documento</button>
                        </form>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </section>

<?php require __DIR__ . '/parciales/pie.php'; ?>
