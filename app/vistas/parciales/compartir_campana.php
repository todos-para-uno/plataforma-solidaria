<?php
// Recibe el ID y el nombre de la campaña que se está viendo.
$rutaCampanaCompartir = $rutaBase . '/index.php?ruta=campana&id=' . (int) $idCampanaCompartir;
?>
<section class="compartir-campana" data-compartir-campana data-titulo="<?= escapar($nombreCampanaCompartir) ?>" data-descripcion="<?= escapar($descripcionCampanaCompartir) ?>" data-foto="<?= escapar(!empty($imagenesCampana) ? $rutaBase . '/' . $imagenesCampana[0]->getRuta() : '') ?>" data-logo="<?= escapar($rutaAssets) ?>/imagenes/logo-todosparauno.png" aria-labelledby="compartir-titulo">
    <h2 id="compartir-titulo">Compartí en tus redes sociales</h2>
    <div class="compartir-barra" data-compartir-controles hidden>
        <div class="compartir-acciones" aria-label="Redes sociales">
            <a class="compartir-icono compartir-whatsapp" data-compartir-whatsapp target="_blank" rel="noopener noreferrer" aria-label="Compartir por WhatsApp" title="Compartir por WhatsApp">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.5 0 .2 5.3.2 11.9c0 2.1.5 4.1 1.6 5.9L0 24l6.4-1.7a12 12 0 0 0 5.7 1.4C18.7 23.7 24 18.4 24 11.9c0-3.2-1.2-6.2-3.5-8.4ZM12.1 21.7c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.8 1 1-3.7-.2-.4a9.9 9.9 0 1 1 8.4 4.7Zm5.4-7.4c-.3-.1-1.8-.9-2.1-1-.3-.1-.5-.1-.7.2l-1 1.2c-.2.2-.4.2-.7.1-1.8-.9-3-2-3.8-3.6-.2-.3 0-.4.1-.6l.6-.7c.2-.2.2-.4.3-.6.1-.2 0-.4 0-.6l-1-2.3c-.2-.6-.5-.5-.7-.5H8c-.2 0-.6.1-.9.4-.3.3-1.1 1.1-1.1 2.6s1.1 3 1.3 3.2c.1.2 2.2 3.4 5.4 4.7 2 .9 2.8.9 3.8.7.6-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.2-.3-.3-.6-.4Z"/></svg>
            </a>
            <a class="compartir-icono compartir-telegram" data-compartir-telegram target="_blank" rel="noopener noreferrer" aria-label="Compartir por Telegram" title="Compartir por Telegram">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21.8 2.4-3.4 19c-.3 1.3-1 1.6-2 .9l-5.2-3.8-2.5 2.4c-.3.3-.5.5-1 .5l.4-5.3L17.8 7c.4-.4-.1-.6-.6-.3L5.2 14.3.1 12.7c-1.1-.3-1.1-1.1.2-1.6L20.3 2c.9-.4 1.7.2 1.5.4Z"/></svg>
            </a>
        </div>
        <button class="boton boton-secundario boton-chico compartir-historia" type="button" data-compartir-historia>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle class="instagram-punto" cx="17.5" cy="6.5" r="1"/></svg>
            Descargar imagen para historia
        </button>
        <details class="compartir-mas" id="compartir-mas" data-compartir-mas>
            <summary class="boton boton-secundario boton-chico">＋ Más opciones</summary>
            <div class="compartir-panel">
                <label for="compartir-enlace">Enlace de tu campaña</label>
                <div class="compartir-enlace">
                    <input id="compartir-enlace" type="url" readonly data-compartir-enlace data-ruta="<?= escapar($rutaCampanaCompartir) ?>">
                    <button class="boton boton-chico" type="button" data-compartir-copiar>Copiar enlace</button>
                </div>
                <button class="boton boton-secundario boton-chico" type="button" data-compartir-nativo hidden>Compartir con otras apps</button>
            </div>
        </details>
    </div>
    <p class="compartir-estado" role="status" aria-live="polite" data-compartir-estado></p>
    <noscript><p>Abrí <a href="<?= escapar($rutaCampanaCompartir) ?>">tu campaña</a> y copiá la dirección del navegador para compartirla.</p></noscript>
</section>
