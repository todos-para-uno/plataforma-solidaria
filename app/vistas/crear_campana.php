<?php
/*
 * Vista para crear una campaña: solo la presentación del formulario.
 */
$tituloPagina = 'Crear campana';
$paginaActual = 'crear_campana';
$estilosPagina = ['crear_campana.css'];

// Valores por defecto para la vista
$usuarioActual = $usuarioActual ?? null;
$errores = $errores ?? [];
$nombre = $nombre ?? '';
$categoria = $categoria ?? '';
$descripcion = $descripcion ?? '';
$tipoAyuda = $tipoAyuda ?? 'dinero';
$objetivo = $objetivo ?? '';
$insumo = $insumo ?? '';
$cbu = $cbu ?? '';
$fechaLimite = $fechaLimite ?? '';
$ubicacionTexto = $ubicacionTexto ?? '';
$lat = $lat ?? '';
$lng = $lng ?? '';

$categorias = $categorias ?? [
    'alimentos' => 'Alimentos',
    'educacion' => 'Educación',
    'salud' => 'Salud',
    'animales' => 'Animales',
    'comunidad' => 'Comunidad',
    'emergencias' => 'Emergencias',
    'otro' => 'Otro',
];

require __DIR__ . '/parciales/cabecera.php';
?>

<!-- Celdas y dependencias CSS/JS para OpenStreetMap (Leaflet) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>

<section class="seccion" aria-labelledby="titulo-crear">
    <div class="contenedor">
        <div class="crear-interior">
            <p class="crear-volver"><a href="<?= escapar($rutas['explorar']) ?>">Volver a Explorar campañas</a></p>

            <div class="seccion-encabezado">
                <h1 id="titulo-crear">Crear campaña</h1>
                <p class="bajada">Completá los datos para empezar a recaudar. Los campos obligatorios están marcados.</p>
            </div>

            <?php if (!empty($errores)): ?>
                <div class="alerta alerta-error" role="alert">
                    <p>No pudimos publicar la campaña:</p>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?= escapar($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="crear-formulario" action="<?= escapar($rutas['crear_campana']) ?>" method="post">

                <!-- Información básica -->
                <section class="tarjeta crear-tarjeta" aria-labelledby="crear-basica">
                    <h2 id="crear-basica">Información básica</h2>
                    <div class="campo">
                        <label for="crear-nombre">Nombre de la campaña</label>
                        <input id="crear-nombre" name="nombre" type="text" maxlength="100"
                               value="<?= escapar($nombre) ?>" required aria-describedby="crear-nombre-ayuda">
                        <span class="campo-ayuda" id="crear-nombre-ayuda">Hasta 100 caracteres.</span>
                    </div>
                    <div class="campo">
                        <label for="crear-descripcion">Descripción</label>
                        <textarea id="crear-descripcion" name="descripcion" required
                                  aria-describedby="crear-descripcion-ayuda"><?= escapar($descripcion) ?></textarea>
                        <span class="campo-ayuda" id="crear-descripcion-ayuda">Contá para qué es la ayuda y cómo se va a usar.</span>
                    </div>
                </section>

                <!-- Categoría -->
                <section class="tarjeta crear-tarjeta" aria-labelledby="crear-categoria">
                    <h2 id="crear-categoria">Categoría</h2>
                    <div class="campo">
                        <label for="crear-categoria-campo">Elegí una categoría</label>
                        <select id="crear-categoria-campo" name="categoria" required>
                            <option value="">Seleccioná una opción</option>
                            <?php foreach ($categorias as $clave => $nombreCategoria): ?>
                                <option value="<?= escapar($clave) ?>"<?php if ($clave === $categoria): ?> selected<?php endif; ?>><?= escapar($nombreCategoria) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </section>

                <!-- Objetivo de la campaña (Dinero o Insumos dinámico) -->
                <section class="tarjeta crear-tarjeta" aria-labelledby="crear-objetivo">
                    <h2 id="crear-objetivo">Objetivo de la campaña</h2>
                    <div class="campo">
                        <label for="crear-tipo">¿Qué necesitás?</label>
                        <select id="crear-tipo" name="tipo_ayuda" required>
                            <option value="dinero"<?php if ($tipoAyuda === 'dinero'): ?> selected<?php endif; ?>>Dinero</option>
                            <option value="insumos"<?php if ($tipoAyuda === 'insumos'): ?> selected<?php endif; ?>>Insumos</option>
                        </select>
                    </div>

                    <!-- Campos exclusivos de DINERO -->
                    <div id="bloque-dinero">
                        <div class="campo">
                            <label for="crear-objetivo-campo">Objetivo a recaudar</label>
                            <div class="input-con-simbolo">
                                <span>$</span>
                                <input id="crear-objetivo-campo" name="objetivo" type="number" inputmode="numeric" min="1" step="1"
                                       value="<?= escapar($objetivo) ?>" aria-describedby="crear-objetivo-ayuda">
                            </div>
                            <span class="campo-ayuda" id="crear-objetivo-ayuda">Monto total en pesos que necesitás juntar.</span>
                        </div>
                        <div class="campo">
                            <label for="crear-cbu">CBU para recibir donaciones</label>
                            <input id="crear-cbu" name="cbu" type="text" inputmode="numeric" autocomplete="off" maxlength="22"
                                   value="<?= escapar($cbu) ?>" aria-describedby="crear-cbu-ayuda">
                            <span class="campo-ayuda" id="crear-cbu-ayuda">Son 22 números de tu cuenta bancaria o CVU.</span>
                        </div>
                    </div>

                    <!-- Campos exclusivos de INSUMOS -->
                    <div id="bloque-insumos" style="display: none;">
                        <div class="campo">
                            <label for="crear-insumo">Insumo que necesitás</label>
                            <input id="crear-insumo" name="insumo" type="text" value="<?= escapar($insumo) ?>"
                                   aria-describedby="crear-insumo-ayuda" placeholder="Ej: Mochilas, alimentos no perecederos, frazadas">
                            <span class="campo-ayuda" id="crear-insumo-ayuda">Especificá detalladamente qué tipo de elementos requerís.</span>
                        </div>
                    </div>
                </section>

                <!-- Ubicación y Mapa OpenStreetMap -->
                <section class="tarjeta crear-tarjeta" aria-labelledby="crear-ubicacion">
                    <h2 id="crear-ubicacion">Ubicación</h2>
                    <div class="campo">
                        <label for="crear-direccion">Dirección o referencia del lugar</label>
                        <input id="crear-direccion" name="ubicacion_texto" type="text"
                               value="<?= escapar($ubicacionTexto) ?>" placeholder="Ej: Av. Rivadavia 1234, CABA">
                    </div>
                    <div class="campo">
                        <label>Marcá la ubicación exacta en el mapa:</label>
                        <div id="mapa-osm"></div>
                        <input type="hidden" id="lat" name="lat" value="<?= escapar($lat) ?>">
                        <input type="hidden" id="lng" name="lng" value="<?= escapar($lng) ?>">
                        <span class="campo-ayuda">Hacé clic en el mapa o arrastrá el marcador para fijar las coordenadas.</span>
                    </div>
                </section>

                <!-- Fecha límite -->
                <section class="tarjeta crear-tarjeta" aria-labelledby="crear-fecha">
                    <h2 id="crear-fecha">Fecha límite</h2>
                    <div class="campo">
                        <label for="crear-fecha-campo">¿Hasta cuándo recaudás? (opcional)</label>
                        <input id="crear-fecha-campo" name="fecha_limite" type="date"
                               value="<?= escapar($fechaLimite) ?>" aria-describedby="crear-fecha-ayuda">
                        <span class="campo-ayuda" id="crear-fecha-ayuda">Dejalo vacío si no hay fecha límite.</span>
                    </div>
                </section>

                <button class="boton boton-ancho" type="submit">Publicar campaña</button>
            </form>
        </div>
    </div>
</section>

<!-- Script para la lógica dinámica de campos y el mapa de OpenStreetMap -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Lógica para alternar campos entre Dinero e Insumos
    const tipoSelect = document.getElementById('crear-tipo');
    const bloqueDinero = document.getElementById('bloque-dinero');
    const bloqueInsumos = document.getElementById('bloque-insumos');
    const inputObjetivo = document.getElementById('crear-objetivo-campo');
    const inputCbu = document.getElementById('crear-cbu');
    const inputInsumo = document.getElementById('crear-insumo');

    function actualizarFormulario() {
        if (tipoSelect.value === 'dinero') {
            bloqueDinero.style.display = 'block';
            bloqueInsumos.style.display = 'none';
            inputObjetivo.required = true;
            inputCbu.required = true;
            inputInsumo.required = false;
        } else {
            bloqueDinero.style.display = 'none';
            bloqueInsumos.style.display = 'block';
            inputObjetivo.required = false;
            inputCbu.required = false;
            inputInsumo.required = true;
        }
    }

    tipoSelect.addEventListener('change', actualizarFormulario);
    actualizarFormulario();

    // 2. Inicialización de OpenStreetMap con Leaflet
    let latVal = document.getElementById('lat').value ? parseFloat(document.getElementById('lat').value) : -34.6037;
    let lngVal = document.getElementById('lng').value ? parseFloat(document.getElementById('lng').value) : -58.3816;

    let map = L.map('mapa-osm').setView([latVal, lngVal], 13);

   L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    referrerPolicy: 'strict-origin-when-cross-origin',
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

    let marker = L.marker([latVal, lngVal], { draggable: true }).addTo(map);

    function actualizarCoordenadas(lat, lng) {
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
    }

    // Evento al arrastrar el marcador
    marker.on('dragend', function(e) {
        let position = marker.getLatLng();
        actualizarCoordenadas(position.lat, position.lng);
    });

    // Evento al hacer clic en el mapa
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        actualizarCoordenadas(e.latlng.lat, e.latlng.lng);
    });

    // 3. NUEVO: Buscar dirección automáticamente cuando el usuario termina de escribir
    const inputDireccion = document.getElementById('crear-direccion');
    if (inputDireccion) {
        inputDireccion.addEventListener('change', function() {
            let query = this.value.trim();
            if (!query) return;

            // Consultamos la API pública de geocodificación de Nominatim (OpenStreetMap)
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        let lat = parseFloat(data[0].lat);
                        let lon = parseFloat(data[0].lon);

                        // Centramos el mapa y movemos el marcador a la ubicación encontrada
                        map.setView([lat, lon], 15);
                        marker.setLatLng([lat, lon]);
                        
                        // Guardamos las coordenadas en los campos ocultos
                        actualizarCoordenadas(lat, lon);
                    } else {
                        alert('No se encontró la ubicación ingresada en el mapa. Probá agregando localidad o partido (ej: San Justo, Buenos Aires).');
                    }
                })
                .catch(error => {
                    console.error('Error al buscar la dirección:', error);
                });
        });
    }
});
</script>

<?php require __DIR__ . '/parciales/pie.php'; ?>
