// Comportamiento del menú compartido. cabecera.php carga este archivo en las vistas.
// El botón se habilita al encontrar el menú. Sin JavaScript, los enlaces quedan visibles.
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

    // Un clic fuera del menú de usuario cierra su desplegable <details>.
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
