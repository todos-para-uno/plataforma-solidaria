'use strict';
document.addEventListener('DOMContentLoaded', () => {
    const provincia = document.getElementById('provincia');
    const localidad = document.getElementById('localidad');
    const catalogo = JSON.parse(document.getElementById('catalogo-entrega').textContent);
    function localidades() {
        localidad.replaceChildren(new Option('Seleccioná una localidad', ''));
        (catalogo[provincia.value] || []).forEach(nombre => localidad.add(new Option(nombre, nombre)));
    }
    provincia.addEventListener('change', localidades);
    const estado = document.getElementById('estado-busqueda');
    const buscar = document.getElementById('buscar-lugar');
    if (typeof L === 'undefined') {
        document.getElementById('mapa-entrega').textContent = 'No se pudo cargar el mapa. Revisá tu conexión y recargá la página para elegir el punto de entrega.';
        if (buscar) buscar.disabled = true;
        return;
    }
    const lat = document.getElementById('latitud'), lng = document.getElementById('longitud');
    const valido = () => lat.value !== '' && lng.value !== '' && Number.isFinite(Number(lat.value)) && Number.isFinite(Number(lng.value)) && Math.abs(Number(lat.value)) <= 90 && Math.abs(Number(lng.value)) <= 180;
    const mapa = L.map('mapa-entrega').setView(valido() ? [Number(lat.value), Number(lng.value)] : [-34.6037, -58.3816], 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapa);
    document.addEventListener('crear:ubicacion-visible', () => requestAnimationFrame(() => mapa.invalidateSize()));
    let marcador;
    function marcar(y, x, acercar = false) {
        if (!Number.isFinite(y) || !Number.isFinite(x) || Math.abs(y) > 90 || Math.abs(x) > 180) return;
        lat.value = y.toFixed(6); lng.value = x.toFixed(6);
        if (marcador) marcador.setLatLng([y, x]);
        else {
            marcador = L.marker([y, x], { draggable: true }).addTo(mapa);
            marcador.on('dragend', () => {
                const punto = marcador.getLatLng(); marcar(punto.lat, punto.lng);
                if (estado) estado.textContent = 'Punto ajustado en el mapa. Revisá la dirección y la localidad antes de guardar.';
            });
        }
        if (acercar) mapa.setView([y, x], 16);
    }
    if (valido()) marcar(Number(lat.value), Number(lng.value));
    mapa.on('click', e => {
        marcar(e.latlng.lat, e.latlng.lng);
        if (estado) estado.textContent = 'Punto seleccionado en el mapa. Revisá la dirección y la localidad antes de guardar.';
    });
    // Impedir guardar una campaña de insumos sin haber elegido un lugar.
    lat.form.addEventListener('submit', e => {
        if (lat.disabled || lat.closest('fieldset[disabled]')) return;
        if (!valido()) {
            e.preventDefault();
            if (estado) estado.textContent = 'Buscá y elegí un resultado o marcá el lugar en el mapa antes de guardar.';
            document.getElementById('buscar-direccion')?.focus();
        }
    });
    if (!buscar) return;
    const entrada = document.getElementById('buscar-direccion');
    const resultados = document.getElementById('resultados-direccion');
    const referencia = document.getElementById('referencia') || document.getElementById('crear-direccion');
    const normalizar = valor => String(valor || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/^provincia de /, '').trim();
    function completarZona(p) {
        const alias = nombre => ['caba', 'capital federal', 'ciudad autonoma de buenos aires'].includes(normalizar(nombre)) ? 'caba' : normalizar(nombre);
        const prov = Object.keys(catalogo).find(nombre => alias(nombre) === alias(p.state));
        if (!prov) return false;
        provincia.value = prov; localidades();
        const candidatos = [p.city, p.district, p.locality, p.county, p.name].map(normalizar);
        const loc = catalogo[prov].find(nombre => candidatos.includes(normalizar(nombre)));
        if (loc) localidad.value = loc;
        return Boolean(loc);
    }
    const cache = new Map();
    let ocupada = false;
    async function consultar() {
        const texto = entrada.value.trim();
        if (ocupada) return;
        if (texto.length < 3) { estado.textContent = 'Escribí al menos 3 caracteres para buscar.'; return; }
        const consulta = [texto, localidad.value, provincia.value].filter(Boolean).join(', ');
        ocupada = true; buscar.disabled = true; resultados.hidden = true; resultados.replaceChildren();
        estado.textContent = 'Buscando lugares…';
        const abortar = new AbortController();
        const plazo = setTimeout(() => abortar.abort(), 10000);
        try {
            let datos = cache.get(consulta);
            if (!datos) {
                const url = new URL('https://photon.komoot.io/api/');
                url.search = new URLSearchParams({ q: consulta, limit: '5', countrycode: 'AR' });
                const respuesta = await fetch(url, { signal: abortar.signal, credentials: 'omit' });
                if (!respuesta.ok) throw new Error('Servicio no disponible');
                datos = await respuesta.json();
                if (!Array.isArray(datos.features)) throw new Error('Respuesta no válida');
                if (cache.size >= 20) cache.delete(cache.keys().next().value);
                cache.set(consulta, datos);
            }
            const lugares = datos.features.filter(f => f.geometry?.type === 'Point' && Array.isArray(f.geometry.coordinates) && f.geometry.coordinates.length >= 2 && f.geometry.coordinates.slice(0, 2).every(Number.isFinite) && Math.abs(f.geometry.coordinates[0]) <= 180 && Math.abs(f.geometry.coordinates[1]) <= 90).slice(0, 5);
            estado.textContent = lugares.length ? 'Elegí el lugar correcto de la lista.' : 'No encontramos ese lugar. Probá con su nombre completo y localidad, o con la dirección.';
            lugares.forEach(f => {
                const p = f.properties || {};
                const etiqueta = [...new Set([p.name, [p.street, p.housenumber].filter(Boolean).join(' '), p.city || p.district, p.state].filter(Boolean))].join(', ') || texto;
                const li = document.createElement('li'), boton = document.createElement('button');
                boton.type = 'button'; boton.className = 'boton boton-secundario'; boton.textContent = etiqueta;
                boton.addEventListener('click', () => {
                    marcar(f.geometry.coordinates[1], f.geometry.coordinates[0], true);
                    const zonaCompleta = completarZona(p);
                    if (referencia) referencia.value = etiqueta.slice(0, 150);
                    entrada.value = etiqueta; resultados.hidden = true;
                    estado.textContent = 'Lugar seleccionado: ' + etiqueta + (zonaCompleta ? '. Podés ajustar el marcador.' : '. Completá o revisá la provincia y localidad.');
                    entrada.focus();
                });
                li.append(boton); resultados.append(li);
            });
            resultados.hidden = lugares.length === 0;
        } catch (error) {
            estado.textContent = 'No pudimos completar la búsqueda. Intentá nuevamente o elegí el punto haciendo clic en el mapa.';
        } finally {
            clearTimeout(plazo); ocupada = false; buscar.disabled = false;
        }
    }
    buscar.addEventListener('click', consultar);
    entrada.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); consultar(); } });
});
