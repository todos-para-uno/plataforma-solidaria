(() => {
  'use strict';
  const root = document.getElementById('gestion-usuarios');
  if (!root) return;
  const get = id => document.getElementById(id);
  const list = get('admin-mensajes-lista'), status = get('admin-mensajes-estado');
  const search = get('admin-mensajes-busqueda'), previous = get('admin-mensajes-anterior');
  const next = get('admin-mensajes-siguiente'), indicator = get('admin-mensajes-pagina');
  const tab = get('tab-mensajes'), order = get('admin-mensajes-orden');
  let page = 1, pages = 1, debounce, controller, loaded = false;
  const make = (tag, text, cls) => {
    const node = document.createElement(tag);
    if (text !== undefined) node.textContent = String(text);
    if (cls) node.className = cls;
    return node;
  };
  const date = value => {
    if (!value) return 'Fecha desconocida';
    const parsed = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(parsed.getTime()) ? String(value)
      : new Intl.DateTimeFormat('es-AR',{dateStyle:'medium',timeStyle:'short'}).format(parsed);
  };
  const subjects = {
    consulta:'Consulta general',campana:'Problema con una campaña',
    donacion:'Problema con una donación',cuenta:'Mi cuenta',
    denuncia:'Denunciar una campaña'
  };
  function updatePager() {
    indicator.textContent = `Página ${page} de ${pages}`;
    previous.disabled = page <= 1;
    next.disabled = page >= pages;
  }
  async function showMessages(mail, holder, button) {
    const open = button.getAttribute('aria-expanded') !== 'true';
    button.setAttribute('aria-expanded',String(open));
    holder.classList.toggle('admin-mensajes-abierto',open);
    if (!open || holder.dataset.loaded === 'true') return;
    const body = holder.querySelector('.admin-mensajes-interior');
    body.replaceChildren(make('p','Cargando mensajes…'));
    try {
      const params = new URLSearchParams({ruta:'admin_mensajes',mail});
      const response = await fetch(`${root.dataset.api}?${params}`,{credentials:'same-origin',headers:{Accept:'application/json'}});
      const data = await response.json();
      if (!response.ok) throw Error(data.error || 'No se pudieron cargar los mensajes.');
      if (button.getAttribute('aria-expanded') !== 'true') return;
      body.replaceChildren();
      for (const item of data.mensajes || []) {
        const article = make('article',undefined,'admin-mensaje');
        const heading = make('div',undefined,'admin-mensaje-encabezado');
        heading.append(make('strong',subjects[item.asunto] || item.asunto),
          make('time',date(item.fecha_hora)));
        const content = make('p',item.contenido,'admin-mensaje-texto');
        const meta = make('small',`Mensaje #${item.id} · ${item.nombre}`,'admin-mensaje-meta');
        const reply = make('a','Responder por correo ↗','admin-mensaje-responder');
        reply.href = `mailto:${mail}?subject=${encodeURIComponent('Re: '+(subjects[item.asunto] || item.asunto))}`;
        article.append(heading,content,meta,reply);
        body.append(article);
      }
      if (!body.children.length) body.append(make('p','No se encontraron mensajes.'));
      holder.dataset.loaded = 'true';
    } catch(error) {
      body.replaceChildren(make('p',error.message,'admin-mensaje-error'));
    }
  }
  function render(data) {
    list.replaceChildren();
    const senders = data.remitentes || [];
    for (const sender of senders) {
      const item = make('article',undefined,'admin-mensajes-remitente');
      const button = make('button',undefined,'admin-mensajes-cabecera');
      button.type = 'button';
      button.setAttribute('aria-expanded','false');
      const info = make('span',undefined,'admin-mensajes-identidad');
      info.append(make('strong',sender.nombre),make('small',sender.mail));
      const summary = make('span',undefined,'admin-mensajes-cantidad');
      summary.append(make('span',`${sender.cantidad} mensaje(s)`),
        make('small',date(sender.ultima_fecha)),make('span','⌄','admin-mensajes-flecha'));
      button.append(info,summary);
      const details = make('div',undefined,'admin-mensajes-desplegable');
      details.append(make('div',undefined,'admin-mensajes-interior'));
      button.addEventListener('click',()=>showMessages(sender.mail,item,button));
      item.append(button,details);
      list.append(item);
    }
    if (!senders.length) {
      const empty = make('div',undefined,'admin-mensajes-vacio');
      empty.append(make('span','✉','admin-mensajes-vacio-icono'),
        make('p','No hay mensajes para mostrar.'));
      list.append(empty);
    }
  }
  async function load() {
    if (controller) controller.abort();
    controller = new AbortController();
    status.textContent = 'Cargando remitentes…';
    const params = new URLSearchParams({ruta:'admin_mensajes',q:search.value,pagina:String(page),orden:order.value});
    try {
      const response = await fetch(`${root.dataset.api}?${params}`,{
        credentials:'same-origin',headers:{Accept:'application/json'},signal:controller.signal
      });
      const data = await response.json();
      if (!response.ok) throw Error(data.error || 'No se pudieron cargar los mensajes.');
      pages = Math.max(1,Math.ceil(Number(data.total)/15));
      if (page > pages) {page = pages; load(); return;}
      render(data);
      status.textContent = `${data.total} remitente(s) encontrados.`;
      updatePager();
      loaded = true;
    } catch(error) {
      if (error.name === 'AbortError') return;
      status.textContent = error.message;
      previous.disabled = next.disabled = true;
    }
  }
  tab.addEventListener('click',()=>{if(!loaded) load();});
  tab.addEventListener('focus',()=>{if(tab.getAttribute('aria-selected')==='true'&&!loaded) load();});
  search.addEventListener('input',()=>{clearTimeout(debounce);debounce=setTimeout(()=>{page=1;load();},300);});
  order.addEventListener('change',()=>{page=1;load();});
  previous.addEventListener('click',()=>{if(page>1){page--;load();}});
  next.addEventListener('click',()=>{if(page<pages){page++;load();}});
  updatePager();
})();
