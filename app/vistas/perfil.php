<?php
/**
 * Vista de perfil de usuario en todospara.uno
 * Muestra los datos del usuario, alerta si falta el documento e incluye un formulario para cargarlo.
 */

$tituloPagina  = 'Mi perfil — todospara.uno';
$paginaActual  = 'perfil';
$usuarioActual = $usuarioActual ?? null; 
$estilosPagina = ['perfil.css']; // Hoja propia para el layout del perfil

// Nota técnica: $usuario es provisto por el controlador utilizando los modelos y repositorios correspondientes.
// Contiene las columnas de la tabla usuarios y el nombre de la tabla validaciones (según schema.sql).
$usuario = $usuario ?? [
    'nombre'            => 'Sofía Martínez',
    'mail'              => 'sofia.martinez@email.com',
    'telefono'          => '011 1523456789',
    'tipo_documento'    => null, // Probá cambiando a 'DNI' y un número para ver el estado cargado
    'documento'         => null, 
    'validacion_nombre' => 'Blanco'
];

// Mapeo para asignar la clase de color correspondiente a la insignia de nivel de confianza
$clasesNivel = [
    'Blanco'   => 'nivel-blanco',
    'Celeste'  => 'nivel-celeste',
    'Azul'     => 'nivel-azul',
    'Violeta'  => 'nivel-violeta'
];
$claseInsignia = $clasesNivel[$usuario['validacion_nombre']] ?? 'nivel-blanco';

// Verificamos si el usuario ya tiene cargado su número de documento
$tieneDocumento = !empty($usuario['documento']);

require __DIR__ . '/parciales/cabecera.php';
?>

<main>
    <section class="seccion">
        <div class="contenedor">
            <div class="seccion-encabezado">
                <h1>Mi perfil</h1>
                <p class="bajada">Administrá la información de tu cuenta y tu nivel de confianza en la plataforma.</p>
            </div>

            <?php if (empty($usuario)): ?>
                <div class="alerta alerta-error" role="alert">
                    <p>No se pudieron cargar los datos del usuario. Intentá nuevamente más tarde.</p>
                </div>
            <?php else: ?>
                
                <!-- Cartel de aviso si el usuario no tiene su documento ingresado -->
                <?php if (!$tieneDocumento): ?>
                    <div class="alerta alerta-aviso" role="region" aria-label="Aviso de documento pendiente">
                        <p><strong>Aviso importante:</strong> Todavía no ingresaste tu número de documento. Completalo para pasar al nivel Celeste y aumentar tu confianza en la plataforma.</p>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta principal con la información del usuario -->
                <div class="tarjeta perfil-tarjeta">
                    
                    <!-- Nombre y nivel de validación en una sola línea -->
                    <div class="perfil-encabezado-linea">
                        <h2><?= escapar($usuario['nombre']) ?></h2>
                        <span class="insignia <?= escapar($claseInsignia) ?>">
                            Nivel <?= escapar($usuario['validacion_nombre']) ?>
                        </span>
                    </div>

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

                <!-- Formulario para ingresar el documento (aparece abajo solo si no lo tiene) -->
                <?php if (!$tieneDocumento): ?>
                    <div class="tarjeta perfil-form-tarjeta">
                        <h3>Completá tu documento</h3>
                        <p class="perfil-form-descripcion">Ingresá tu tipo y número de documento para validar tu cuenta.</p>
                        
                        <form action="actualizar_documento.php" method="post">
                            <div class="campo">
                                <label for="tipo_documento">Tipo de documento</label>
                                <select id="tipo_documento" name="tipo_documento" required>
                                    <option value="DNI">DNI</option>
                                    <option value="Pasaporte">Pasaporte</option>
                                </select>
                            </div>

                            <div class="campo">
                                <label for="documento">Número de documento</label>
                                <input type="text" id="documento" name="documento" placeholder="Ej. 35123456" required />
                            </div>

                            <button type="submit" class="boton">Guardar documento</button>
                        </form>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/parciales/pie.php'; ?>