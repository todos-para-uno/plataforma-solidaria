<?php
// Incluí este archivo al final de cada pantalla, después de su contenido.
// Cierra la estructura que abrió cabecera.php y reutiliza sus $rutas y $rutaAssets.
?>
</main>

<footer class="pie">
    <div class="contenedor pie-interior">
        <div class="pie-marca">
            <img src="<?= escapar($rutaAssets) ?>/imagenes/logo-todosparauno-claro.png" alt="todospara.uno" width="640" height="109" loading="lazy">
            <p>Campañas solidarias para donar dinero o insumos a quien lo necesita.</p>
        </div>

        <nav aria-labelledby="pie-plataforma">
            <h2 id="pie-plataforma">Plataforma</h2>
            <ul>
                <li><a href="<?= escapar($rutas['explorar']) ?>">Explorar campañas</a></li>
                <li><a href="<?= escapar($rutaCrearCampana) ?>">Crear una campaña</a></li>
            </ul>
        </nav>

        <nav aria-labelledby="pie-ayuda">
            <h2 id="pie-ayuda">Ayuda</h2>
            <ul>
                <li><a href="<?= escapar($rutas['preguntas']) ?>">Preguntas frecuentes</a></li>
                <li><a href="<?= escapar($rutas['contacto']) ?>">Contacto</a></li>
            </ul>
        </nav>
    </div>

    <div class="contenedor pie-creditos">
        <p>Proyecto académico del Grupo 4, Tecnicatura Universitaria en Programación (UTN), <?= date('Y') ?>.</p>
    </div>
</footer>
<?php
if ($usuarioActual !== null) {
    // Aislar las variables del parcial de los formularios y datos de la página.
    (static function ($usuario, string $rutaCuentaMp): void {
        require __DIR__ . '/modal_cuenta.php';
    })($cuentaParaModal, $rutas['mercadopago'] ?? '');
}
?>
</body>
</html>
