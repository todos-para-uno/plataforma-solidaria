'use strict';
// Comprueba los eventos de la búsqueda sin depender del proveedor ni de los mapas externos.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
class Elemento extends EventTarget {
    constructor() { super(); this.value = ''; this.children = []; this.disabled = false; this.hidden = false; this.textContent = ''; }
    replaceChildren(...hijos) { this.children = hijos; this.value = ''; }
    add(hijo) { this.children.push(hijo); }
    append(hijo) { this.children.push(hijo); }
    closest() { return null; }
    focus() {}
    click() { this.dispatchEvent(new Event('click')); }
}
const ids = ['provincia','localidad','catalogo-entrega','estado-busqueda','buscar-lugar','mapa-entrega','latitud','longitud','buscar-direccion','resultados-direccion','referencia'];
const elementos = Object.fromEntries(ids.map(id => [id, new Elemento()]));
elementos['catalogo-entrega'].textContent = JSON.stringify({'Buenos Aires':['Haedo','La Plata']});
const formulario = new Elemento(); elementos.latitud.form = formulario;
const documento = new EventTarget();
documento.getElementById = id => elementos[id] || null;
documento.createElement = () => new Elemento();
global.document = documento;
global.Option = class { constructor(texto, valor) { this.textContent = texto; this.value = valor; } };
global.requestAnimationFrame = fn => fn();
const eventos = {};
const mapa = { setView() { return this; }, on(nombre, fn) { eventos[nombre] = fn; }, invalidateSize() {} };
global.L = { map: () => mapa, tileLayer: () => ({addTo(){}}), marker: () => ({addTo(){return this;}, on(){}, setLatLng(){}}) };
let llamadas = 0, falla = false;
global.fetch = async () => {
    llamadas++; if (falla) throw Error('Sin red');
    return {ok:true, json:async () => ({features:[{geometry:{type:'Point',coordinates:[-58.602,-34.640]},properties:{name:'UTN Haedo',city:'Haedo',state:'Buenos Aires'}}]})};
};
vm.runInThisContext(fs.readFileSync(require('node:path').join(__dirname, '../../public_php/assets/js/punto-entrega.js'),'utf8'));
documento.dispatchEvent(new Event('DOMContentLoaded'));
const esperar = () => new Promise(resolve => setImmediate(resolve));
(async () => {
    elementos['buscar-direccion'].value = 'UTN Haedo'; elementos['buscar-lugar'].click(); await esperar();
    assert.equal(elementos['resultados-direccion'].children.length,1);
    elementos['resultados-direccion'].children[0].children[0].click();
    assert.equal(elementos.latitud.value,'-34.640000'); assert.equal(elementos.longitud.value,'-58.602000');
    assert.equal(elementos.provincia.value,'Buenos Aires'); assert.equal(elementos.localidad.value,'Haedo');
    assert.equal(elementos.referencia.value,'UTN Haedo, Haedo, Buenos Aires');
    elementos['buscar-lugar'].click(); await esperar(); const anteriores = llamadas;
    elementos['buscar-lugar'].click(); await esperar(); assert.equal(llamadas,anteriores);
    eventos.click({latlng:{lat:-34.65,lng:-58.61}}); assert.equal(elementos.latitud.value,'-34.650000');
    falla = true; elementos['buscar-direccion'].value = 'Otra dirección'; elementos['buscar-lugar'].click(); await esperar();
    assert.match(elementos['estado-busqueda'].textContent,/No pudimos/); assert.equal(elementos.latitud.value,'-34.650000');
    elementos.latitud.value = ''; const submit = new Event('submit',{cancelable:true}); formulario.dispatchEvent(submit);
    assert.equal(submit.defaultPrevented,true);
    console.log('OK: búsqueda, selección, ubicación automática, caché, ajuste manual, error de red y punto obligatorio.');
})().catch(error => { console.error(error); process.exitCode = 1; });
