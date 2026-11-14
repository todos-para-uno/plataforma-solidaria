'use strict';

// Composición local: usa únicamente los datos públicos y las fotos de la campaña.
async function crearHistoriaCampana({ titulo, descripcion = '', foto, logo }) {
    const cargar = (ruta) => new Promise((resolve) => {
        if (!ruta) return resolve(null);
        const imagen = new Image();
        const limite = setTimeout(() => resolve(null), 8000);
        imagen.onload = () => { clearTimeout(limite); resolve(imagen); };
        imagen.onerror = () => { clearTimeout(limite); resolve(null); };
        const origen = new URL(ruta, window.location.href);
        if (origen.origin !== window.location.origin) { clearTimeout(limite); return resolve(null); }
        imagen.src = origen.href;
    });
    const [imagen, marca] = await Promise.all([cargar(foto), cargar(logo)]);
    const canvas = document.createElement('canvas');
    canvas.width = 1080;
    canvas.height = 1920;
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Canvas no disponible');
    const azul = '#153a6b', papel = '#f7f5f0', amarillo = '#f6c85f';
    ctx.fillStyle = papel;
    ctx.fillRect(0, 0, 1080, 1920);
    ctx.fillStyle = amarillo;
    ctx.beginPath(); ctx.arc(1020, 220, 290, 0, Math.PI * 2); ctx.fill();
    if (marca) {
        const ancho = 440;
        ctx.drawImage(marca, 80, 160, ancho, ancho * marca.naturalHeight / marca.naturalWidth);
    } else {
        ctx.fillStyle = azul; ctx.font = 'bold 54px sans-serif';
        ctx.fillText('todospara.uno', 80, 210);
    }
    ctx.fillStyle = azul;
    ctx.font = 'bold 25px sans-serif';
    ctx.fillText('UNA CAMPAÑA · MUCHAS MANOS', 80, 300);

    ctx.save();
    ctx.beginPath(); ctx.rect(80, 350, 920, 680); ctx.clip();
    if (imagen) {
        const escala = Math.max(920 / imagen.naturalWidth, 680 / imagen.naturalHeight);
        const ancho = imagen.naturalWidth * escala, alto = imagen.naturalHeight * escala;
        ctx.drawImage(imagen, 80 + (920 - ancho) / 2, 350 + (680 - alto) / 2, ancho, alto);
    } else {
        ctx.fillStyle = '#e2eadf'; ctx.fillRect(80, 350, 920, 680);
        ctx.fillStyle = amarillo;
        ctx.beginPath(); ctx.arc(540, 640, 160, 0, Math.PI * 2); ctx.fill();
        ctx.fillStyle = azul; ctx.textAlign = 'center'; ctx.font = 'bold 48px sans-serif';
        ctx.fillText('Juntos podemos más', 540, 910);
        ctx.textAlign = 'left';
    }
    ctx.restore();
    ctx.strokeStyle = azul; ctx.lineWidth = 3; ctx.strokeRect(80, 350, 920, 680);

    // Partir también palabras largas para mantener cualquier título dentro del margen.
    const lineas = (texto, ancho) => {
        const resultado = []; let linea = '';
        for (const caracter of Array.from(texto.replace(/\s+/gu, ' ').trim())) {
            if (ctx.measureText(linea + caracter).width > ancho && linea) {
                const corte = linea.lastIndexOf(' ');
                if (corte > linea.length / 2 && caracter !== ' ') {
                    resultado.push(linea.slice(0, corte)); linea = linea.slice(corte + 1);
                } else { resultado.push(linea.trim()); linea = ''; }
            }
            linea += caracter;
        }
        if (linea.trim()) resultado.push(linea.trim());
        return resultado;
    };
    ctx.fillStyle = azul;
    const tieneDescripcion = descripcion.trim() !== '';
    const maxTitulo = tieneDescripcion ? 3 : 4;
    let tamaño = tieneDescripcion ? 64 : 72;
    ctx.font = `bold ${tamaño}px sans-serif`;
    while (lineas(titulo, 920).length > maxTitulo && tamaño > 48) {
        tamaño -= 4; ctx.font = `bold ${tamaño}px sans-serif`;
    }
    const renglones = lineas(titulo, 920);
    if (renglones.length > maxTitulo) {
        let ultimo = renglones[maxTitulo - 1];
        while (ctx.measureText(ultimo + '…').width > 920) ultimo = Array.from(ultimo).slice(0, -1).join('');
        renglones[maxTitulo - 1] = ultimo.trimEnd() + '…';
    }
    const tituloVisible = renglones.slice(0, maxTitulo);
    tituloVisible.forEach((linea, i) => ctx.fillText(linea, 80, 1150 + i * (tamaño + 14)));
    if (tieneDescripcion) {
        const inicio = 1150 + (tituloVisible.length - 1) * (tamaño + 14) + 64;
        const maxDescripcion = Math.floor((1460 - inicio) / 44) + 1;
        ctx.fillStyle = '#4d4b46'; ctx.font = '34px sans-serif';
        const detalle = lineas(descripcion, 920);
        if (detalle.length > maxDescripcion) {
            let ultimo = detalle[maxDescripcion - 1];
            while (ctx.measureText(ultimo + '…').width > 920) ultimo = Array.from(ultimo).slice(0, -1).join('');
            detalle[maxDescripcion - 1] = ultimo.trimEnd() + '…';
        }
        detalle.slice(0, maxDescripcion).forEach((linea, i) => ctx.fillText(linea, 80, inicio + i * 44));
    }
    ctx.fillStyle = '#b94f39'; ctx.fillRect(80, 1510, 920, 108);
    ctx.fillStyle = '#ffffff'; ctx.font = 'bold 42px sans-serif'; ctx.textAlign = 'center';
    ctx.fillText('SUMATE A ESTA CAMPAÑA', 540, 1579);
    ctx.textAlign = 'left'; ctx.fillStyle = azul; ctx.font = '28px sans-serif';
    ctx.fillText('Tu ayuda hace la diferencia.', 80, 1690);
    return new Promise((resolve, reject) => canvas.toBlob(
        blob => blob ? resolve(blob) : reject(new Error('No se pudo crear la imagen')), 'image/png'
    ));
}
