// Comportamiento del menú compartido y modales. cabecera.php carga este archivo en las vistas.
// El botón se habilita al encontrar el menú. Sin JavaScript, los enlaces quedan visibles.
document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', function () {
    const bienvenida = document.querySelector('[data-bienvenida]');
    const cerrarBienvenida = bienvenida?.querySelector('[data-cerrar-bienvenida]');
    if (cerrarBienvenida) {
        cerrarBienvenida.hidden = false;
        cerrarBienvenida.addEventListener('click', function () {
            bienvenida.closest('.bienvenida').remove();
            document.getElementById('contenido').focus({ preventScroll: true });
        });
    }

    // 1. Gestión del menú desplegable del celular
    const boton = document.querySelector('.menu-boton');
    const menu = document.getElementById('menu');
    if (boton && menu) {
        boton.hidden = false;
        boton.addEventListener('click', function () {
            const abierto = boton.getAttribute('aria-expanded') === 'true';
            boton.setAttribute('aria-expanded', String(!abierto));
            menu.classList.toggle('abierto', !abierto);
        });
    }

    // Un clic fuera del menú de usuario cierra su desplegable <details>.
    document.addEventListener('click', function (evento) {
        document.querySelectorAll('.menu-usuario[open]').forEach(function (desplegable) {
            if (!desplegable.contains(evento.target)) {
                desplegable.open = false;
            }
        });
    });

    document.addEventListener('keydown', function (evento) {
        // El diálogo administra Escape y devuelve el foco a quien lo abrió.
        if (document.querySelector('dialog[open]')) return;
        if (evento.key === 'Escape' && menu && menu.classList.contains('abierto')) {
            boton.setAttribute('aria-expanded', 'false');
            menu.classList.remove('abierto');
            boton.focus();
        }
    });

    // 2. Gestión del modal flotante de donación (para la página de detalle de campaña)
    const botonAbrirDonar = document.querySelector('.btn-abrir-modal-donar');
    const modalDonar = document.querySelector('#modal-donar');
    const botonCerrarDonar = document.querySelector('#cerrar-modal');

    if (botonAbrirDonar && modalDonar) {
        botonAbrirDonar.addEventListener('click', function (evento) {
            evento.preventDefault();
            modalDonar.removeAttribute('hidden');
        });
    }

    if (botonCerrarDonar && modalDonar) {
        botonCerrarDonar.addEventListener('click', function () {
            modalDonar.setAttribute('hidden', 'true');
        });
    }

    // Cerrar el modal haciendo clic fuera de la tarjeta (en la capa oscura)
    if (modalDonar) {
        modalDonar.addEventListener('click', function (evento) {
            if (evento.target === modalDonar) {
                modalDonar.setAttribute('hidden', 'true');
            }
        });
    }
});
