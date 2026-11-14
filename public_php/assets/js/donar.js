document.querySelector('[data-mp-checkout-cta="checkout-pro"]')?.form.addEventListener('submit', (event) => {
    if (!event.target.checkValidity()) return;
    const boton = event.target.querySelector('[data-mp-checkout-cta]');
    boton.disabled = true;
    boton.setAttribute('aria-busy', 'true');
    boton.textContent = 'Preparando tu donación…';
});
window.addEventListener('pageshow', () => {
    const boton = document.querySelector('[data-mp-checkout-cta="checkout-pro"]');
    if (!boton) return;
    boton.disabled = false;
    boton.removeAttribute('aria-busy');
    boton.textContent = 'Donar con Mercado Pago';
});
