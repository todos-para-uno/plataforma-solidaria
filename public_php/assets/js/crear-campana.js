'use strict';
document.addEventListener('DOMContentLoaded', () => {
    const tipo = document.getElementById('crear-tipo');
    function alternar() {
        ['insumos', 'dinero', 'ubicacion'].forEach(nombre => {
            const bloque = document.getElementById('bloque-' + nombre);
            const activo = nombre === 'dinero' ? tipo.value === 'dinero' : tipo.value === 'insumos';
            bloque.hidden = !activo; bloque.disabled = !activo;
        });
        document.dispatchEvent(new Event('crear:ubicacion-visible'));
    }
    tipo.addEventListener('change', alternar); alternar();
    const lista = document.getElementById('lista-insumos');
    const agregar = document.getElementById('agregar-insumo');
    let indice = lista.children.length;
    function actualizar() {
        agregar.disabled = lista.children.length >= 20;
        lista.querySelectorAll('[data-quitar-insumo]').forEach(boton => boton.disabled = lista.children.length <= 1);
        document.getElementById('estado-insumos').textContent = lista.children.length + ' de 20 insumos.';
    }
    agregar.addEventListener('click', () => {
        if (lista.children.length >= 20) return;
        const plantilla = document.getElementById('plantilla-insumo');
        lista.insertAdjacentHTML('beforeend', plantilla.innerHTML.replaceAll('__INDICE__', String(indice++)));
        actualizar(); lista.lastElementChild.querySelector('select').focus();
    });
    lista.addEventListener('click', e => {
        const boton = e.target.closest('[data-quitar-insumo]');
        if (!boton || lista.children.length <= 1) return;
        const fila = boton.closest('.crear-insumo-fila');
        const destino = fila.nextElementSibling || fila.previousElementSibling;
        fila.remove(); actualizar(); destino.querySelector('select').focus();
    });
    actualizar();
});
