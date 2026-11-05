<?php
/*
 * Reactivación de cuenta: reutiliza el diseño y los componentes del ingreso.
 * $rutaBase, $rutaAssets y $csrfToken provienen del controlador.
 */
$tituloPagina = 'Reactivar cuenta';
$paginaActual = 'ingresar';
$usuarioActual = null;
$estilosPagina = ['ingresar.css'];
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion" aria-labelledby="titulo-reactivar">
    <div class="contenedor">
        <div class="ingresar-interior">
            <div class="seccion-encabezado">
                <h1 id="titulo-reactivar">Tu cuenta está desactivada</h1>
                <p class="bajada">Desactivaste tu cuenta anteriormente. ¿Querés reactivarla para volver a ingresar a TodosPara.Uno?</p>
            </div>

            <form class="tarjeta formulario-reactivacion" action="<?= escapar($rutaBase) ?>/index.php?ruta=reactivar" method="post">
                <input type="hidden" name="csrf_token" value="<?= escapar($csrfToken) ?>">
                <div class="campo">
                    <p>Si reactivás tu cuenta, podrás volver a acceder a tus campañas, donaciones y mensajes.</p>
                </div>
                <div class="acciones-reactivacion">
                <button class="boton boton-ancho" name="decision" value="reactivar" type="submit">
                    Sí, reactivar mi cuenta
                </button>
                <button class="boton boton-ancho" name="decision" value="volver" type="submit">
                    No, volver al ingreso
                </button>
                </div>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
