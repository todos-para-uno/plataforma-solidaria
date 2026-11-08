'use strict';
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const codigo = fs.readFileSync(require('node:path').join(__dirname, '../../public_php/assets/js/compartir-campana.js'), 'utf8');

function preparar({ seguro = true, clipboard, share, copiaAlternativa = false, fallaHistoria = false } = {}) {
    const elementos = {};
    for (const clave of ['enlace', 'estado', 'whatsapp', 'telegram', 'controles', 'copiar', 'nativo', 'historia', 'mas']) {
        elementos[clave] = {
            hidden: true, dataset: {}, value: '', textContent: '', eventos: {},
            addEventListener(tipo, fn) { this.eventos[tipo] = fn; },
            focus() { this.enfocado = true; }, select() { this.seleccionado = true; }, setSelectionRange() {},
        };
    }
    elementos.enlace.dataset.ruta = './index.php?ruta=campana&id=42';
    const seccion = {
        dataset: { titulo: 'Abrigo & alimentos 🧡', descripcion: 'Recolectamos alimentos y abrigo para las familias del barrio.' },
        querySelector(selector) { return elementos[selector.slice('[data-compartir-'.length, -1)]; },
    };
    elementos.mas.open = false;
    elementos.descargas = [];
    const archivoURL = class extends URL {};
    archivoURL.createObjectURL = () => 'blob:historia';
    archivoURL.revokeObjectURL = () => {};
    const protocolo = seguro ? 'https:' : 'http:';
    vm.runInNewContext(codigo, {
        URL: archivoURL, navigator: { clipboard, share }, setTimeout: () => {},
        crearHistoriaCampana: async datos => { elementos.datosHistoria = datos; if (fallaHistoria) throw Error('Sin canvas'); return {}; },
        window: { isSecureContext: seguro, location: { href: `${protocolo}//ejemplo.org/solidaria/index.php?ruta=mis_campanas&privado=secreto#cuenta` } },
        document: {
            querySelectorAll: () => [seccion], execCommand: () => copiaAlternativa,
            body: { append() {} },
            createElement: () => ({ click() { elementos.descargas.push(this.download); }, remove() {} }),
        },
    });
    return elementos;
}

(async () => {
    let copiado, compartido;
    const e = preparar({ clipboard: { writeText: async texto => { copiado = texto; } }, share: async datos => { compartido = datos; } });
    const url = 'https://ejemplo.org/solidaria/index.php?ruta=campana&id=42';
    assert.equal(e.enlace.value, url);
    assert.equal(e.controles.hidden, false);
    const telegram = new URL(e.telegram.href);
    assert.equal(telegram.searchParams.get('url'), url);
    assert.match(telegram.searchParams.get('text'), /Abrigo & alimentos 🧡/);
    assert.equal(new URL(e.whatsapp.href).searchParams.get('text'), `Sumate a mi campaña solidaria: Abrigo & alimentos 🧡\n${url}`);
    await e.copiar.eventos.click();
    assert.equal(copiado, url);
    assert.match(e.estado.textContent, /Enlace copiado/);
    await e.historia.eventos.click();
    assert.deepEqual(e.descargas, ['historia-campana-42.png']);
    assert.equal(e.datosHistoria.titulo, 'Abrigo & alimentos 🧡');
    assert.equal(e.datosHistoria.descripcion, 'Recolectamos alimentos y abrigo para las familias del barrio.');
    assert.equal(e.datosHistoria.url, url);
    assert.equal(e.historia.disabled, false);
    const errorHistoria = preparar({ fallaHistoria: true });
    await errorHistoria.historia.eventos.click();
    assert.equal(errorHistoria.descargas.length, 0);
    assert.match(errorHistoria.estado.textContent, /No pudimos preparar/);
    assert.equal(errorHistoria.historia.disabled, false);
    await e.nativo.eventos.click();
    assert.equal(compartido.url, url);
    const local = preparar({ seguro: false, copiaAlternativa: true });
    await local.copiar.eventos.click();
    assert.match(local.estado.textContent, /Enlace copiado/);
    assert.equal(local.nativo.hidden, true);
    const denegado = preparar({ clipboard: { writeText: async () => { throw Error('Permiso denegado'); } } });
    await denegado.copiar.eventos.click();
    assert.equal(denegado.enlace.seleccionado, true);
    assert.match(denegado.estado.textContent, /Copialo/);
    const cancelado = preparar({ share: async () => { throw { name: 'AbortError' }; } });
    await cancelado.nativo.eventos.click();
    assert.equal(cancelado.estado.textContent, '');
    const falla = preparar({ share: async () => { throw Error('No disponible'); } });
    await falla.nativo.eventos.click();
    assert.match(falla.estado.textContent, /No pudimos/);
    console.log('OK: enlaces públicos, codificación, copia, HTTP local, permisos denegados y compartir nativo.');
})().catch(error => { console.error(error); process.exitCode = 1; });
