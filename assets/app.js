document.addEventListener('DOMContentLoaded', () => {
  const API_AUTH = 'api/auth.php';
  const API_USUARIOS = 'api/usuarios.php';
  const API_REGISTROS = 'api/bienes.php';
  const API_CATALOGOS = 'api/catalogos.php';
  const API_ADMIN = 'api/admin_catalogos.php';

  const appPrincipal     = document.getElementById('appPrincipal');
  const pageTitle        = document.getElementById('pageTitle');
  const statsRegistros   = document.getElementById('statsRegistros');
  const tabButtons       = document.querySelectorAll('#tabSwitch .nav-link, #tabSwitchMobile .nav-link');
  const panelRegistrar   = document.getElementById('panelRegistrar');
  const panelListado     = document.getElementById('panelListado');
  const panelCatalogos   = document.getElementById('panelCatalogos');
  const panelUsuarios    = document.getElementById('panelUsuarios');
  const panelPerfil      = document.getElementById('panelPerfil');
  const formBien         = document.getElementById('formBien');
  const registroId       = document.getElementById('registroId');
  const codigoActual     = document.getElementById('codigoActual');
  const codigoTexto      = document.getElementById('codigoTexto');
  const municipioSelect  = document.getElementById('municipio_id');
  const juzgadoSelect    = document.getElementById('juzgado_id');
  const responsableSelect= document.getElementById('responsable_id');
  const tipoSelect       = document.getElementById('tipo_bien_id');
  const fechaInput       = document.getElementById('fecha_registro');
  const listaPerifericos = document.getElementById('listaPerifericos');
  const btnAgregarPerif  = document.getElementById('btnAgregarPeriferico');
  const photoGrid        = document.getElementById('photoGrid');
  const photoAdd         = document.getElementById('photoAdd');
  const photoInput       = document.getElementById('photoInput');
  const btnGuardar       = document.getElementById('btnGuardar');
  const btnCancelar      = document.getElementById('btnCancelar');
  const msgForm          = document.getElementById('msgForm');
  const listaRegistros   = document.getElementById('listaRegistros');
  const buscarListado    = document.getElementById('buscarListado');
  const modalDetalle     = new bootstrap.Modal(document.getElementById('modalDetalle'));
  const modalDetalleBody = document.getElementById('modalDetalleBody');
  const btnEditarModal   = document.getElementById('btnEditarModal');
  const btnEliminarModal = document.getElementById('btnEliminarModal');
  const modalFoto        = new bootstrap.Modal(document.getElementById('modalFoto'));
  const modalFotoImg     = document.getElementById('modalFotoImg');
  const modalFotoContador= document.getElementById('modalFotoContador');
  const btnFotoAnterior  = document.getElementById('btnFotoAnterior');
  const btnFotoSiguiente = document.getElementById('btnFotoSiguiente');
  const catalogoTabs     = document.querySelectorAll('#catalogoTabs button');
  const formCatalogo     = document.getElementById('formCatalogo');
  const catalogoEditId   = document.getElementById('catalogoEditId');
  const catalogoCamposExtra = document.getElementById('catalogoCamposExtra');
  const catalogoNombre   = document.getElementById('catalogoNombre');
  const catalogoUnidad   = document.getElementById('catalogoUnidad');
  const campoUnidad      = document.getElementById('campoUnidad');
  const campoActivo      = document.getElementById('campoActivo');
  const catalogoActivo   = document.getElementById('catalogoActivo');
  const btnGuardarCatalogo = document.getElementById('btnGuardarCatalogo');
  const btnCancelarCatalogo = document.getElementById('btnCancelarCatalogo');
  const listaCatalogo    = document.getElementById('listaCatalogo');
  const msgCatalogo      = document.getElementById('msgCatalogo');
  const catalogoFormTitulo = document.getElementById('catalogoFormTitulo');
  const formUsuario        = document.getElementById('formUsuario');
  const usuarioEditId      = document.getElementById('usuarioEditId');
  const usuarioNombreInput = document.getElementById('usuarioNombreInput');
  const usuarioEmailInput  = document.getElementById('usuarioEmailInput');
  const usuarioPasswordInput = document.getElementById('usuarioPasswordInput');
  const usuarioRolInput    = document.getElementById('usuarioRolInput');
  const usuarioActivoInput = document.getElementById('usuarioActivoInput');
  const campoUsuarioActivo = document.getElementById('campoUsuarioActivo');
  const usuarioPasswordAyuda = document.getElementById('usuarioPasswordAyuda');
  const btnGuardarUsuario  = document.getElementById('btnGuardarUsuario');
  const btnCancelarUsuario = document.getElementById('btnCancelarUsuario');
  const listaUsuarios      = document.getElementById('listaUsuarios');
  const msgUsuario         = document.getElementById('msgUsuario');
  const usuarioFormTitulo  = document.getElementById('usuarioFormTitulo');
  const btnLogout          = document.getElementById('btnLogout');
  const formPerfil         = document.getElementById('formPerfil');
  const perfilNombre       = document.getElementById('perfilNombre');
  const perfilEmail        = document.getElementById('perfilEmail');
  const perfilRol          = document.getElementById('perfilRol');
  const perfilPasswordActual = document.getElementById('perfilPasswordActual');
  const perfilPasswordNueva  = document.getElementById('perfilPasswordNueva');
  const perfilPasswordConfirm = document.getElementById('perfilPasswordConfirm');
  const btnGuardarPerfil   = document.getElementById('btnGuardarPerfil');
  const msgPerfil          = document.getElementById('msgPerfil');

  const THUMB_PLACEHOLDER = `<div class="item-thumb-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 16l-5-5L5 20"/></svg></div>`;

  let catalogoPerifericos = [];
  let fotosNuevas = [];
  let fotosExistentes = [];
  let fotosEliminar = [];
  let registrosCache = [];
  let registroSeleccionado = null;
  let catalogoActual = 'municipios';
  let itemsCatalogoCache = [];
  let galeriaUrls = [];
  let galeriaIndice = 0;
  let catalogosListos = null;
  let usuarioSesion = null;

  fechaInput.value = new Date().toISOString().slice(0, 10);

  function resetMsg(el) {
    if (!el) return;
    el.textContent = '';
    el.className = 'msg-feedback';
  }

  async function fetchApi(url, options = {}) {
    const resp = await fetch(url, { credentials: 'same-origin', ...options });
    const data = await resp.json().catch(() => ({}));
    if (resp.status === 401) {
      window.location.href = 'login.php';
      throw new Error('Sesión expirada.');
    }
    return { resp, data };
  }

  async function verificarSesion() {
    const { resp, data } = await fetchApi(`${API_AUTH}?accion=me`);
    if (!resp.ok || !data.ok) {
      window.location.href = 'login.php';
      return false;
    }

    usuarioSesion = data.usuario;
    document.getElementById('usuarioNombre').textContent = usuarioSesion.nombre;
    const rolTexto = usuarioSesion.rol === 'admin' ? 'Administrador' : 'Operario';
    document.getElementById('usuarioRol').textContent = rolTexto;
    const avatar = document.getElementById('usuarioAvatar');
    if (avatar) {
      const inicial = (usuarioSesion.nombre || '?').trim().charAt(0).toUpperCase();
      avatar.textContent = inicial || '?';
    }
    const rolMobile = document.getElementById('usuarioRolMobile');
    if (rolMobile) rolMobile.textContent = rolTexto;

    if (usuarioSesion.rol === 'admin') {
      document.getElementById('navGroupAdmin')?.classList.remove('d-none');
      ['tabCatalogosMobile', 'tabUsuariosMobile'].forEach(id => {
        document.getElementById(id)?.classList.remove('d-none');
      });
    }

    appPrincipal.classList.remove('d-none');
    return true;
  }

  btnLogout.addEventListener('click', async () => {
    await fetchApi(`${API_AUTH}?accion=logout`, { method: 'POST' });
    window.location.href = 'login.php';
  });

  const TAB_TITLES = {
    registrar: 'Registrar bien',
    listado: 'Listado de bienes',
    perfil: 'Mi perfil',
    catalogos: 'Catálogos',
    usuarios: 'Usuarios',
  };

  function cambiarTab(tab) {
    tabButtons.forEach(b => {
      b.classList.toggle('active', b.dataset.tab === tab);
    });
    if (pageTitle) pageTitle.textContent = TAB_TITLES[tab] || 'Trazabilidad';
    panelRegistrar.classList.toggle('d-none', tab !== 'registrar');
    panelListado.classList.toggle('d-none', tab !== 'listado');
    panelCatalogos.classList.toggle('d-none', tab !== 'catalogos');
    panelUsuarios.classList.toggle('d-none', tab !== 'usuarios');
    panelPerfil.classList.toggle('d-none', tab !== 'perfil');
    if (tab === 'listado') cargarListado();
    if (tab === 'perfil') cargarPerfil();
    if (tab === 'catalogos') resetFormularioCatalogo().then(() => cargarCatalogoAdmin());
    if (tab === 'usuarios') { resetFormularioUsuario(); cargarUsuarios(); }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => cambiarTab(btn.dataset.tab));
  });

  // ─── Catálogos ───────────────────────────────────────────
  async function fetchCatalogo(tipo, params = {}) {
    const qs = new URLSearchParams({ tipo, ...params });
    const { data } = await fetchApi(`${API_CATALOGOS}?${qs}`);
    if (!data.ok) throw new Error(data.error || 'Error al cargar catálogo.');
    return data.items;
  }

  function llenarSelect(select, items, placeholder, valorSeleccionado = '') {
    select.innerHTML = `<option value="">${placeholder}</option>` +
      items.map(i => `<option value="${String(i.id)}">${escapeHtml(i.nombre)}</option>`).join('');
    if (valorSeleccionado !== '' && valorSeleccionado !== null && valorSeleccionado !== undefined) {
      select.value = String(valorSeleccionado);
    }
  }

  async function cargarCatalogosIniciales() {
    const [municipios, tipos, perifericos] = await Promise.all([
      fetchCatalogo('municipios'),
      fetchCatalogo('tipos_bienes'),
      fetchCatalogo('perifericos'),
    ]);
    llenarSelect(municipioSelect, municipios, 'Seleccione municipio…');
    llenarSelect(tipoSelect, tipos, 'Seleccione tipo…');
    catalogoPerifericos = perifericos;
  }

  function asegurarCatalogos() {
    if (!catalogosListos) {
      catalogosListos = cargarCatalogosIniciales().catch(err => {
        catalogosListos = null;
        throw err;
      });
    }
    return catalogosListos;
  }

  async function cargarJuzgados(municipioId, valorSeleccionado = '') {
    juzgadoSelect.disabled = true;
    responsableSelect.disabled = true;
    llenarSelect(juzgadoSelect, [], 'Cargando juzgados…');
    llenarSelect(responsableSelect, [], 'Seleccione responsable…');

    if (!municipioId) {
      llenarSelect(juzgadoSelect, [], 'Seleccione juzgado…');
      return;
    }

    const juzgados = await fetchCatalogo('juzgados', { municipio_id: String(municipioId) });
    llenarSelect(juzgadoSelect, juzgados, juzgados.length ? 'Seleccione juzgado…' : 'Sin juzgados disponibles', valorSeleccionado);
    juzgadoSelect.disabled = false;
  }

  async function cargarResponsables(juzgadoId, valorSeleccionado = '') {
    responsableSelect.disabled = true;
    llenarSelect(responsableSelect, [], 'Cargando responsables…');

    if (!juzgadoId) {
      llenarSelect(responsableSelect, [], 'Seleccione responsable…');
      return;
    }

    const responsables = await fetchCatalogo('responsables', { juzgado_id: String(juzgadoId) });
    llenarSelect(
      responsableSelect,
      responsables,
      responsables.length ? 'Seleccione responsable…' : 'Sin responsables disponibles',
      valorSeleccionado
    );
    responsableSelect.disabled = false;
  }

  municipioSelect.addEventListener('change', async () => {
    try {
      await cargarJuzgados(municipioSelect.value);
    } catch (err) {
      llenarSelect(juzgadoSelect, [], 'Error al cargar juzgados');
      msgForm.textContent = err.message;
      msgForm.className = 'msg-feedback text-danger';
    }
  });

  juzgadoSelect.addEventListener('change', async () => {
    try {
      await cargarResponsables(juzgadoSelect.value);
    } catch (err) {
      llenarSelect(responsableSelect, [], 'Error al cargar responsables');
      msgForm.textContent = err.message;
      msgForm.className = 'msg-feedback text-danger';
    }
  });

  // ─── Periféricos dinámicos ─────────────────────────────────
  function agregarFilaPeriferico(perifericoId = '', cantidad = 1) {
    const row = document.createElement('div');
    row.className = 'periferico-row';
    row.innerHTML = `
      <select class="form-select form-select-sm periferico-select">
        <option value="">Periférico…</option>
        ${catalogoPerifericos.map(p =>
          `<option value="${String(p.id)}" ${String(p.id) === String(perifericoId) ? 'selected' : ''}>${escapeHtml(p.nombre)}</option>`
        ).join('')}
      </select>
      <input type="number" class="form-control form-control-sm periferico-cant" min="1" value="${cantidad}">
      <button type="button" class="btn btn-sm btn-outline-danger">&times;</button>
    `;
    row.querySelector('button').addEventListener('click', () => row.remove());
    listaPerifericos.appendChild(row);
  }

  btnAgregarPerif.addEventListener('click', () => agregarFilaPeriferico());

  function obtenerPerifericosFormulario() {
    return Array.from(listaPerifericos.querySelectorAll('.periferico-row')).map(row => ({
      periferico_id: row.querySelector('.periferico-select').value,
      cantidad: parseInt(row.querySelector('.periferico-cant').value, 10) || 1,
    })).filter(p => p.periferico_id);
  }

  // ─── Fotos ───────────────────────────────────────────────
  function comprimirImagen(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = e => {
        const img = new Image();
        img.onload = () => {
          const maxW = 900;
          const scale = Math.min(1, maxW / img.width);
          const canvas = document.createElement('canvas');
          canvas.width = img.width * scale;
          canvas.height = img.height * scale;
          canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
          resolve(canvas.toDataURL('image/jpeg', 0.7));
        };
        img.onerror = reject;
        img.src = e.target.result;
      };
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  photoAdd.addEventListener('click', () => photoInput.click());
  photoInput.addEventListener('change', async () => {
    const file = photoInput.files[0];
    if (!file) return;
    const dataUrl = await comprimirImagen(file);
    fotosNuevas.push(dataUrl);
    renderFotos();
    photoInput.value = '';
  });

  function renderFotos() {
    photoGrid.querySelectorAll('.photo-thumb').forEach(el => el.remove());
    const todasUrls = obtenerUrlsVisibles();

    fotosExistentes.forEach((foto, idx) => {
      const thumb = crearThumb(foto.ruta, () => {
        fotosEliminar.push(foto.id);
        fotosExistentes = fotosExistentes.filter(f => f.id !== foto.id);
        renderFotos();
      }, () => abrirGaleria(todasUrls, idx));
      photoGrid.insertBefore(thumb, photoAdd);
    });

    fotosNuevas.forEach((src, idx) => {
      const indice = fotosExistentes.length + idx;
      const thumb = crearThumb(src, () => {
        fotosNuevas.splice(idx, 1);
        renderFotos();
      }, () => abrirGaleria(todasUrls, indice));
      photoGrid.insertBefore(thumb, photoAdd);
    });
  }

  function crearThumb(src, onRemove, onView) {
    const div = document.createElement('div');
    div.className = 'photo-thumb';
    div.innerHTML = `<img src="${escapeAttr(src)}" alt=""><button type="button">&times;</button>`;
    div.querySelector('img').addEventListener('click', (e) => {
      e.stopPropagation();
      if (onView) onView();
    });
    div.querySelector('button').addEventListener('click', (e) => {
      e.stopPropagation();
      onRemove();
    });
    return div;
  }

  function obtenerUrlsVisibles() {
    return [
      ...fotosExistentes.map(f => f.ruta),
      ...fotosNuevas,
    ];
  }

  function abrirGaleria(urls, indice = 0) {
    if (!urls.length) return;
    galeriaUrls = urls;
    galeriaIndice = indice;
    mostrarFotoGaleria();
    modalFoto.show();
  }

  function mostrarFotoGaleria() {
    modalFotoImg.src = galeriaUrls[galeriaIndice];
    modalFotoContador.textContent = `${galeriaIndice + 1} / ${galeriaUrls.length}`;
    btnFotoAnterior.disabled = galeriaIndice === 0;
    btnFotoSiguiente.disabled = galeriaIndice === galeriaUrls.length - 1;
  }

  btnFotoAnterior.addEventListener('click', () => {
    if (galeriaIndice > 0) {
      galeriaIndice--;
      mostrarFotoGaleria();
    }
  });

  btnFotoSiguiente.addEventListener('click', () => {
    if (galeriaIndice < galeriaUrls.length - 1) {
      galeriaIndice++;
      mostrarFotoGaleria();
    }
  });

  function limpiarFotos() {
    fotosNuevas = [];
    fotosExistentes = [];
    fotosEliminar = [];
    renderFotos();
  }

  // ─── Validación ────────────────────────────────────────────
  function validarFormulario() {
    const campos = [
      ['municipio_id', 'Municipio'],
      ['juzgado_id', 'Juzgado'],
      ['responsable_id', 'Responsable'],
      ['fecha_registro', 'Fecha de registro'],
      ['tipo_bien_id', 'Tipo de bien'],
      ['cantidad', 'Cantidad'],
    ];
    for (const [id, label] of campos) {
      const el = document.getElementById(id);
      if (!el.value) {
        el.focus();
        return `Complete el campo obligatorio: ${label}.`;
      }
    }
    if (parseInt(document.getElementById('cantidad').value, 10) < 1) {
      return 'La cantidad debe ser mayor a 0.';
    }
    return null;
  }

  function obtenerPayload(esEdicion) {
    const payload = {
      municipio_id: parseInt(municipioSelect.value, 10),
      juzgado_id: parseInt(juzgadoSelect.value, 10),
      responsable_id: parseInt(responsableSelect.value, 10),
      tipo_bien_id: parseInt(tipoSelect.value, 10),
      cantidad: parseInt(document.getElementById('cantidad').value, 10),
      fecha_registro: fechaInput.value,
      observaciones: document.getElementById('observaciones').value.trim(),
      perifericos: obtenerPerifericosFormulario(),
    };
    if (esEdicion) {
      payload.id = parseInt(registroId.value, 10);
      payload.fotos_nuevas = fotosNuevas;
      payload.fotos_eliminar = fotosEliminar;
    } else {
      payload.fotos = fotosNuevas;
    }
    return payload;
  }

  // ─── Guardar ─────────────────────────────────────────────
  formBien.addEventListener('submit', async (e) => {
    e.preventDefault();
    resetMsg(msgForm);

    const error = validarFormulario();
    if (error) {
      msgForm.textContent = error;
      msgForm.classList.add('text-danger');
      return;
    }

    const esEdicion = !!registroId.value;
    const payload = obtenerPayload(esEdicion);

    btnGuardar.disabled = true;
    btnGuardar.textContent = esEdicion ? 'Actualizando…' : 'Registrando…';

    try {
      const { resp, data } = await fetchApi(API_REGISTROS, {
        method: esEdicion ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!resp.ok || !data.ok) throw new Error(data.error || 'Error al guardar.');

      const codigo = data.registro?.codigo || '';
      msgForm.textContent = data.mensaje + (codigo ? ` Código: ${codigo}` : '');
      msgForm.classList.add('text-success');

      if (!esEdicion) resetFormulario();
    } catch (err) {
      msgForm.textContent = err.message;
      msgForm.classList.add('text-danger');
    } finally {
      btnGuardar.disabled = false;
      btnGuardar.textContent = esEdicion ? 'Actualizar bien' : 'Registrar bien';
    }
  });

  btnCancelar.addEventListener('click', () => {
    resetFormulario();
    cambiarTab('registrar');
  });

  function resetFormulario() {
    formBien.reset();
    registroId.value = '';
    fechaInput.value = new Date().toISOString().slice(0, 10);
    codigoActual.classList.add('d-none');
    btnCancelar.classList.add('d-none');
    btnGuardar.textContent = 'Registrar bien';
    juzgadoSelect.disabled = true;
    responsableSelect.disabled = true;
    llenarSelect(juzgadoSelect, [], 'Seleccione juzgado…');
    llenarSelect(responsableSelect, [], 'Seleccione responsable…');
    listaPerifericos.innerHTML = '';
    limpiarFotos();
    asegurarCatalogos().catch(() => {});
  }

  async function cargarEnFormulario(reg) {
    await asegurarCatalogos();

    registroId.value = reg.id;
    codigoTexto.textContent = reg.codigo;
    codigoActual.classList.remove('d-none');
    btnCancelar.classList.remove('d-none');
    btnGuardar.textContent = 'Actualizar bien';

    municipioSelect.value = String(reg.municipio_id);
    await cargarJuzgados(reg.municipio_id, reg.juzgado_id);
    await cargarResponsables(reg.juzgado_id, reg.responsable_id);

    fechaInput.value = reg.fecha_registro?.slice(0, 10) || '';
    tipoSelect.value = String(reg.tipo_bien_id);
    document.getElementById('cantidad').value = reg.cantidad;
    document.getElementById('observaciones').value = reg.observaciones || '';

    listaPerifericos.innerHTML = '';
    (reg.perifericos || []).forEach(p => agregarFilaPeriferico(p.periferico_id, p.cantidad));

    fotosNuevas = [];
    fotosEliminar = [];
    fotosExistentes = [...(reg.fotos || [])];
    renderFotos();
    cambiarTab('registrar');
  }

  // ─── Listado ───────────────────────────────────────────────
  async function cargarListado() {
    listaRegistros.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="spinner-border spinner-border-sm text-secondary"></div><p class="mt-2 mb-0 small">Cargando…</p></div>';
    try {
      const { data } = await fetchApi(API_REGISTROS);
      if (!data.ok) throw new Error(data.error || 'Error al cargar.');
      registrosCache = data.registros;
      renderListado(registrosCache);
    } catch (err) {
      listaRegistros.innerHTML = `<div class="text-center text-danger small py-4">${escapeHtml(err.message)}</div>`;
    }
  }

  buscarListado.addEventListener('input', () => {
    const q = buscarListado.value.trim().toLowerCase();
    if (!q) { renderListado(registrosCache); return; }
    const filtrados = registrosCache.filter(r =>
      [r.codigo, r.municipio_nombre, r.juzgado_nombre, r.responsable_nombre, r.tipo_bien_nombre]
        .some(c => (c || '').toLowerCase().includes(q))
    );
    renderListado(filtrados);
  });

  function renderListado(registros) {
    if (statsRegistros) {
      statsRegistros.textContent = `${registros.length} registro${registros.length !== 1 ? 's' : ''}`;
    }

    if (registros.length === 0) {
      listaRegistros.innerHTML = `
        <div class="empty-state" style="grid-column:1/-1">
          <div class="empty-icon">—</div>
          <p class="mb-0">No hay registros para mostrar.</p>
        </div>`;
      return;
    }

    listaRegistros.innerHTML = registros.map(r => `
      <div class="item-card" data-id="${r.id}">
        ${r.foto_principal
          ? `<img src="${escapeAttr(r.foto_principal)}" class="item-thumb" alt="">`
          : THUMB_PLACEHOLDER}
        <div class="code-badge mb-2">${escapeHtml(r.codigo)}</div>
        <div class="item-title">${escapeHtml(r.tipo_bien_nombre)}</div>
        <div class="item-meta">${escapeHtml(r.juzgado_nombre)} · ${escapeHtml(r.municipio_nombre)}</div>
        <div class="item-meta mt-1">Cant: ${r.cantidad} · ${formatearFecha(r.fecha_registro)}</div>
      </div>
    `).join('');

    listaRegistros.querySelectorAll('.item-card').forEach(el => {
      el.addEventListener('click', async () => {
        try {
          const { data } = await fetchApi(`${API_REGISTROS}?id=${el.dataset.id}`);
          if (!data.ok) throw new Error(data.error);
          mostrarDetalle(data.registro);
        } catch (err) {
          alert(err.message);
        }
      });

      const img = el.querySelector('.item-thumb');
      if (img) {
        img.addEventListener('click', async (e) => {
          e.stopPropagation();
          try {
            const { data } = await fetchApi(`${API_REGISTROS}?id=${el.dataset.id}`);
            if (!data.ok) throw new Error(data.error);
            const fotos = (data.registro.fotos || []).map(f => f.ruta);
            if (fotos.length) abrirGaleria(fotos, 0);
          } catch (err) {
            alert(err.message);
          }
        });
      }
    });
  }

  function mostrarDetalle(reg) {
    registroSeleccionado = reg;
    const perifTexto = (reg.perifericos || []).length
      ? reg.perifericos.map(p => `${escapeHtml(p.nombre)} (${p.cantidad})`).join(', ')
      : '—';
    const fotos = reg.fotos || [];
    const fotosUrls = fotos.map(f => f.ruta);
    const fotosHtml = fotos.length
      ? `<div class="foto-galeria mb-3">${fotos.map((f, i) =>
          `<img src="${escapeAttr(f.ruta)}" alt="Foto ${i + 1}" data-idx="${i}">`
        ).join('')}</div>`
      : '';

    modalDetalleBody.innerHTML = `
      <div class="text-center mb-3"><span class="code-badge">${escapeHtml(reg.codigo)}</span></div>
      ${fotosHtml}
      <dl class="row small mb-0">
        <dt class="col-5">Municipio</dt><dd class="col-7">${escapeHtml(reg.municipio_nombre)}</dd>
        <dt class="col-5">Juzgado</dt><dd class="col-7">${escapeHtml(reg.juzgado_nombre)}</dd>
        <dt class="col-5">Responsable</dt><dd class="col-7">${escapeHtml(reg.responsable_nombre)}</dd>
        <dt class="col-5">Fecha</dt><dd class="col-7">${formatearFecha(reg.fecha_registro)}</dd>
        <dt class="col-5">Tipo de bien</dt><dd class="col-7">${escapeHtml(reg.tipo_bien_nombre)}</dd>
        <dt class="col-5">Cantidad</dt><dd class="col-7">${reg.cantidad} ${escapeHtml(reg.tipo_bien_unidad || '')}</dd>
        <dt class="col-5">Periféricos</dt><dd class="col-7">${perifTexto}</dd>
        <dt class="col-5">Observaciones</dt><dd class="col-7">${escapeHtml(reg.observaciones || '—')}</dd>
      </dl>
    `;
    modalDetalleBody.querySelectorAll('.foto-galeria img').forEach(img => {
      img.addEventListener('click', () => {
        abrirGaleria(fotosUrls, parseInt(img.dataset.idx, 10));
      });
    });
    modalDetalle.show();
  }

  btnEditarModal.addEventListener('click', () => {
    modalDetalle.hide();
    if (registroSeleccionado) cargarEnFormulario(registroSeleccionado);
  });

  btnEliminarModal.addEventListener('click', async () => {
    if (!registroSeleccionado) return;
    if (!confirm(`¿Eliminar el registro ${registroSeleccionado.codigo}?`)) return;
    try {
      const { resp, data } = await fetchApi(`${API_REGISTROS}?id=${registroSeleccionado.id}`, { method: 'DELETE' });
      if (!resp.ok || !data.ok) throw new Error(data.error || 'Error al eliminar.');
      modalDetalle.hide();
      registroSeleccionado = null;
      cargarListado();
    } catch (err) {
      alert(err.message);
    }
  });

  // ─── Administración de catálogos ───────────────────────────
  const catalogoLabels = {
    municipios: 'municipio',
    juzgados: 'juzgado',
    responsables: 'responsable',
    tipos_bienes: 'tipo de bien',
    perifericos: 'periférico',
  };

  catalogoTabs.forEach(btn => {
    btn.addEventListener('click', async () => {
      catalogoTabs.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      catalogoActual = btn.dataset.catalogo;
      await resetFormularioCatalogo();
      await cargarCatalogoAdmin();
    });
  });

  async function fetchAdminCatalogo(tipo) {
    const { data } = await fetchApi(`${API_ADMIN}?tipo=${encodeURIComponent(tipo)}`);
    if (!data.ok) throw new Error(data.error || 'Error al cargar catálogo.');
    return data.items;
  }

  async function renderCamposExtraCatalogo(item = null) {
    catalogoCamposExtra.innerHTML = '';

    if (catalogoActual === 'juzgados') {
      const municipios = await fetchAdminCatalogo('municipios');
      const activos = municipios.filter(m => m.activo);
      catalogoCamposExtra.innerHTML = `
        <div class="mb-2">
          <label class="form-label required" for="catalogoMunicipioId">Municipio</label>
          <select class="form-select" id="catalogoMunicipioId" required>
            <option value="">Seleccione municipio…</option>
            ${activos.map(m => `<option value="${String(m.id)}" ${String(item?.municipio_id) === String(m.id) ? 'selected' : ''}>${escapeHtml(m.nombre)}</option>`).join('')}
          </select>
        </div>`;
    }

    if (catalogoActual === 'responsables') {
      const juzgados = await fetchAdminCatalogo('juzgados');
      const activos = juzgados.filter(j => j.activo);
      catalogoCamposExtra.innerHTML = `
        <div class="mb-2">
          <label class="form-label required" for="catalogoJuzgadoId">Juzgado</label>
          <select class="form-select" id="catalogoJuzgadoId" required>
            <option value="">Seleccione juzgado…</option>
            ${activos.map(j => `<option value="${String(j.id)}" ${String(item?.juzgado_id) === String(j.id) ? 'selected' : ''}>${escapeHtml(j.municipio_nombre)} · ${escapeHtml(j.nombre)}</option>`).join('')}
          </select>
        </div>`;
    }
  }

  async function resetFormularioCatalogo() {
    formCatalogo.reset();
    catalogoEditId.value = '';
    catalogoUnidad.value = 'unidad';
    catalogoActivo.checked = true;
    campoUnidad.classList.toggle('d-none', catalogoActual !== 'tipos_bienes');
    campoActivo.classList.add('d-none');
    btnCancelarCatalogo.classList.add('d-none');
    btnGuardarCatalogo.textContent = 'Agregar';
    catalogoFormTitulo.textContent = `Nuevo ${catalogoLabels[catalogoActual]}`;
    resetMsg(msgCatalogo);
    await renderCamposExtraCatalogo();
  }

  async function cargarCatalogoAdmin() {
    listaCatalogo.innerHTML = '<div class="text-center text-secondary small py-4">Cargando…</div>';
    try {
      itemsCatalogoCache = await fetchAdminCatalogo(catalogoActual);
      renderListaCatalogo();
      if (!catalogoCamposExtra.innerHTML.trim()) {
        await renderCamposExtraCatalogo();
      }
    } catch (err) {
      listaCatalogo.innerHTML = `<div class="text-center text-danger small py-4">${escapeHtml(err.message)}</div>`;
    }
  }

  function renderListaCatalogo() {
    if (!itemsCatalogoCache.length) {
      listaCatalogo.innerHTML = '<div class="empty-state"><div class="empty-icon">·</div><p class="mb-0">No hay registros.</p></div>';
      return;
    }

    listaCatalogo.innerHTML = itemsCatalogoCache.map(item => {
      let detalle = '';
      if (catalogoActual === 'juzgados') detalle = `<div class="small text-secondary">${escapeHtml(item.municipio_nombre)}</div>`;
      if (catalogoActual === 'responsables') detalle = `<div class="small text-secondary">${escapeHtml(item.municipio_nombre)} · ${escapeHtml(item.juzgado_nombre)}</div>`;
      if (catalogoActual === 'tipos_bienes') detalle = `<div class="small text-secondary">Unidad: ${escapeHtml(item.unidad)}</div>`;

      return `
        <div class="catalogo-item ${item.activo ? '' : 'inactivo'}">
          <div class="flex-fill">
            <div class="fw-semibold small">${escapeHtml(item.nombre)}</div>
            ${detalle}
            ${!item.activo ? '<span class="badge bg-secondary badge-inactivo">Inactivo</span>' : ''}
          </div>
          <button type="button" class="btn btn-outline-primary btn-sm" data-edit="${item.id}">Editar</button>
          ${item.activo ? `<button type="button" class="btn btn-outline-danger btn-sm" data-del="${item.id}">Desactivar</button>` : ''}
        </div>`;
    }).join('');

    listaCatalogo.querySelectorAll('[data-edit]').forEach(btn => {
      btn.addEventListener('click', () => editarCatalogo(parseInt(btn.dataset.edit, 10)));
    });
    listaCatalogo.querySelectorAll('[data-del]').forEach(btn => {
      btn.addEventListener('click', () => desactivarCatalogo(parseInt(btn.dataset.del, 10)));
    });
  }

  async function editarCatalogo(id) {
    const item = itemsCatalogoCache.find(i => i.id === id);
    if (!item) return;

    catalogoEditId.value = item.id;
    catalogoNombre.value = item.nombre;
    catalogoActivo.checked = !!item.activo;
    campoActivo.classList.remove('d-none');
    btnCancelarCatalogo.classList.remove('d-none');
    btnGuardarCatalogo.textContent = 'Actualizar';
    catalogoFormTitulo.textContent = `Editar ${catalogoLabels[catalogoActual]}`;

    if (catalogoActual === 'tipos_bienes') {
      catalogoUnidad.value = item.unidad || 'unidad';
    }

    await renderCamposExtraCatalogo(item);
    catalogoNombre.focus();
  }

  btnCancelarCatalogo.addEventListener('click', resetFormularioCatalogo);

  formCatalogo.addEventListener('submit', async (e) => {
    e.preventDefault();
    resetMsg(msgCatalogo);

    const payload = {
      tipo: catalogoActual,
      nombre: catalogoNombre.value.trim(),
    };

    if (catalogoActual === 'juzgados') {
      payload.municipio_id = parseInt(document.getElementById('catalogoMunicipioId')?.value || '0', 10);
      if (!payload.municipio_id) {
        msgCatalogo.textContent = 'Seleccione un municipio.';
        msgCatalogo.classList.add('text-danger');
        return;
      }
    }

    if (catalogoActual === 'responsables') {
      payload.juzgado_id = parseInt(document.getElementById('catalogoJuzgadoId')?.value || '0', 10);
      if (!payload.juzgado_id) {
        msgCatalogo.textContent = 'Seleccione un juzgado.';
        msgCatalogo.classList.add('text-danger');
        return;
      }
    }

    if (catalogoActual === 'tipos_bienes') {
      payload.unidad = catalogoUnidad.value.trim() || 'unidad';
    }

    const esEdicion = !!catalogoEditId.value;
    if (esEdicion) {
      payload.id = parseInt(catalogoEditId.value, 10);
      payload.activo = catalogoActivo.checked;
    }

    btnGuardarCatalogo.disabled = true;

    try {
      const { resp, data } = await fetchApi(API_ADMIN, {
        method: esEdicion ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!resp.ok || !data.ok) throw new Error(data.error || 'Error al guardar.');

      msgCatalogo.textContent = data.mensaje;
      msgCatalogo.classList.add('text-success');
      await resetFormularioCatalogo();
      await cargarCatalogoAdmin();
      catalogosListos = null;
      await asegurarCatalogos();
    } catch (err) {
      msgCatalogo.textContent = err.message;
      msgCatalogo.classList.add('text-danger');
    } finally {
      btnGuardarCatalogo.disabled = false;
    }
  });

  async function desactivarCatalogo(id) {
    const item = itemsCatalogoCache.find(i => i.id === id);
    if (!item || !confirm(`¿Desactivar "${item.nombre}"?`)) return;

    try {
      const { resp, data } = await fetchApi(`${API_ADMIN}?tipo=${catalogoActual}&id=${id}`, { method: 'DELETE' });
      if (!resp.ok || !data.ok) throw new Error(data.error || 'Error al desactivar.');
      await cargarCatalogoAdmin();
      catalogosListos = null;
      await asegurarCatalogos();
    } catch (err) {
      alert(err.message);
    }
  }

  // ─── Perfil del usuario ────────────────────────────────────
  function cargarPerfil() {
    if (!usuarioSesion) return;

    perfilNombre.value = usuarioSesion.nombre;
    perfilEmail.value = usuarioSesion.email;
    perfilRol.value = usuarioSesion.rol === 'admin' ? 'Administrador' : 'Operario';
    perfilPasswordActual.value = '';
    perfilPasswordNueva.value = '';
    perfilPasswordConfirm.value = '';
    resetMsg(msgPerfil);
  }

  formPerfil.addEventListener('submit', async (e) => {
    e.preventDefault();
    resetMsg(msgPerfil);

    const nombre = perfilNombre.value.trim();
    const passwordNueva = perfilPasswordNueva.value;
    const passwordConfirm = perfilPasswordConfirm.value;
    const passwordActual = perfilPasswordActual.value;

    if (!nombre) {
      msgPerfil.textContent = 'El nombre es obligatorio.';
      msgPerfil.classList.add('text-danger');
      return;
    }

    if (passwordNueva || passwordConfirm || passwordActual) {
      if (!passwordActual) {
        msgPerfil.textContent = 'Ingrese su contraseña actual para cambiarla.';
        msgPerfil.classList.add('text-danger');
        return;
      }
      if (passwordNueva.length < 8) {
        msgPerfil.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.';
        msgPerfil.classList.add('text-danger');
        return;
      }
      if (passwordNueva !== passwordConfirm) {
        msgPerfil.textContent = 'Las contraseñas nuevas no coinciden.';
        msgPerfil.classList.add('text-danger');
        return;
      }
    }

    const payload = {
      id: usuarioSesion.id,
      nombre,
    };
    if (passwordNueva) {
      payload.password = passwordNueva;
      payload.password_actual = passwordActual;
    }

    btnGuardarPerfil.disabled = true;

    try {
      const { resp, data } = await fetchApi(API_USUARIOS, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!resp.ok || !data.ok) throw new Error(data.error || 'Error al actualizar perfil.');

      usuarioSesion = data.usuario;
      document.getElementById('usuarioNombre').textContent = usuarioSesion.nombre;
      const avatarEl = document.getElementById('usuarioAvatar');
      if (avatarEl) {
        avatarEl.textContent = (usuarioSesion.nombre || '?').trim().charAt(0).toUpperCase() || '?';
      }
      perfilPasswordActual.value = '';
      perfilPasswordNueva.value = '';
      perfilPasswordConfirm.value = '';
      msgPerfil.textContent = data.mensaje;
      msgPerfil.classList.add('text-success');
    } catch (err) {
      msgPerfil.textContent = err.message;
      msgPerfil.classList.add('text-danger');
    } finally {
      btnGuardarPerfil.disabled = false;
    }
  });

  // ─── Administración de usuarios (solo admin) ───────────────
  let usuariosCache = [];

  function resetFormularioUsuario() {
    formUsuario.reset();
    usuarioEditId.value = '';
    usuarioRolInput.disabled = false;
    usuarioEmailInput.disabled = false;
    campoUsuarioActivo.classList.add('d-none');
    btnCancelarUsuario.classList.add('d-none');
    btnGuardarUsuario.textContent = 'Crear usuario';
    usuarioFormTitulo.textContent = 'Nuevo usuario';
    usuarioPasswordInput.required = true;
    usuarioPasswordAyuda.textContent = 'Obligatoria al crear usuario.';
    resetMsg(msgUsuario);
  }

  async function cargarUsuarios() {
    listaUsuarios.innerHTML = '<div class="text-center text-secondary small py-4">Cargando…</div>';
    try {
      const { data } = await fetchApi(API_USUARIOS);
      if (!data.ok) throw new Error(data.error || 'Error al cargar usuarios.');
      usuariosCache = data.usuarios;
      renderListaUsuarios();
    } catch (err) {
      listaUsuarios.innerHTML = `<div class="text-center text-danger small py-4">${escapeHtml(err.message)}</div>`;
    }
  }

  function renderListaUsuarios() {
    if (!usuariosCache.length) {
      listaUsuarios.innerHTML = '<div class="empty-state"><div class="empty-icon">·</div><p class="mb-0">No hay usuarios.</p></div>';
      return;
    }

    listaUsuarios.innerHTML = usuariosCache.map(u => `
      <div class="catalogo-item ${u.activo ? '' : 'inactivo'}">
        <div class="flex-fill">
          <div class="fw-semibold small">${escapeHtml(u.nombre)}</div>
          <div class="small text-secondary">${escapeHtml(u.email)}</div>
          <div class="small text-secondary">${u.rol === 'admin' ? 'Administrador' : 'Operario'}</div>
          ${!u.activo ? '<span class="badge bg-secondary badge-inactivo">Inactivo</span>' : ''}
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" data-edit-user="${u.id}">Editar</button>
        ${u.activo && u.id !== usuarioSesion.id
          ? `<button type="button" class="btn btn-outline-danger btn-sm" data-del-user="${u.id}">Desactivar</button>`
          : ''}
      </div>
    `).join('');

    listaUsuarios.querySelectorAll('[data-edit-user]').forEach(btn => {
      btn.addEventListener('click', () => editarUsuario(parseInt(btn.dataset.editUser, 10)));
    });
    listaUsuarios.querySelectorAll('[data-del-user]').forEach(btn => {
      btn.addEventListener('click', () => desactivarUsuario(parseInt(btn.dataset.delUser, 10)));
    });
  }

  function editarUsuario(id) {
    const u = usuariosCache.find(item => item.id === id);
    if (!u) return;

    usuarioEditId.value = u.id;
    usuarioNombreInput.value = u.nombre;
    usuarioEmailInput.value = u.email;
    usuarioRolInput.value = u.rol;
    usuarioActivoInput.checked = !!u.activo;
    usuarioPasswordInput.value = '';
    usuarioPasswordInput.required = false;
    usuarioPasswordAyuda.textContent = 'Dejar vacío para no cambiar la contraseña.';
    campoUsuarioActivo.classList.remove('d-none');
    btnCancelarUsuario.classList.remove('d-none');
    btnGuardarUsuario.textContent = 'Actualizar usuario';
    usuarioFormTitulo.textContent = 'Editar usuario';
    usuarioNombreInput.focus();
  }

  btnCancelarUsuario.addEventListener('click', resetFormularioUsuario);

  formUsuario.addEventListener('submit', async (e) => {
    e.preventDefault();
    resetMsg(msgUsuario);

    const esEdicion = !!usuarioEditId.value;
    const payload = {
      nombre: usuarioNombreInput.value.trim(),
      email: usuarioEmailInput.value.trim(),
      rol: usuarioRolInput.value,
    };

    const password = usuarioPasswordInput.value;
    if (!esEdicion && password.length < 8) {
      msgUsuario.textContent = 'La contraseña debe tener al menos 8 caracteres.';
      msgUsuario.classList.add('text-danger');
      return;
    }
    if (password) payload.password = password;
    if (esEdicion) {
      payload.id = parseInt(usuarioEditId.value, 10);
      payload.activo = usuarioActivoInput.checked;
    }

    btnGuardarUsuario.disabled = true;

    try {
      const { resp, data } = await fetchApi(API_USUARIOS, {
        method: esEdicion ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!resp.ok || !data.ok) throw new Error(data.error || 'Error al guardar usuario.');

      msgUsuario.textContent = data.mensaje;
      msgUsuario.classList.add('text-success');
      resetFormularioUsuario();
      await cargarUsuarios();
    } catch (err) {
      msgUsuario.textContent = err.message;
      msgUsuario.classList.add('text-danger');
    } finally {
      btnGuardarUsuario.disabled = false;
    }
  });

  async function desactivarUsuario(id) {
    const u = usuariosCache.find(item => item.id === id);
    if (!u || !confirm(`¿Desactivar al usuario "${u.nombre}"?`)) return;

    try {
      const { resp, data } = await fetchApi(`${API_USUARIOS}?id=${id}`, { method: 'DELETE' });
      if (!resp.ok || !data.ok) throw new Error(data.error || 'Error al desactivar.');
      await cargarUsuarios();
    } catch (err) {
      alert(err.message);
    }
  }

  // ─── Utilidades ──────────────────────────────────────────
  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c =>
      ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])
    );
  }

  function escapeAttr(str) {
    return String(str ?? '').replace(/"/g, '&quot;');
  }

  function formatearFecha(fecha) {
    if (!fecha) return '—';
    const [y, m, d] = fecha.slice(0, 10).split('-');
    return `${d}/${m}/${y}`;
  }

  verificarSesion().then(ok => {
    if (!ok) return;
    catalogosListos = cargarCatalogosIniciales().catch(err => {
      catalogosListos = null;
      msgForm.textContent = 'Error al cargar catálogos: ' + err.message;
      msgForm.className = 'msg-feedback text-danger';
    });
  });
});
