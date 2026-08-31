<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trazabilidad de Bienes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/app.css" rel="stylesheet">
</head>
<body>

<div class="app-shell d-none" id="appPrincipal">

  <!-- Sidebar (desktop) -->
  <aside class="app-sidebar">
    <div class="sidebar-brand">
      <div class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h10M4 17h14"/><rect x="2" y="3" width="20" height="18" rx="2"/></svg>
      </div>
      <div class="brand-text">
        <span class="brand-title">Trazabilidad</span>
        <span class="brand-sub">Registro de bienes</span>
      </div>
    </div>

    <nav class="sidebar-nav" id="tabSwitch">
      <div class="nav-group">
        <span class="nav-group-label">Menú</span>
        <button type="button" class="nav-link active" data-tab="registrar">
          <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 5v14M5 12h14"/></svg>
          <span>Registrar</span>
        </button>
        <button type="button" class="nav-link" data-tab="listado">
          <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
          <span>Listado</span>
        </button>
        <button type="button" class="nav-link" data-tab="perfil">
          <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="8" r="4"/><path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/></svg>
          <span>Perfil</span>
        </button>
      </div>

      <div class="nav-group d-none" id="navGroupAdmin">
        <span class="nav-group-label">Administración</span>
        <button type="button" class="nav-link" id="tabCatalogosItem" data-tab="catalogos">
          <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 7h5l2 2h11v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/></svg>
          <span>Catálogos</span>
        </button>
        <button type="button" class="nav-link" id="tabUsuariosItem" data-tab="usuarios">
          <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 19c0-2.8 2.7-5 6-5s6 2.2 6 5M14 19c0-2 1.6-3.6 3.5-3.9"/></svg>
          <span>Usuarios</span>
        </button>
      </div>
    </nav>

    <div class="sidebar-user">
      <div class="user-card">
        <div class="user-avatar" id="usuarioAvatar" aria-hidden="true">—</div>
        <div class="user-info">
          <div class="user-name" id="usuarioNombre">—</div>
          <div class="user-role" id="usuarioRol">—</div>
        </div>
      </div>
      <button type="button" class="btn-logout" id="btnLogout">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Cerrar sesión
      </button>
    </div>
  </aside>

  <main class="app-main">
    <header class="app-header">
      <h2 id="pageTitle">Registrar bien</h2>
      <span class="rol-badge d-md-none" id="usuarioRolMobile">—</span>
    </header>

    <div class="app-content">

      <!-- REGISTRAR -->
      <div id="panelRegistrar">
        <form id="formBien" class="card-panel">
          <input type="hidden" id="registroId" value="">
          <div id="codigoActual" class="d-none mb-3">
            <span class="code-badge" id="codigoTexto"></span>
          </div>

          <div class="form-registro-grid">
            <div class="form-registro-main">
              <div class="section-title">Ubicación y responsable</div>
              <div class="row g-3 mb-4">
                <div class="col-md-12">
                  <label class="form-label required" for="municipio_id">Municipio</label>
                  <select class="form-select" id="municipio_id" required>
                    <option value="">Seleccione municipio…</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label required" for="juzgado_id">Juzgado</label>
                  <select class="form-select" id="juzgado_id" required disabled>
                    <option value="">Seleccione juzgado…</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label required" for="responsable_id">Responsable</label>
                  <select class="form-select" id="responsable_id" required disabled>
                    <option value="">Seleccione responsable…</option>
                  </select>
                </div>
              </div>

              <div class="section-title">Datos del bien</div>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="form-label required" for="fecha_registro">Fecha</label>
                  <input type="date" class="form-control" id="fecha_registro" required>
                </div>
                <div class="col-md-5">
                  <label class="form-label required" for="tipo_bien_id">Tipo de bien</label>
                  <select class="form-select" id="tipo_bien_id" required>
                    <option value="">Seleccione tipo…</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label required" for="cantidad">Cantidad</label>
                  <input type="number" class="form-control" id="cantidad" min="1" value="1" required>
                </div>
              </div>

              <div class="section-title">Periféricos</div>
              <div id="listaPerifericos" class="mb-2"></div>
              <button type="button" class="btn btn-outline-secondary btn-sm mb-4" id="btnAgregarPeriferico">+ Agregar periférico</button>

              <div class="section-title">Observaciones</div>
              <textarea class="form-control" id="observaciones" rows="3"
                        placeholder="Estado, ubicación, notas adicionales…"></textarea>
            </div>

            <div class="form-registro-sidebar">
              <div class="section-title">Fotografías</div>
              <p class="small text-secondary mb-2">Toque una foto para verla en grande.</p>
              <div class="photo-grid" id="photoGrid">
                <div class="photo-add" id="photoAdd" title="Agregar foto">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 16l-5-5L5 20"/></svg>
                </div>
              </div>
              <input type="file" accept="image/*" capture="environment" class="d-none" id="photoInput">
            </div>

            <div class="form-registro-full">
              <div class="form-actions">
                <button type="submit" class="btn btn-brand flex-fill flex-md-grow-0" id="btnGuardar">Registrar bien</button>
                <button type="button" class="btn btn-outline-secondary d-none" id="btnCancelar">Cancelar</button>
              </div>
              <div id="msgForm" class="msg-feedback"></div>
            </div>
          </div>
        </form>
      </div>

      <!-- LISTADO -->
      <div id="panelListado" class="d-none">
        <div class="listado-toolbar">
          <div class="search-wrap">
            <input type="search" class="form-control" id="buscarListado"
                   placeholder="Buscar por código, juzgado, municipio…">
          </div>
          <span class="stats-pill" id="statsRegistros">0 registros</span>
        </div>
        <div id="listaRegistros" class="registros-grid"></div>
      </div>

      <!-- PERFIL -->
      <div id="panelPerfil" class="d-none">
        <div class="row justify-content-center">
          <div class="col-lg-8 col-xl-6">
            <div class="card-panel">
              <div class="section-title">Mi perfil</div>
              <form id="formPerfil">
                <div class="mb-3">
                  <label class="form-label required" for="perfilNombre">Nombre</label>
                  <input type="text" class="form-control" id="perfilNombre" required>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="perfilEmail">Correo</label>
                  <input type="email" class="form-control" id="perfilEmail" readonly disabled>
                  <div class="form-text">Solo un administrador puede cambiar el correo.</div>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="perfilRol">Rol</label>
                  <input type="text" class="form-control" id="perfilRol" readonly disabled>
                </div>
                <hr>
                <p class="small text-secondary">Para cambiar su contraseña complete los campos. Déjelos vacíos si no desea cambiarla.</p>
                <div class="mb-3">
                  <label class="form-label" for="perfilPasswordActual">Contraseña actual</label>
                  <input type="password" class="form-control" id="perfilPasswordActual" autocomplete="current-password">
                </div>
                <div class="mb-3">
                  <label class="form-label" for="perfilPasswordNueva">Nueva contraseña</label>
                  <input type="password" class="form-control" id="perfilPasswordNueva" minlength="8" autocomplete="new-password">
                </div>
                <div class="mb-3">
                  <label class="form-label" for="perfilPasswordConfirm">Confirmar contraseña</label>
                  <input type="password" class="form-control" id="perfilPasswordConfirm" minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-brand w-100" id="btnGuardarPerfil">Guardar cambios</button>
                <div id="msgPerfil" class="msg-feedback"></div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- CATÁLOGOS -->
      <div id="panelCatalogos" class="d-none">
        <div class="catalogo-tabs nav" id="catalogoTabs">
          <button type="button" class="nav-link active" data-catalogo="municipios">Municipios</button>
          <button type="button" class="nav-link" data-catalogo="juzgados">Juzgados</button>
          <button type="button" class="nav-link" data-catalogo="responsables">Responsables</button>
          <button type="button" class="nav-link" data-catalogo="tipos_bienes">Tipos de bien</button>
          <button type="button" class="nav-link" data-catalogo="perifericos">Periféricos</button>
        </div>
        <div class="admin-split">
          <div class="card-panel">
            <h6 class="fw-bold mb-3" id="catalogoFormTitulo">Nuevo municipio</h6>
            <form id="formCatalogo">
              <input type="hidden" id="catalogoEditId" value="">
              <div id="catalogoCamposExtra"></div>
              <div class="mb-3">
                <label class="form-label required" for="catalogoNombre">Nombre</label>
                <input type="text" class="form-control" id="catalogoNombre" required>
              </div>
              <div class="mb-3 d-none" id="campoUnidad">
                <label class="form-label" for="catalogoUnidad">Unidad</label>
                <input type="text" class="form-control" id="catalogoUnidad" value="unidad">
              </div>
              <div class="mb-3 d-none" id="campoActivo">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="catalogoActivo" checked>
                  <label class="form-check-label" for="catalogoActivo">Activo</label>
                </div>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-brand btn-sm flex-fill" id="btnGuardarCatalogo">Agregar</button>
                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btnCancelarCatalogo">Cancelar</button>
              </div>
              <div id="msgCatalogo" class="msg-feedback"></div>
            </form>
          </div>
          <div class="card-panel list-scroll p-0 px-3" id="listaCatalogo"></div>
        </div>
      </div>

      <!-- USUARIOS -->
      <div id="panelUsuarios" class="d-none">
        <div class="admin-split">
          <div class="card-panel">
            <h6 class="fw-bold mb-3" id="usuarioFormTitulo">Nuevo usuario</h6>
            <form id="formUsuario">
              <input type="hidden" id="usuarioEditId" value="">
              <div class="mb-3">
                <label class="form-label required" for="usuarioNombreInput">Nombre</label>
                <input type="text" class="form-control" id="usuarioNombreInput" required>
              </div>
              <div class="mb-3">
                <label class="form-label required" for="usuarioEmailInput">Correo</label>
                <input type="email" class="form-control" id="usuarioEmailInput" required>
              </div>
              <div class="mb-3">
                <label class="form-label" for="usuarioPasswordInput">Contraseña</label>
                <input type="password" class="form-control" id="usuarioPasswordInput" minlength="8">
                <div class="form-text" id="usuarioPasswordAyuda">Obligatoria al crear usuario.</div>
              </div>
              <div class="mb-3">
                <label class="form-label required" for="usuarioRolInput">Rol</label>
                <select class="form-select" id="usuarioRolInput" required>
                  <option value="operario">Operario</option>
                  <option value="admin">Administrador</option>
                </select>
              </div>
              <div class="mb-3 d-none" id="campoUsuarioActivo">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="usuarioActivoInput" checked>
                  <label class="form-check-label" for="usuarioActivoInput">Activo</label>
                </div>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-brand btn-sm flex-fill" id="btnGuardarUsuario">Crear usuario</button>
                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btnCancelarUsuario">Cancelar</button>
              </div>
              <div id="msgUsuario" class="msg-feedback"></div>
            </form>
          </div>
          <div class="card-panel list-scroll p-0 px-3" id="listaUsuarios"></div>
        </div>
      </div>

    </div>
  </main>

  <!-- Bottom nav (mobile/tablet) -->
  <nav class="app-bottom-nav" id="tabSwitchMobile" aria-label="Navegación principal">
    <button type="button" class="nav-link active" data-tab="registrar">
      <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 5v14M5 12h14"/></svg>
      <span>Registrar</span>
    </button>
    <button type="button" class="nav-link" data-tab="listado">
      <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
      <span>Listado</span>
    </button>
    <button type="button" class="nav-link" data-tab="perfil">
      <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="8" r="4"/><path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/></svg>
      <span>Perfil</span>
    </button>
    <button type="button" class="nav-link d-none" id="tabCatalogosMobile" data-tab="catalogos">
      <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 7h5l2 2h11v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/></svg>
      <span>Catálogos</span>
    </button>
    <button type="button" class="nav-link d-none" id="tabUsuariosMobile" data-tab="usuarios">
      <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 19c0-2.8 2.7-5 6-5s6 2.2 6 5M14 19c0-2 1.6-3.6 3.5-3.9"/></svg>
      <span>Usuarios</span>
    </button>
  </nav>
</div>

<!-- Modals -->
<div class="modal fade" id="modalFoto" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
    <div class="modal-content bg-dark text-white border-0">
      <div class="modal-header border-0 py-2">
        <h6 class="modal-title">Fotografía</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <img src="" alt="Fotografía" id="modalFotoImg">
      </div>
      <div class="modal-footer border-0 py-2">
        <button type="button" class="btn btn-outline-light btn-sm" id="btnFotoAnterior">← Anterior</button>
        <span class="small" id="modalFotoContador">1 / 1</span>
        <button type="button" class="btn btn-outline-light btn-sm" id="btnFotoSiguiente">Siguiente →</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDetalle" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold">Detalle del bien</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalDetalleBody"></div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-brand btn-sm" id="btnEditarModal">Editar</button>
        <button type="button" class="btn btn-outline-danger btn-sm" id="btnEliminarModal">Eliminar</button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
