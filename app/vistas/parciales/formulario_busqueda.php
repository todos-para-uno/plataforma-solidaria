<?php
// El controlador prepara filtros, categorías y errores antes de incluir la vista.
?>
<form class="tarjeta buscador-formulario" action="<?= escapar($rutas['resultados_buscar']) ?>" method="get">
    <input type="hidden" name="ruta" value="resultados_buscar">
    <?php if ($errores !== []): ?>
        <div class="alerta alerta-error" role="alert">
            <?php foreach ($errores as $error): ?>
                <p><?= escapar($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="buscador-filtros">
        <div class="campo">
            <label for="buscar">Palabra clave</label>
            <div class="buscador-contenedor-input">
                <svg class="buscador-icono" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" id="buscar" name="buscar" maxlength="150"
                       value="<?= escapar($buscar) ?>" placeholder="Ej. alimentos, abrigo…">
            </div>
        </div>
        <div class="campo">
            <label for="id_categoria_campana">Categoría de campaña</label>
            <select id="id_categoria_campana" name="id_categoria_campana">
                <option value="">Todas las categorías</option>
                <?php foreach ($categoriasCampanas as $categoria): ?>
                    <option value="<?= (int) $categoria['id'] ?>"<?= $idCategoria === (int) $categoria['id'] ? ' selected' : '' ?>><?= escapar($categoria['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="buscador-acciones">
        <button type="submit" class="boton">Buscar campañas</button>
        <a href="<?= escapar($rutas['explorar']) ?>" class="boton boton-secundario">Limpiar filtros</a>
    </div>
    <p class="buscador-ayuda texto-secundario">Buscamos en el nombre y la descripción de las campañas activas. Podés dejar los filtros vacíos para verlas todas.</p>
</form>
