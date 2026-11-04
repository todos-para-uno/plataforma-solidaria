// =========================================================
// Modal "Mi cuenta" — todospara.uno
// Ubicación sugerida: public_php/assets/js/modal-cuenta.js
// Se carga al final del <body> o con defer.
// =========================================================

(function () {
  // La previsualización mejora ambos formularios; el guardado no depende de JavaScript.
  document.querySelectorAll('[data-perfil-formulario]').forEach(function (formularioPerfil) {
    var input = formularioPerfil.querySelector('[data-avatar-input]');
    var estado = formularioPerfil.querySelector('[data-avatar-estado]');
    var inicial = formularioPerfil.querySelector('[data-avatar-vista]').cloneNode(true);
    var quitar = formularioPerfil.querySelector('[name="eliminar_avatar"]');
    var url = null;
    function restaurar() {
      if (url) URL.revokeObjectURL(url);
      url = null;
      formularioPerfil.querySelector('[data-avatar-vista]').replaceWith(inicial.cloneNode(true));
    }
    function rechazar(mensaje) {
      restaurar();
      input.value = '';
      estado.textContent = mensaje;
    }
    formularioPerfil.addEventListener('reset', function () { restaurar(); estado.textContent = ''; });
    window.addEventListener('pagehide', function () { if (url) URL.revokeObjectURL(url); });
    if (quitar) quitar.addEventListener('change', function () {
      if (quitar.checked) { input.value = ''; restaurar(); estado.textContent = 'La foto se quitará al guardar.'; }
      else estado.textContent = '';
    });
    input.addEventListener('change', function () {
      var archivo = input.files[0];
      if (!archivo) { restaurar(); estado.textContent = ''; return; }
      if (archivo.size > Number(input.dataset.maximoBytes)) {
        rechazar('La imagen supera el tamaño máximo indicado. Elegí una más liviana.'); return;
      }
      if (!/\.(jpe?g|webp)$/i.test(archivo.name) || (archivo.type && !['image/jpeg', 'image/webp'].includes(archivo.type))) {
        rechazar('Elegí una imagen JPG, JPEG o WEBP.'); return;
      }
      restaurar();
      if (quitar) quitar.checked = false;
      var img = document.createElement('img');
      img.className = 'avatar'; img.alt = ''; img.width = 72; img.height = 72;
      img.setAttribute('data-avatar-vista', '');
      img.onerror = function () { rechazar('No pudimos previsualizar la imagen. Elegí otra foto.'); };
      url = URL.createObjectURL(archivo); img.src = url;
      formularioPerfil.querySelector('[data-avatar-vista]').replaceWith(img);
      estado.textContent = 'Foto seleccionada. Se guardará recortada desde el centro a 250 × 250 píxeles.';
    });
  });

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

  // ---------- Pestañas de navegación (hoy solo Perfil) ----------
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
  function mostrarLecturaPerfil() {
    if (!formulario) return;
    formulario.reset();
    formulario.hidden = true;
    lectura.hidden = false;
  }

  modal.querySelector('[data-perfil-editar]').addEventListener('click', function () {
    lectura.hidden = true;
    formulario.hidden = false;
    formulario.querySelector('input[name="nombre"]').focus();
  });

  modal.querySelector('[data-perfil-cancelar]').addEventListener('click', mostrarLecturaPerfil);

})();
