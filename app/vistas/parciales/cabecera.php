<?php
/*
 * Estructura inicial de las pantallas: título, estilos y menú compartido.
 * Abre <html>, <head>, <body> y <main>. Completá la pantalla incluyendo pie.php al final.
 *
 * La vista puede definir estas variables antes de incluir la cabecera:
 *   $tituloPagina   ?string  Título de la pestaña. Con null, se muestra solo la marca.
 *   $paginaActual   ?string  Clave de rutas.php para marcar el enlace activo. Ej.: 'explorar'.
 *   $usuarioActual  ?array   null para el menú de visitante. Para el menú de usuario:
 *                            ['nombre' => string, 'admin' => bool, 'mensajesSinLeer' => int]
 *   $rutaBase       string   URL donde está index.php, sin barra final. rutas.php la usa.
 *   $rutaAssets     string   URL de la carpeta assets, sin barra final.
 *   $estilosPagina  string[] Hojas adicionales dentro de assets/css. Ej.: ['registro.css'].
 *
 * Todas son opcionales. El punto de entrada público define $rutaBase y $rutaAssets;
 * los valores por defecto permiten abrir las vistas directamente en la vista previa local.
 * $usuarioActual solo decide qué mostrar: la autorización debe comprobarse en el servidor.
 *
 * Ejemplo desde un archivo en app/vistas/:
 *   <?php $tituloPagina = 'Explorar campañas'; $paginaActual = 'explorar';
 *         require __DIR__ . '/parciales/cabecera.php'; ?>
 *   ... contenido de la pantalla ...
 *   <?php require __DIR__ . '/parciales/pie.php'; ?>
 */
require_once __DIR__ . '/ayudantes.php';

$rutas = require __DIR__ . '/rutas.php';
$tituloPagina = $tituloPagina ?? null;
$paginaActual = $paginaActual ?? null;
$usuarioActual = $usuarioActual ?? null;
$rutaAssets = $rutaAssets ?? '../../public_php/assets';
$estilosPagina = $estilosPagina ?? [];

?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $tituloPagina ? escapar($tituloPagina) . ' | ' : '' ?>todospara.uno</title>
    <link rel="stylesheet" href="<?= escapar($rutaAssets) ?>/css/base.css">
    <?php foreach ($estilosPagina as $hoja): ?>
        <link rel="stylesheet" href="<?= escapar($rutaAssets) ?>/css/<?= escapar($hoja) ?>">
    <?php endforeach; ?>
    <script src="<?= escapar($rutaAssets) ?>/js/base.js" defer></script>
</head>
<body>
<a class="saltar-contenido" href="#contenido">Ir al contenido</a>

<header class="cabecera">
    <div class="contenedor cabecera-interior">
        <a class="cabecera-logo" href="<?= escapar($rutas['inicio']) ?>">
            <img src="<?= escapar($rutaAssets) ?>/imagenes/logo-todosparauno.png"
                 alt="todospara.uno, ir al inicio" width="640" height="109">
        </a>

        <button class="menu-boton" type="button" aria-expanded="false" aria-controls="menu" hidden>Menú</button>

        <nav class="menu" id="menu" aria-label="Principal">
            <ul class="menu-principal">
                <li><a href="<?= escapar($rutas['explorar']) ?>"<?= marcarActual('explorar', $paginaActual) ?>>Explorar campañas</a></li>
                <li><a href="<?= escapar($rutas['como_funciona']) ?>"<?= marcarActual('como_funciona', $paginaActual) ?>>Cómo funciona</a></li>
                <li><a href="<?= escapar($rutas['contacto']) ?>"<?= marcarActual('contacto', $paginaActual) ?>>Contacto</a></li>
            </ul>

            <ul class="menu-cuenta">
                <?php if ($usuarioActual === null): ?>
                    <li><a href="<?= escapar($rutas['ingresar']) ?>"<?= marcarActual('ingresar', $paginaActual) ?>>Ingresar</a></li>
                    <li><a class="boton boton-chico" href="<?= escapar($rutas['registro']) ?>"<?= marcarActual('registro', $paginaActual) ?>>Crear cuenta</a></li>
                <?php else: ?>
                    <li>
                        <a href="<?= escapar($rutas['mensajes']) ?>"<?= marcarActual('mensajes', $paginaActual) ?>>
                            Mensajes
                            <?php if (($usuarioActual['mensajesSinLeer'] ?? 0) > 0): ?>
                                <span class="contador"><?= (int) $usuarioActual['mensajesSinLeer'] ?><span class="solo-lectores"> sin leer</span></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <!-- El navegador permite abrir este menú con <details>, incluso sin JavaScript. -->
                        <details class="menu-usuario">
                            <summary><?= escapar($usuarioActual['nombre']) ?></summary>
                            <ul>
                                <li><a href="<?= escapar($rutas['perfil']) ?>"<?= marcarActual('perfil', $paginaActual) ?>>Mi cuenta</a></li>
                                <li><a href="<?= escapar($rutas['mis_campanas']) ?>"<?= marcarActual('mis_campanas', $paginaActual) ?>>Mis campañas</a></li>
                                <li><a href="<?= escapar($rutas['mis_donaciones']) ?>"<?= marcarActual('mis_donaciones', $paginaActual) ?>>Mis donaciones</a></li>
                                <?php if (!empty($usuarioActual['admin'])): ?>
                                    <li><a href="<?= escapar($rutas['administracion']) ?>"<?= marcarActual('administracion', $paginaActual) ?>>Administración</a></li>
                                <?php endif; ?>
                                <li>
                                    <form action="<?= escapar($rutas['salir']) ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken ?? '') ?>">
                                        <button class="menu-salir" type="submit">Salir</button>
                                    </form>
                                </li>
                            </ul>
                        </details>
                    </li>
                    <li><a class="boton boton-chico" href="<?= escapar($rutas['crear_campana']) ?>"<?= marcarActual('crear_campana', $paginaActual) ?>>Crear campaña</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main id="contenido" tabindex="-1">
