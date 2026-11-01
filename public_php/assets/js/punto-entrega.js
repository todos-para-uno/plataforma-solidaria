'use strict';
document.addEventListener('DOMContentLoaded', () => {
    const provincia = document.getElementById('provincia');
    const localidad = document.getElementById('localidad');
    const catalogo = JSON.parse(document.getElementById('catalogo-entrega').textContent);
    provincia.addEventListener('change', () => {
        localidad.replaceChildren(new Option('Seleccioná una localidad', ''));
        (catalogo[provincia.value] || []).forEach(nombre => localidad.add(new Option(nombre, nombre)));
    });
    if (typeof L === 'undefined') {
        document.getElementById('mapa-entrega').textContent = 'No se pudo cargar el mapa. Podés ingresar las coordenadas en los campos de abajo.';
        return;
    }
    const lat = document.getElementById('latitud'), lng = document.getElementById('longitud');
    const valido = () => lat.value !== '' && lng.value !== '' && Number.isFinite(Number(lat.value)) && Number.isFinite(Number(lng.value)) && Math.abs(Number(lat.value)) <= 90 && Math.abs(Number(lng.value)) <= 180;
    const mapa = L.map('mapa-entrega').setView(valido() ? [Number(lat.value), Number(lng.value)] : [-34.6037, -58.3816], 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapa);
    let marcador = valido() ? L.marker([Number(lat.value), Number(lng.value)]).addTo(mapa) : null;
    function ubicar() {
        if (!valido()) return;
        const punto = [Number(lat.value), Number(lng.value)];
        if (marcador) marcador.setLatLng(punto);
        else marcador = L.marker(punto).addTo(mapa);
        mapa.panTo(punto);
    }
    mapa.on('click', e => {
        lat.value = e.latlng.lat.toFixed(6); lng.value = e.latlng.lng.toFixed(6); ubicar();
    });
    lat.addEventListener('change', ubicar); lng.addEventListener('change', ubicar);
});
