<section class="bienvenida contenedor" aria-labelledby="bienvenida-titulo">
    <div class="bienvenida-tarjeta" data-bienvenida>
        <div class="bienvenida-texto">
            <p class="bienvenida-estado"><span aria-hidden="true">✓</span> Sesión iniciada</p>
            <h2 id="bienvenida-titulo">¡Hola, <?= escapar($usuarioActual['nombre']) ?>!</h2>
            <p class="bienvenida-marca">Te damos la bienvenida a
                <img src="<?= escapar($rutaAssets) ?>/imagenes/logo-todosparauno.png"
                     alt="todospara.uno" width="640" height="109">
            </p>
            <p class="bienvenida-gracias">Ya estás en tu cuenta. Gracias por ser parte.</p>
        </div>
        <button class="bienvenida-cerrar" type="button" aria-label="Cerrar mensaje de bienvenida" data-cerrar-bienvenida hidden>
            <span aria-hidden="true">×</span>
        </button>
    </div>
</section>
