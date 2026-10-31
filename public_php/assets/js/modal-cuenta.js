// =========================================================
// Modal "Mi cuenta" — todospara.uno
// Ubicación sugerida: public_php/assets/js/modal-cuenta.js
// Se carga al final del <body> o con defer.
// =========================================================

(function () {
  var modal = document.getElementById('modal-cuenta');
  if (!modal || typeof modal.showModal !== 'function') return;
  var devolverFoco = null;

  // ---------- Abrir y cerrar ----------
  // Cualquier botón con data-abrir-cuenta abre el modal.
  document.querySelectorAll('[data-abrir-cuenta]').forEach(function (boton) {
    boton.addEventListener('click', function (evento) {
      evento.preventDefault();
      var menuCuenta = boton.closest('.menu-usuario');
      devolverFoco = menuCuenta ? menuCuenta.querySelector('summary') : boton;
      if (menuCuenta) menuCuenta.open = false;
      modal.showModal();                         // <dialog> nativo: pone el ::backdrop y atrapa el foco
      document.body.classList.add('modal-abierto');
    });
  });

  modal.querySelectorAll('[data-cerrar-cuenta]').forEach(function (boton) {
    boton.addEventListener('click', function () { modal.close(); });
  });

  // Clic en el fondo oscuro (fuera de la caja) también cierra
  modal.addEventListener('click', function (evento) {
    if (evento.target === modal) modal.close();
  });

  // Se dispara al cerrar por botón, por clic afuera o con la tecla Esc
  modal.addEventListener('close', function () {
    document.body.classList.remove('modal-abierto');
    mostrarLecturaPerfil();
    if (devolverFoco) devolverFoco.focus();
  });

  // ---------- Pestañas Perfil / Seguridad ----------
  var pestanas = modal.querySelectorAll('[role="tab"]');

  function activarPestana(pestana) {
    pestanas.forEach(function (p) {
      var activa = (p === pestana);
      p.setAttribute('aria-selected', activa ? 'true' : 'false');
      p.tabIndex = activa ? 0 : -1;
      document.getElementById(p.getAttribute('aria-controls')).hidden = !activa;
    });
  }

  pestanas.forEach(function (pestana, i) {
    pestana.addEventListener('click', function () { activarPestana(pestana); });

    // Flechas arriba/abajo para moverse entre pestañas con teclado
    pestana.addEventListener('keydown', function (evento) {
      var siguiente = null;
      if (evento.key === 'ArrowDown' || evento.key === 'ArrowRight') siguiente = pestanas[(i + 1) % pestanas.length];
      if (evento.key === 'ArrowUp' || evento.key === 'ArrowLeft') siguiente = pestanas[(i - 1 + pestanas.length) % pestanas.length];
      if (siguiente) {
        evento.preventDefault();
        activarPestana(siguiente);
        siguiente.focus();
      }
    });
  });

  // ---------- Editar perfil ----------
  var lectura = modal.querySelector('[data-perfil-lectura]');
  var formulario = modal.querySelector('[data-perfil-formulario]');
  // Esta vista permite previsualizar. No enviar a una ruta hasta integrar el guardado.
  formulario.addEventListener('submit', function (evento) { evento.preventDefault(); });
  var vistaInicial = formulario.querySelector('[data-avatar-vista]').cloneNode(true);
  var urlAvatar = null;

  function liberarAvatar() {
    if (urlAvatar) URL.revokeObjectURL(urlAvatar);
    urlAvatar = null;
  }

  function mostrarLecturaPerfil() {
    if (!formulario) return;
    formulario.reset();
    liberarAvatar();
    formulario.querySelector('[data-avatar-vista]').replaceWith(vistaInicial.cloneNode(true));
    formulario.hidden = true;
    lectura.hidden = false;
  }

  modal.querySelector('[data-perfil-editar]').addEventListener('click', function () {
    lectura.hidden = true;
    formulario.hidden = false;
    formulario.querySelector('input[name="nombre"]').focus();
  });

  modal.querySelector('[data-perfil-cancelar]').addEventListener('click', mostrarLecturaPerfil);

  // Vista previa de la imagen elegida + control de tamaño (10 MB)
  var inputAvatar = modal.querySelector('[data-avatar-input]');
  inputAvatar.addEventListener('change', function () {
    var archivo = inputAvatar.files[0];
    if (!archivo) return;

    if (archivo.size > 10 * 1024 * 1024) {
      alert('La imagen supera los 10 MB. Elegí una más liviana.');
      inputAvatar.value = '';
      return;
    }

    if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(archivo.type)) {
      alert('Elegí una imagen JPG, PNG, GIF o WEBP.');
      inputAvatar.value = '';
      return;
    }

    var vista = formulario.querySelector('[data-avatar-vista]');
    var img = document.createElement('img');
    img.className = 'avatar';
    img.alt = '';
    img.setAttribute('data-avatar-vista', '');
    liberarAvatar();
    urlAvatar = URL.createObjectURL(archivo);
    img.src = urlAvatar;
    vista.replaceWith(img);
  });
})();
