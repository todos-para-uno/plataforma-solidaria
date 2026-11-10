'use strict';

// Mejora progresiva: sin JavaScript, todos los campos y el envío normal siguen disponibles.
document.addEventListener('DOMContentLoaded', () => {
    const editor = document.querySelector('.crear-editor');
    if (!editor) return;
    const form = editor.querySelector('.crear-formulario');
    const menu = editor.querySelector('.crear-editor-menu');
    const previa = editor.querySelector('.crear-editor-previa');
    const detallesPrevia = previa.querySelector('details');
    const acciones = editor.querySelector('.crear-editor-acciones');
    const anterior = acciones.querySelector('[data-editor-anterior]');
    const siguiente = acciones.querySelector('[data-editor-siguiente]');
    const publicar = document.getElementById('crear-publicar');
    const tipo = document.getElementById('crear-tipo');
    const escritorio = window.matchMedia('(min-width: 1024px)');
    const paneles = [...form.querySelectorAll('[data-editor-panel]')];
    const enlaces = [...menu.querySelectorAll('[data-editor-enlace]')];
    const nombres = { historia: 'Historia', fotos: 'Fotos', objetivos: 'Objetivos', entrega: 'Entrega', revision: 'Revisión' };
    const destinos = Object.fromEntries(paneles.map(panel => [panel.dataset.editorPanel, panel]));
    const pesos = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 2 });
    const cantidad = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 });
    let activo = form.dataset.editorErrores === 'true' ? 'revision' : 'historia';
    let fotos = 0;
    let portada = '';
    let actualizacionPendiente = false;

    const valor = id => document.getElementById(id).value.trim();
    const esDinero = () => tipo.value === 'dinero';
    const recorrido = () => ['historia', 'fotos', 'objetivos', ...(esDinero() ? [] : ['entrega']), 'revision'];
    const puntoValido = () => valor('latitud') !== '' && valor('longitud') !== ''
        && Number.isFinite(Number(valor('latitud'))) && Number.isFinite(Number(valor('longitud')))
        && Math.abs(Number(valor('latitud'))) <= 90 && Math.abs(Number(valor('longitud'))) <= 180;
    const camposInvalidos = panel => [...panel.querySelectorAll('input, select, textarea')]
        .filter(campo => campo.willValidate && !campo.validity.valid);
    const errorFotos = () => !document.getElementById('crear-imagenes-error').hidden;

    function seccionCompleta(nombre) {
        if (nombre === 'fotos') return !errorFotos();
        return camposInvalidos(destinos[nombre]).length === 0 && (nombre !== 'entrega' || puntoValido());
    }

    function mostrar(nombre, enfocar = false) {
        const orden = recorrido();
        activo = orden.includes(nombre) ? nombre : 'objetivos';
        paneles.forEach(panel => {
            const inactivo = panel.dataset.editorPanel !== activo;
            panel.toggleAttribute('data-editor-inactivo', inactivo);
            panel.inert = escritorio.matches && inactivo;
        });
        enlaces.forEach(enlace => {
            const nombreEnlace = enlace.dataset.editorEnlace;
            enlace.closest('li').hidden = nombreEnlace === 'entrega' && esDinero();
            if (nombreEnlace === activo) enlace.setAttribute('aria-current', 'location');
            else enlace.removeAttribute('aria-current');
        });
        const indice = orden.indexOf(activo);
        anterior.hidden = indice === 0;
        siguiente.hidden = indice === orden.length - 1;
        siguiente.textContent = orden[indice + 1] === 'revision' ? 'Revisar campaña' : 'Continuar';
        document.dispatchEvent(new Event('crear:ubicacion-visible'));
        if (enfocar) {
            const panel = destinos[activo];
            const titulo = panel.querySelector('h2');
            titulo.tabIndex = -1;
            titulo.focus({ preventScroll: true });
            panel.scrollIntoView({ block: 'start', behavior: 'auto' });
        }
    }

    function objetivosTexto() {
        if (esDinero()) return Number(valor('crear-objetivo')) > 0
            ? pesos.format(Number(valor('crear-objetivo'))) : 'Definí el monto que necesitás.';
        const filas = [...document.querySelectorAll('#lista-insumos .crear-insumo-fila')];
        const objetivos = filas.map(fila => {
            const nombre = fila.querySelector('[name$="[nombre]"]').value.trim();
            const total = Number(fila.querySelector('[name$="[cantidad]"]').value);
            return nombre && total > 0 ? cantidad.format(total) + ' de ' + nombre : '';
        }).filter(Boolean);
        return objetivos.length ? objetivos.join('\n') : 'Definí los insumos que necesitás.';
    }

    function actualizar() {
        const categoria = document.getElementById('crear-categoria');
        const categoriaTexto = categoria.value ? categoria.selectedOptions[0].textContent : '';
        const ubicacionTexto = [valor('localidad'), valor('provincia')].filter(Boolean).join(', ');
        editor.querySelector('[data-previa-nombre]').textContent = valor('crear-nombre') || 'Nombre de tu campaña';
        editor.querySelector('[data-previa-descripcion]').textContent = valor('crear-descripcion') || 'Tu historia aparecerá acá mientras la escribís.';
        editor.querySelector('[data-previa-tipo]').textContent = esDinero() ? 'Dinero' : 'Insumos';
        const etiquetaCategoria = editor.querySelector('[data-previa-categoria]');
        etiquetaCategoria.hidden = !categoriaTexto;
        etiquetaCategoria.textContent = categoriaTexto;
        editor.querySelector('[data-previa-objetivo]').textContent = objetivosTexto();
        const entrega = editor.querySelector('[data-previa-entrega]');
        entrega.hidden = esDinero();
        entrega.textContent = ubicacionTexto || 'Elegí el punto de entrega.';
        const imagen = editor.querySelector('[data-previa-imagen]');
        imagen.hidden = !portada;
        if (portada && imagen.getAttribute('src') !== portada) imagen.src = portada;
        if (!portada) imagen.removeAttribute('src');
        editor.querySelector('[data-previa-sin-foto]').hidden = Boolean(portada);

        const resumen = editor.querySelector('[data-editor-resumen]');
        resumen.replaceChildren();
        const datos = [
            ['Nombre', valor('crear-nombre') || 'Sin completar'],
            ['Historia', valor('crear-descripcion') || 'Sin completar'],
            ['Categoría', categoriaTexto || 'Sin elegir'],
            ['Tipo de ayuda', esDinero() ? 'Dinero' : 'Insumos'],
            ['Objetivos', objetivosTexto()],
            ['Fotos', fotos ? fotos + (fotos === 1 ? ' foto seleccionada' : ' fotos seleccionadas') : 'Sin fotos (opcional)'],
        ];
        if (esDinero()) datos.push(['CBU o CVU', valor('crear-cbu') || 'Sin completar']);
        else datos.push(['Entrega', [ubicacionTexto, valor('crear-direccion'), puntoValido() ? 'Punto marcado en el mapa' : 'Falta marcar el punto en el mapa'].filter(Boolean).join('\n')]);
        datos.forEach(([etiqueta, contenido]) => {
            const termino = document.createElement('dt');
            const descripcion = document.createElement('dd');
            termino.textContent = etiqueta;
            descripcion.textContent = contenido;
            resumen.append(termino, descripcion);
        });

        const obligatorias = recorrido().filter(nombre => !['fotos', 'revision'].includes(nombre));
        const completas = obligatorias.filter(seccionCompleta).length;
        const progreso = editor.querySelector('[data-editor-progreso]');
        const textoProgreso = completas + ' de ' + obligatorias.length + ' secciones obligatorias completas.';
        if (progreso.textContent !== textoProgreso) progreso.textContent = textoProgreso;
        const faltantes = recorrido().filter(nombre => nombre !== 'revision' && !seccionCompleta(nombre));
        const lista = editor.querySelector('[data-editor-faltantes]');
        lista.replaceChildren();
        faltantes.forEach(nombre => {
            const li = document.createElement('li');
            const enlace = document.createElement('a');
            enlace.href = '#' + destinos[nombre].id;
            enlace.dataset.irSeccion = nombre;
            enlace.textContent = nombre === 'fotos' ? 'Revisá las fotos seleccionadas.' : 'Completá ' + nombres[nombre].toLowerCase() + '.';
            li.append(enlace);
            lista.append(li);
        });
        editor.querySelector('[data-editor-pendientes]').hidden = faltantes.length === 0;
        editor.querySelector('[data-editor-listo]').hidden = faltantes.length > 0;
        enlaces.forEach(enlace => {
            const nombre = enlace.dataset.editorEnlace;
            if (nombre === 'revision') return;
            const completo = seccionCompleta(nombre);
            enlace.dataset.completo = String(completo && (nombre !== 'fotos' || fotos > 0));
            enlace.querySelector('[data-editor-estado]').textContent = nombre === 'fotos' && !fotos && completo
                ? 'Opcional' : completo ? 'Completo' : 'Pendiente';
        });
        if (!recorrido().includes(activo)) mostrar('objetivos');
        enlaces.find(enlace => enlace.dataset.editorEnlace === 'entrega').closest('li').hidden = esDinero();
    }

    function programarActualizacion() {
        if (actualizacionPendiente) return;
        actualizacionPendiente = true;
        queueMicrotask(() => {
            actualizacionPendiente = false;
            actualizar();
        });
    }

    menu.addEventListener('click', evento => {
        const enlace = evento.target.closest('[data-editor-enlace]');
        if (!enlace) return;
        evento.preventDefault();
        mostrar(enlace.dataset.editorEnlace, true);
        actualizar();
    });
    form.addEventListener('click', evento => {
        const enlace = evento.target.closest('[data-ir-seccion]');
        if (!enlace) return;
        evento.preventDefault();
        mostrar(enlace.dataset.irSeccion, true);
        const campo = camposInvalidos(destinos[activo])[0];
        if (campo) campo.focus();
    });
    anterior.addEventListener('click', () => mostrar(recorrido()[recorrido().indexOf(activo) - 1], true));
    siguiente.addEventListener('click', () => {
        actualizar();
        mostrar(recorrido()[recorrido().indexOf(activo) + 1], true);
    });
    form.addEventListener('input', programarActualizacion);
    form.addEventListener('change', programarActualizacion);
    document.addEventListener('crear:filas-actualizadas', programarActualizacion);
    document.addEventListener('crear:entrega-actualizada', programarActualizacion);
    document.addEventListener('crear:imagenes-actualizadas', evento => {
        fotos = evento.detail.cantidad;
        portada = evento.detail.portada;
        programarActualizacion();
    });

    // Revelar el campo antes de enfocar un error evita errores de validación en paneles cerrados.
    form.noValidate = true;
    form.addEventListener('submit', evento => {
        actualizar();
        const campo = [...form.querySelectorAll('input, select, textarea')].find(elemento => elemento.willValidate && !elemento.validity.valid);
        if (campo) {
            evento.preventDefault();
            evento.stopImmediatePropagation();
            mostrar(campo.closest('[data-editor-panel]').dataset.editorPanel);
            campo.reportValidity();
            return;
        }
        if (!esDinero() && !puntoValido()) {
            evento.preventDefault();
            evento.stopImmediatePropagation();
            mostrar('entrega', true);
            const estado = document.getElementById('estado-busqueda');
            estado.textContent = 'Buscá y elegí un resultado o marcá el lugar en el mapa antes de publicar.';
            document.getElementById('buscar-direccion').focus();
            return;
        }
        if (errorFotos()) {
            evento.preventDefault();
            evento.stopImmediatePropagation();
            mostrar('fotos', true);
            document.getElementById('crear-imagenes').focus();
            return;
        }
        if (escritorio.matches && activo !== 'revision') {
            evento.preventDefault();
            evento.stopImmediatePropagation();
            mostrar('revision', true);
        }
    }, true);
    form.addEventListener('submit', evento => {
        queueMicrotask(() => {
            // Los validadores de fotos y ubicación siguen teniendo la última palabra.
            if (evento.defaultPrevented) return;
            publicar.disabled = true;
            publicar.textContent = 'Publicando…';
            form.setAttribute('aria-busy', 'true');
        });
    });
    window.addEventListener('pageshow', () => {
        publicar.disabled = false;
        publicar.textContent = 'Publicar campaña';
        form.removeAttribute('aria-busy');
    });
    detallesPrevia.addEventListener('toggle', () => {
        if (escritorio.matches && !detallesPrevia.open) detallesPrevia.open = true;
    });
    escritorio.addEventListener('change', () => {
        detallesPrevia.open = escritorio.matches;
        mostrar(activo);
    });
    menu.hidden = false;
    previa.hidden = false;
    acciones.hidden = false;
    editor.querySelector('[data-editor-revision]').hidden = false;
    editor.classList.add('crear-editor-listo');
    detallesPrevia.open = escritorio.matches;
    mostrar(activo);
    actualizar();
});
