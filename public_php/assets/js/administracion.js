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
  const tabs = [byId('tab-usuarios'),byId('tab-mensajes')];
  tabs.forEach((tab,index)=>tab.addEventListener('click',()=>{
    tabs.forEach((item,i)=>item.setAttribute('aria-selected',String(i===index)));
    byId('panel-usuarios').hidden = index!==0;
    byId('panel-mensajes').hidden = index!==1;
  }));
  fetchRows();
})();
