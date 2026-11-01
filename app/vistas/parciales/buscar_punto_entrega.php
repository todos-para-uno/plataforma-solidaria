<div class="campo entrega-buscador">
    <label for="buscar-direccion">Buscar un lugar o dirección</label>
    <div class="entrega-busqueda-controles">
        <input type="search" id="buscar-direccion" maxlength="200" placeholder="Ej.: UTN Haedo o París 532, Haedo" aria-describedby="ayuda-buscar-direccion" autocomplete="off">
        <button type="button" class="boton boton-secundario" id="buscar-lugar">Buscar en el mapa</button>
    </div>
    <p class="campo-ayuda" id="ayuda-buscar-direccion">Escribí el lugar o la dirección y elegí un resultado. Podés ajustar el punto haciendo clic en el mapa.</p>
    <p id="estado-busqueda" role="status" aria-live="polite"></p>
    <ul id="resultados-direccion" class="entrega-resultados" aria-label="Lugares encontrados" hidden></ul>
    <p class="campo-ayuda">Búsqueda: Photon · Datos de <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>.</p>
</div>
