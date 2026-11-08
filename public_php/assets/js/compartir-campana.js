'use strict';

document.querySelectorAll('[data-compartir-campana]').forEach((seccion) => {
    const enlace = seccion.querySelector('[data-compartir-enlace]');
    // Usar solo la ruta pública y el ID, sin otros parámetros ni fragmentos de la sesión.
    const url = new URL(enlace.dataset.ruta, window.location.href).href;
    const titulo = seccion.dataset.titulo;
    const texto = `Sumate a mi campaña solidaria: ${titulo}`;
    const estado = seccion.querySelector('[data-compartir-estado]');
    enlace.value = url;
    seccion.querySelector('[data-compartir-whatsapp]').href = `https://wa.me/?text=${encodeURIComponent(`${texto}\n${url}`)}`;
    seccion.querySelector('[data-compartir-telegram]').href = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(texto)}`;
    seccion.querySelector('[data-compartir-controles]').hidden = false;

    enlace.addEventListener('click', () => enlace.select());
    const copiarEnlace = async () => {
        let copiado = false;
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
                copiado = true;
            }
        } catch (_) {
            // Si el permiso se rechaza, intentar la copia compatible con HTTP local.
        }
        if (!copiado) {
            const opciones = seccion.querySelector('[data-compartir-mas]');
            const estabaAbierto = opciones.open;
            opciones.open = true;
            enlace.focus();
            enlace.select();
            enlace.setSelectionRange(0, enlace.value.length);
            try {
                copiado = document.execCommand('copy');
            } catch (_) {
                copiado = false;
            }
            if (copiado) opciones.open = estabaAbierto;
        }
        estado.textContent = copiado
            ? 'Enlace copiado'
            : 'Seleccionamos el enlace. Copialo con el menú de tu dispositivo o con Ctrl+C (⌘+C en Mac).';
        return copiado;
    };
    seccion.querySelector('[data-compartir-copiar]').addEventListener('click', () => copiarEnlace());
    const historia = seccion.querySelector('[data-compartir-historia]');
    historia.addEventListener('click', async () => {
        historia.disabled = true;
        estado.textContent = 'Preparando imagen…';
        try {
            const blob = await crearHistoriaCampana({ titulo, descripcion: seccion.dataset.descripcion, foto: seccion.dataset.foto, logo: seccion.dataset.logo, url });
            const descarga = document.createElement('a');
            const archivo = URL.createObjectURL(blob);
            descarga.href = archivo;
            descarga.download = `historia-campana-${new URL(url).searchParams.get('id')}.png`;
            document.body.append(descarga);
            descarga.click();
            descarga.remove();
            setTimeout(() => URL.revokeObjectURL(archivo), 60000);
            estado.textContent = 'Imagen lista para tu historia';
        } catch (_) {
            estado.textContent = 'No pudimos preparar la imagen. Intentá nuevamente.';
        } finally {
            historia.disabled = false;
        }
    });

    const compartir = seccion.querySelector('[data-compartir-nativo]');
    if (typeof navigator.share === 'function' && window.isSecureContext) {
        compartir.hidden = false;
        compartir.addEventListener('click', async () => {
            try {
                await navigator.share({ title: titulo, text: texto, url });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    estado.textContent = 'No pudimos abrir el menú de compartir. Podés copiar el enlace o usar WhatsApp o Telegram.';
                }
            }
        });
    }
});
