// todospara.uno — comportamiento compartido (lo carga la cabecera).
// Sin JavaScript el menú se muestra siempre abierto, así que nada queda inaccesible.
document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', function () {
    const boton = document.querySelector('.menu-boton');
    const menu = document.getElementById('menu');
    if (!boton || !menu) {
        return;
    }

    boton.hidden = false;
    boton.addEventListener('click', function () {
        const abierto = boton.getAttribute('aria-expanded') === 'true';
        boton.setAttribute('aria-expanded', String(!abierto));
        menu.classList.toggle('abierto', !abierto);
    });

    // Cierra el desplegable del usuario al hacer clic afuera.
    document.addEventListener('click', function (evento) {
        document.querySelectorAll('.menu-usuario[open]').forEach(function (desplegable) {
            if (!desplegable.contains(evento.target)) {
                desplegable.open = false;
            }
        });
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && menu.classList.contains('abierto')) {
            boton.setAttribute('aria-expanded', 'false');
            menu.classList.remove('abierto');
            boton.focus();
        }
    });
});
