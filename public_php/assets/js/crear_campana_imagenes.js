/*
 * Carga de imágenes en "Crear campaña".
 * - Valida formato (PNG, JPG, WEBP), tamaño y cantidad antes de enviar.
 * - Permite arrastrar o elegir archivos, quitar fotos y elegir la portada.
 * - Mantiene sincronizado el <input type="file">: el formulario se envía de forma normal.
 * El servidor vuelve a validar todo: esta revisión solo evita esperas y errores evitables.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const contenedor = document.querySelector('[data-carga-imagenes]');
    if (!contenedor) return;

    const input = contenedor.querySelector('input[type="file"]');
    const zona = contenedor.querySelector('[data-zona-carga]');
    const tituloZona = contenedor.querySelector('[data-zona-titulo]');
    const previa = contenedor.querySelector('[data-previa]');
    const contador = contenedor.querySelector('[data-contador]');
    const puntos = contenedor.querySelectorAll('.imagenes-puntos span');
    const cajaError = contenedor.querySelector('[data-error-imagenes]');
    const formulario = input.form;

    const MAXIMO = parseInt(contenedor.dataset.maxImagenes, 10) || 4;
    const MAX_BYTES = parseInt(contenedor.dataset.maxBytes, 10) || 5 * 1024 * 1024;
    const MAX_TOTAL = parseInt(contenedor.dataset.maxTotal, 10) || 0;
    const TIPOS = { 'image/png': ['png'], 'image/jpeg': ['jpg', 'jpeg'], 'image/webp': ['webp'] };
    const TITULO_INICIAL = tituloZona.textContent;
    const reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Sin DataTransfer no se puede modificar la lista del input: queda el comportamiento nativo.
    let puedeEditarLista = true;
    try { new DataTransfer(); } catch (e) { puedeEditarLista = false; }

    /** @type {{archivo: File, url: string, item: HTMLLIElement}[]} */
    let seleccion = [];

    function formatearTamano(bytes) {
        const mb = bytes / 1048576;
        return (mb < 0.1 ? '0,1' : mb.toFixed(1).replace('.', ',').replace(',0', '')) + ' MB';
    }

    function extension(nombre) {
        const punto = nombre.lastIndexOf('.');
        return punto === -1 ? '' : nombre.slice(punto + 1).toLowerCase();
    }

    function validarArchivo(archivo) {
        const extensiones = TIPOS[archivo.type];
        if (!extensiones || !extensiones.includes(extension(archivo.name))) {
            return '“' + archivo.name + '” no es PNG, JPG o WEBP.';
        }
        if (archivo.size === 0) return '“' + archivo.name + '” está vacío.';
        if (archivo.size > MAX_BYTES) {
            return '“' + archivo.name + '” pesa ' + formatearTamano(archivo.size)
                + '; el máximo es ' + formatearTamano(MAX_BYTES) + '.';
        }
        return null;
    }

    function mostrarError(mensajes, sumar) {
        if (!mensajes.length) return limpiarError();
        // "sumar" conserva los errores visibles (por ejemplo, el de una foto que falla al decodificarse después).
        const previos = sumar && !cajaError.hidden && cajaError.textContent ? cajaError.textContent + ' ' : '';
        cajaError.textContent = previos + mensajes.join(' ');
        // Reinicia la animación aunque ya hubiera un error visible.
        cajaError.hidden = true;
        void cajaError.offsetWidth;
        cajaError.hidden = false;
        contenedor.classList.add('con-error');
        input.setAttribute('aria-invalid', 'true');
    }

    function limpiarError() {
        cajaError.hidden = true;
        cajaError.textContent = '';
        contenedor.classList.remove('con-error');
        input.removeAttribute('aria-invalid');
    }

    function sincronizarInput() {
        if (!puedeEditarLista) return;
        const lista = new DataTransfer();
        seleccion.forEach(function (s) { lista.items.add(s.archivo); });
        input.files = lista.files;
    }

    function actualizarEstado() {
        const cantidad = seleccion.length;
        contador.textContent = cantidad + ' de ' + MAXIMO + (MAXIMO === 1 ? ' imagen' : ' imágenes');
        puntos.forEach(function (punto, i) { punto.classList.toggle('lleno', i < cantidad); });

        const completa = cantidad >= MAXIMO;
        zona.classList.toggle('completa', completa);
        zona.setAttribute('aria-disabled', completa ? 'true' : 'false');
        tituloZona.textContent = completa
            ? 'Llegaste al máximo de ' + MAXIMO + ' imágenes. Quitá una para cambiarla.'
            : (cantidad > 0 ? 'Sumá más fotos' : TITULO_INICIAL);

        seleccion.forEach(function (s, i) {
            const esPortada = i === 0;
            s.item.querySelector('.imagen-portada').hidden = !esPortada;
            s.item.querySelector('.imagen-hacer-portada').hidden = esPortada;
        });
    }

    function crearItem(archivo, url, retraso) {
        const item = document.createElement('li');
        item.className = 'imagen-previa cargando';
        item.style.setProperty('--retraso', retraso + 'ms');

        const figura = document.createElement('figure');
        const imagen = document.createElement('img');
        imagen.alt = 'Vista previa de ' + archivo.name;
        imagen.decoding = 'async';
        imagen.addEventListener('load', function () { item.classList.remove('cargando'); }, { once: true });
        imagen.addEventListener('error', function () {
            // El tipo parecía correcto pero el navegador no puede leerla: probablemente esté dañada.
            quitar(archivo);
            mostrarError(['No pudimos leer “' + archivo.name + '”. Puede estar dañada.'], true);
        }, { once: true });
        imagen.src = url;

        const leyenda = document.createElement('figcaption');
        leyenda.textContent = archivo.name + ' · ' + formatearTamano(archivo.size);
        leyenda.title = archivo.name;

        const portada = document.createElement('span');
        portada.className = 'insignia imagen-portada';
        portada.textContent = 'Portada';
        portada.hidden = true;

        const botonQuitar = document.createElement('button');
        botonQuitar.type = 'button';
        botonQuitar.className = 'imagen-quitar';
        botonQuitar.setAttribute('aria-label', 'Quitar ' + archivo.name);
        botonQuitar.innerHTML = '<span aria-hidden="true">&times;</span>';
        botonQuitar.addEventListener('click', function () {
            const indice = seleccion.findIndex(function (s) { return s.archivo === archivo; });
            quitar(archivo);
            limpiarError();
            // El foco pasa a la foto que ocupa su lugar (o al selector) para no perderse.
            const siguiente = seleccion[Math.min(indice, seleccion.length - 1)];
            (siguiente ? siguiente.item.querySelector('.imagen-quitar') : input).focus();
        });

        const botonPortada = document.createElement('button');
        botonPortada.type = 'button';
        botonPortada.className = 'imagen-hacer-portada';
        botonPortada.textContent = 'Usar de portada';
        botonPortada.setAttribute('aria-label', 'Usar ' + archivo.name + ' como portada');
        botonPortada.addEventListener('click', function () { hacerPortada(archivo); });

        if (!puedeEditarLista) {
            botonQuitar.hidden = true;
            botonPortada.hidden = true;
        }

        figura.append(imagen, leyenda);
        item.append(portada, figura, botonQuitar, botonPortada);
        return item;
    }

    function agregar(archivos) {
        const errores = [];
        const disponibles = MAXIMO - seleccion.length;
        let totalActual = seleccion.reduce(function (suma, s) { return suma + s.archivo.size; }, 0);
        let agregadas = 0;
        const sobrantes = [];

        Array.from(archivos).forEach(function (archivo) {
            const repetida = seleccion.some(function (s) {
                return s.archivo.name === archivo.name && s.archivo.size === archivo.size
                    && s.archivo.lastModified === archivo.lastModified;
            });
            if (repetida) return;

            const error = validarArchivo(archivo);
            if (error) { errores.push(error); return; }

            if (agregadas >= disponibles) { sobrantes.push(archivo.name); return; }

            if (MAX_TOTAL && totalActual + archivo.size > MAX_TOTAL) {
                errores.push('“' + archivo.name + '” no entra: entre todas las fotos no pueden superar '
                    + formatearTamano(MAX_TOTAL) + '.');
                return;
            }

            const url = URL.createObjectURL(archivo);
            const item = crearItem(archivo, url, agregadas * 80);
            seleccion.push({ archivo: archivo, url: url, item: item });
            previa.appendChild(item);
            totalActual += archivo.size;
            agregadas++;
        });

        if (sobrantes.length) {
            errores.push('Podés subir hasta ' + MAXIMO + ' imágenes. Quedaron afuera: ' + sobrantes.join(', ') + '.');
        }

        sincronizarInput();
        actualizarEstado();
        if (errores.length) mostrarError(errores); else limpiarError();
    }

    function quitar(archivo) {
        const indice = seleccion.findIndex(function (s) { return s.archivo === archivo; });
        if (indice === -1) return;
        const quitada = seleccion.splice(indice, 1)[0];
        sincronizarInput();
        actualizarEstado();

        const terminar = function () {
            URL.revokeObjectURL(quitada.url);
            quitada.item.remove();
        };
        if (reducirMovimiento.matches) return terminar();
        quitada.item.classList.add('saliendo');
        quitada.item.addEventListener('animationend', terminar, { once: true });
        setTimeout(terminar, 400); // por si la animación no llega a correr
    }

    function hacerPortada(archivo) {
        const indice = seleccion.findIndex(function (s) { return s.archivo === archivo; });
        if (indice <= 0) return;
        const elegida = seleccion.splice(indice, 1)[0];
        seleccion.unshift(elegida);
        previa.prepend(elegida.item);
        sincronizarInput();
        actualizarEstado();

        elegida.item.classList.remove('movida');
        void elegida.item.offsetWidth;
        elegida.item.classList.add('movida');
        elegida.item.querySelector('.imagen-quitar').focus();
    }

    // Con el máximo alcanzado no se abre el selector (ni con clic ni con teclado).
    function bloquearSiCompleta(evento) {
        if (puedeEditarLista && seleccion.length >= MAXIMO) {
            evento.preventDefault();
            mostrarError(['Ya elegiste ' + MAXIMO + ' imágenes. Quitá alguna para sumar otra.']);
        }
    }
    zona.addEventListener('click', bloquearSiCompleta);
    input.addEventListener('click', bloquearSiCompleta);

    // Elegir desde el explorador de archivos
    input.addEventListener('change', function () {
        if (!puedeEditarLista) {
            // Navegadores viejos: se revisa la selección tal cual, sin poder editarla.
            const archivos = Array.from(input.files);
            const errores = archivos.map(validarArchivo).filter(Boolean);
            if (archivos.length > MAXIMO) errores.push('Podés subir hasta ' + MAXIMO + ' imágenes.');
            seleccion.forEach(function (s) { URL.revokeObjectURL(s.url); s.item.remove(); });
            seleccion = [];
            if (!errores.length) {
                archivos.forEach(function (archivo, i) {
                    const url = URL.createObjectURL(archivo);
                    const item = crearItem(archivo, url, i * 80);
                    seleccion.push({ archivo: archivo, url: url, item: item });
                    previa.appendChild(item);
                });
            }
            actualizarEstado();
            if (errores.length) mostrarError(errores); else limpiarError();
            return;
        }
        // Se suman a las ya elegidas; después el input se vuelve a armar con la lista completa.
        const nuevas = Array.from(input.files).filter(function (archivo) {
            return !seleccion.some(function (s) { return s.archivo === archivo; });
        });
        agregar(nuevas);
    });

    // Arrastrar y soltar
    let profundidadArrastre = 0;
    function llevaArchivos(evento) {
        return evento.dataTransfer && Array.from(evento.dataTransfer.types || []).includes('Files');
    }

    zona.addEventListener('dragenter', function (evento) {
        if (!llevaArchivos(evento)) return;
        evento.preventDefault();
        zona.classList.add('arrastrando');
    });
    zona.addEventListener('dragover', function (evento) {
        if (!llevaArchivos(evento)) return;
        evento.preventDefault();
        evento.dataTransfer.dropEffect = zona.classList.contains('completa') ? 'none' : 'copy';
    });
    zona.addEventListener('dragleave', function (evento) {
        if (!zona.contains(evento.relatedTarget)) zona.classList.remove('arrastrando');
    });
    zona.addEventListener('drop', function (evento) {
        if (!llevaArchivos(evento)) return;
        evento.preventDefault();
        zona.classList.remove('arrastrando', 'esperando');
        profundidadArrastre = 0;
        if (!puedeEditarLista) {
            mostrarError(['Tu navegador no permite soltar archivos acá. Hacé clic para elegirlos.']);
            return;
        }
        if (zona.classList.contains('completa')) {
            mostrarError(['Ya elegiste ' + MAXIMO + ' imágenes. Quitá alguna para sumar otra.']);
            return;
        }
        agregar(evento.dataTransfer.files);
    });

    // Fuera de la zona: se resalta la zona y se evita que el navegador abra la foto y se pierda el formulario.
    window.addEventListener('dragenter', function (evento) {
        if (!llevaArchivos(evento)) return;
        profundidadArrastre++;
        zona.classList.add('esperando');
    });
    window.addEventListener('dragleave', function (evento) {
        if (!llevaArchivos(evento)) return;
        profundidadArrastre = Math.max(0, profundidadArrastre - 1);
        if (profundidadArrastre === 0) zona.classList.remove('esperando');
    });
    window.addEventListener('dragover', function (evento) {
        if (llevaArchivos(evento) && !zona.contains(evento.target)) {
            evento.preventDefault();
            evento.dataTransfer.dropEffect = 'none';
        }
    });
    window.addEventListener('drop', function (evento) {
        profundidadArrastre = 0;
        zona.classList.remove('esperando', 'arrastrando');
        if (llevaArchivos(evento) && !zona.contains(evento.target)) evento.preventDefault();
    });

    // Última revisión antes de enviar
    if (formulario) {
        formulario.addEventListener('submit', function (evento) {
            const archivos = Array.from(input.files);
            const errores = archivos.map(validarArchivo).filter(Boolean);
            if (archivos.length > MAXIMO) errores.push('Podés subir hasta ' + MAXIMO + ' imágenes.');
            const total = archivos.reduce(function (suma, a) { return suma + a.size; }, 0);
            if (MAX_TOTAL && total > MAX_TOTAL) {
                errores.push('Entre todas las fotos no pueden superar ' + formatearTamano(MAX_TOTAL) + '.');
            }
            if (errores.length) {
                evento.preventDefault();
                actualizarEstado();
                mostrarError(errores);
                contenedor.scrollIntoView({ behavior: reducirMovimiento.matches ? 'auto' : 'smooth', block: 'center' });
            }
        });
    }

    actualizarEstado();
});
