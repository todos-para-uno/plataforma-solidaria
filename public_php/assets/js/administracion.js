(() => {
  'use strict';
  const root = document.getElementById('gestion-usuarios');
  if (!root) return;
  const byId = id => document.getElementById(id);
  const list = byId('admin-lista'), status = byId('admin-estado');
  const search = byId('admin-busqueda'), limit = byId('admin-limite');
  const previous = byId('admin-anterior'), next = byId('admin-siguiente');
  const dialog = byId('admin-dialogo'), form = byId('admin-formulario');
  let page = 1, expanded = null, rows = [], controller = null, debounce = null, action = '';
  const api = root.dataset.api;
  const csrf = root.dataset.csrf;
  // Pestañas internas del administrador, independientes de la navegación principal.
  const tabs = Array.from(root.querySelectorAll('[role="tab"]'));
  function activateTab(tab) {
    tabs.forEach(candidate => {
      const selected = candidate === tab;
      candidate.setAttribute('aria-selected', String(selected));
      candidate.tabIndex = selected ? 0 : -1;
      const panel = byId(candidate.getAttribute('aria-controls'));
      if (panel) panel.hidden = !selected;
    });
  }
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateTab(tab));
    tab.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      const nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1
        : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
      activateTab(tabs[nextIndex]);
      tabs[nextIndex].focus();
    });
  });
  if (tabs.length) activateTab(tabs.find(tab => tab.getAttribute('aria-selected') === 'true') || tabs[0]);
  const create = (tag, value, className) => {
    const element = document.createElement(tag);
    if (value !== undefined) element.textContent = String(value ?? '—');
    if (className) element.className = className;
    return element;
  };
  async function fetchRows() {
    if (controller) controller.abort();
    controller = new AbortController();
    status.textContent = 'Cargando usuarios…';
    list.replaceChildren();
    const params = new URLSearchParams({ruta:'admin_usuarios',q:search.value,limite:limit.value,pagina:String(page)});
    try {
      const response = await fetch(`${api}?${params}`, {credentials:'same-origin', signal:controller.signal,headers:{Accept:'application/json'}});
      const data = await response.json();
      if (!response.ok) throw Error(data.error || 'No se pudo cargar el listado.');
      rows = Array.isArray(data.usuarios) ? data.usuarios : [];
      expanded = null;
      render();
      status.textContent = rows.length ? `${rows.length} usuario(s) en esta página.` : 'No se encontraron usuarios.';
      // El endpoint actual no devuelve un total: una página llena podría tener una siguiente.
      next.disabled = rows.length < Number(limit.value);
      previous.disabled = page === 1;
      byId('admin-pagina').textContent = `Página ${page}`;
    } catch (error) {
      if (error.name === 'AbortError') return;
      status.textContent = error.message;
      previous.disabled = next.disabled = true;
    }
  }
  function detail(dl, name, value) {
    dl.append(create('dt',name),create('dd',value));
  }
  function render() {
    list.replaceChildren();
    for (const user of rows) {
      const row = create('article',undefined,'admin-fila');
      const trigger = create('button');
      trigger.type = 'button';
      trigger.setAttribute('aria-expanded',String(expanded === user.id));
      const identity = create('span');
      identity.append(create('strong',user.nombre),create('small',user.mail));
      trigger.append(identity,create('span',expanded === user.id ? 'Cerrar −' : 'Detalles +'));
      trigger.addEventListener('click',() => { expanded = expanded === user.id ? null : user.id; render(); });
      row.append(trigger);
      if (expanded === user.id) {
        const box = create('div',undefined,'admin-detalles');
        const dl = create('dl');
        detail(dl,'Teléfono',user.telefono || 'No informado');
        detail(dl,'Administrador',Number(user.admin) ? 'Sí' : 'No');
        detail(dl,'Validación',user.validacion_nombre || `Nivel ${user.id_validacion}`);
        detail(dl,'Verificado',Number(user.verificado) ? 'Sí' : 'No');
        detail(dl,'Baneo',Number(user.baneado) ? 'Vigente' : 'Sin baneo vigente');
        detail(dl,'Bloqueo',Number(user.bloqueado) ? 'Bloqueada' : 'Sin bloqueo vigente');
        box.append(dl);
        const activity = create('div',undefined,'admin-actividad-resumen');
        activity.textContent = 'Cargando actividad…';
        box.append(activity);
        const activityActions = create('div',undefined,'admin-acciones');
        for (const [type,label] of [['campanas','Ver campañas'],['donaciones','Ver donaciones']]) {
          const button = create('button',label);
          button.type = 'button';
          button.addEventListener('click',()=>openActivity(user,type));
          activityActions.append(button);
        }
        box.append(activityActions);
        requestActivity(user,'resumen',activity);
        if (!Number(user.admin)) {
          const actions = create('div',undefined,'admin-acciones');
          const reset = create('button','Restablecer contraseña');
          reset.type = 'button';
          reset.addEventListener('click',()=>openDialog(user,false));
          actions.append(reset);
          if (Number(user.bloqueado)) {
            const unlock = create('button','Desbloquear y cambiar contraseña');
            unlock.type = 'button';
            unlock.addEventListener('click',()=>openDialog(user,true));
            actions.append(unlock);
          }
          const banButton = create('button', Number(user.baneado) ? 'Finalizar baneo' : 'Banear usuario');
          banButton.type = 'button';
          banButton.addEventListener('click', () => openBanDialog(user));
          actions.append(banButton);
          box.append(actions);
        }
        row.append(box);
      }
      list.append(row);
    }
  }
  const activityDialog = byId('admin-actividad-dialogo');
  const activityContent = byId('admin-actividad-contenido');
  const activityStatus = byId('admin-actividad-estado');
  let activityRequest = 0, summaryRequest = 0;
  async function requestActivity(user, type, target) {
    const requestId = type === 'resumen' ? ++summaryRequest : ++activityRequest;
    if (target) target.textContent = 'Cargando…';
    const params = new URLSearchParams({ruta:'admin_actividad',id_usuario:String(user.id),tipo:type});
    try {
      const response = await fetch(`${api}?${params}`, {credentials:'same-origin',headers:{Accept:'application/json'}});
      const result = await response.json();
      if (!response.ok) throw Error(result.error || 'No se pudo cargar la actividad.');
      if (requestId !== (type === 'resumen' ? summaryRequest : activityRequest)) return;
      if (type === 'resumen') {
        if (target && target.isConnected) target.textContent = `Campañas creadas: ${result.datos.campanas} · Donaciones realizadas: ${result.datos.donaciones}`;
      } else {
        activityStatus.textContent = '';
        renderActivity(type, Array.isArray(result.datos) ? result.datos : []);
      }
    } catch (error) {
      if (requestId !== (type === 'resumen' ? summaryRequest : activityRequest)) return;
      if (type === 'resumen') { if (target && target.isConnected) target.textContent = error.message; }
      else activityStatus.textContent = error.message;
    }
  }
  // Formatos de visualización: nunca se mezclan dinero y unidades de insumos.
  const ars = new Intl.NumberFormat('es-AR',{style:'currency',currency:'ARS',minimumFractionDigits:2,maximumFractionDigits:2});
  const units = new Intl.NumberFormat('es-AR',{maximumFractionDigits:2});
  const numberOrNull = value => (value === null || value === undefined || value === '' || !Number.isFinite(Number(value))) ? null : Number(value);
  function amount(value, monetary) {
    const number = numberOrNull(value);
    if (number === null) return 'No informado';
    return monetary ? ars.format(number) : `${units.format(number)} ${number === 1 ? 'unidad' : 'unidades'}`;
  }
  function localDate(value) {
    if (typeof value !== 'string') return 'No informada';
    const match = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::\d{2})?/.exec(value);
    return match ? `${match[3]}/${match[2]}/${match[1]} ${match[4]}:${match[5]}` : value;
  }
  function progress(item, monetary) {
    const objective = numberOrNull(item.objetivo);
    const achieved = numberOrNull(monetary ? item.logrado : item.parcial);
    if (objective === null || objective <= 0 || achieved === null) return null;
    const percent = Math.max(0, achieved / objective * 100);
    const box = create('div',undefined,'admin-progreso');
    const heading = create('div',undefined,'admin-progreso-encabezado');
    heading.append(create('span','Avance de la campaña'),create('strong',`${units.format(percent)} %`));
    const track = create('div',undefined,'admin-progreso-pista');
    track.setAttribute('role','progressbar');
    track.setAttribute('aria-label','Progreso de la campaña');
    track.setAttribute('aria-valuemin','0');
    track.setAttribute('aria-valuemax','100');
    track.setAttribute('aria-valuenow',String(Math.min(100,Math.round(percent))));
    const fill = create('div',undefined,'admin-progreso-relleno');
    fill.style.setProperty('--admin-progreso-final', `${Math.min(100,percent)}%`);
    track.append(fill);
    box.append(heading,track,create('small',`${amount(achieved,monetary)} de ${amount(objective,monetary)}`));
    return box;
  }
  function renderActivity(type, entries) {
    activityContent.replaceChildren();
    if (!entries.length) {
      activityContent.append(create('p',type === 'campanas' ? 'Este usuario todavía no creó campañas.' : 'Este usuario todavía no realizó donaciones.'));
      return;
    }
    const list = create('ul',undefined,'admin-actividad-lista');
    for (const item of entries) {
      const li = create('li');
      li.append(create('strong',type === 'campanas' ? item.nombre : item.campana_nombre));
      const dl = create('dl');
      if (type === 'campanas') {
        detail(dl,'Estado',item.estado ? String(item.estado).charAt(0).toUpperCase() + String(item.estado).slice(1) : 'No informado');
        detail(dl,'Tipo',Number(item.monetaria) ? 'Monetaria' : 'Insumos');
        detail(dl,'Categoría',item.nombre_categoria);
        detail(dl,'Objetivo',amount(item.objetivo,Boolean(Number(item.monetaria))));
        detail(dl,'Progreso',amount(Number(item.monetaria) ? item.logrado : item.parcial,Boolean(Number(item.monetaria))));
      } else {
        detail(dl,'Campaña ID',item.id_camp);
        detail(dl,'Tipo',Number(item.monetaria) ? 'Monetaria' : 'Insumos');
        detail(dl,Number(item.monetaria) ? 'Importe' : 'Cantidad',amount(item.cantidad,Boolean(Number(item.monetaria))));
        detail(dl,'Fecha',localDate(item.fecha_hora));
        if (item.categoria_insumo) detail(dl,'Categoría de insumo',item.categoria_insumo);
        if (item.subcategoria_insumo) detail(dl,'Subcategoría',item.subcategoria_insumo);
      }
      li.append(dl);
      if (type === 'campanas') {
        const bar = progress(item,Boolean(Number(item.monetaria)));
        if (bar) li.append(bar);
      }
      li.append(create('small',type === 'campanas' ? `Campaña ID: ${item.id}` : `Donación ID: ${item.id}`,'admin-actividad-id'));
      list.append(li);
    }
    activityContent.append(list);
  }
  function openActivity(user, type) {
    activityContent.replaceChildren();
    activityStatus.textContent = 'Cargando…';
    byId('admin-actividad-titulo').textContent = `${type === 'campanas' ? 'Campañas creadas' : 'Donaciones realizadas'} · ${user.nombre}`;
    activityDialog.showModal();
    requestActivity(user,type);
  }
  byId('admin-actividad-cerrar').addEventListener('click',()=>activityDialog.close());
  function openDialog(user, unlock) {
    action = unlock ? 'admin_desbloquear' : 'admin_password';
    form.reset();
    byId('admin-id-usuario').value = user.id;
    byId('admin-dialogo-titulo').textContent = unlock ? 'Desbloquear cuenta' : 'Restablecer contraseña';
    byId('admin-dialogo-descripcion').textContent = `${user.nombre} (${user.mail}). ${unlock ? 'Se establecerá una contraseña nueva y se reiniciarán los intentos fallidos.' : 'Se reemplazará la contraseña actual.'}`;
    byId('admin-dialogo-error').textContent = '';
    dialog.showModal();
  }
  form.addEventListener('submit',async event => {
    event.preventDefault();
    const submit = byId('admin-confirmar');
    submit.disabled = true;
    const payload = new FormData(form);
    payload.set('csrf_token',csrf);
    try {
      const response = await fetch(`${api}?ruta=${action}`,{method:'POST',body:payload,credentials:'same-origin',headers:{Accept:'application/json'}});
      const data = await response.json();
      if (!response.ok) throw Error(data.error || 'No se pudo completar la operación.');
      dialog.close();
      status.textContent = data.mensaje || 'Operación completada.';
      await fetchRows();
    } catch(error) { byId('admin-dialogo-error').textContent = error.message; }
    finally { submit.disabled = false; }
  });
  const banDialog = byId('admin-baneo-dialogo');
  const banForm = byId('admin-baneo-formulario');
  let banAction = '';
  function openBanDialog(user) {
    const lifting = Number(user.baneado) === 1;
    banAction = lifting ? 'finalizar_baneo' : 'banear';
    banForm.reset();
    byId('admin-baneo-usuario').value = user.id;
    byId('admin-baneo-id').value = user.id_baneo_vigente || '';
    byId('admin-baneo-titulo').textContent = lifting ? 'Finalizar baneo' : 'Banear usuario';
    byId('admin-baneo-descripcion').textContent = `${user.nombre} (${user.mail}). ${lifting ? 'El baneo vigente dejará de impedir el ingreso.' : 'El baneo impedirá el ingreso mientras esté vigente.'}`;
    const reason = byId('admin-baneo-motivo');
    reason.name = lifting ? 'motivo_unban' : 'motivo';
    reason.required = !lifting;
    byId('admin-baneo-motivo-label').firstChild.textContent = lifting ? 'Motivo de desbaneo (opcional)' : 'Motivo';
    byId('admin-baneo-fecha-label').hidden = lifting;
    byId('admin-baneo-error').textContent = '';
    if (lifting && !user.id_baneo_vigente) {
      status.textContent = 'No se encontró el identificador del baneo vigente. Actualizá la página.';
      return;
    }
    banDialog.showModal();
  }
  banForm.addEventListener('submit', async event => {
    event.preventDefault();
    const submit = byId('admin-baneo-confirmar');
    submit.disabled = true;
    const payload = new FormData(banForm);
    payload.set('csrf_token', csrf);
    try {
      const response = await fetch(`${api}?ruta=${banAction}`, {
        method:'POST', body:payload, credentials:'same-origin', headers:{Accept:'application/json'}
      });
      const data = await response.json();
      if (!response.ok) throw Error(data.mensaje || data.error || 'No se pudo gestionar el baneo.');
      banDialog.close();
      await fetchRows();
      status.textContent = data.mensaje || 'Baneo actualizado.';
    } catch (error) { byId('admin-baneo-error').textContent = error.message; }
    finally { submit.disabled = false; }
  });
  byId('admin-baneo-cancelar').addEventListener('click', () => banDialog.close());
  byId('admin-cancelar').addEventListener('click',()=>dialog.close());
  search.addEventListener('input',()=> { clearTimeout(debounce); debounce=setTimeout(()=>{page=1;fetchRows();},300); });
  limit.addEventListener('change',()=>{page=1;fetchRows();});
  previous.addEventListener('click',()=>{if(page>1){page--;fetchRows();}});
  next.addEventListener('click',()=>{page++;fetchRows();});
  fetchRows();
})();
