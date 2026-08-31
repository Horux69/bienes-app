<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión — Trazabilidad de Bienes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/app.css" rel="stylesheet">
</head>
<body class="auth-page">
<div class="auth-card">
  <div class="eyebrow">Sistema de trazabilidad</div>
  <h1>Iniciar sesión</h1>
  <p class="text-secondary small mb-4" id="loginSubtitulo">Ingrese sus credenciales para continuar.</p>
  <div id="alertInstalado" class="alert alert-success small d-none">Tabla de usuarios instalada. Cree su cuenta de administrador.</div>

  <form id="formLogin">
    <div class="mb-3">
      <label class="form-label" for="email">Correo electrónico</label>
      <input type="email" class="form-control" id="email" autocomplete="username" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="password">Contraseña</label>
      <input type="password" class="form-control" id="password" autocomplete="current-password" required>
    </div>
    <div id="campoConfirmar" class="mb-3 d-none">
      <label class="form-label" for="passwordConfirm">Confirmar contraseña</label>
      <input type="password" class="form-control" id="passwordConfirm" autocomplete="new-password">
    </div>
    <div id="campoNombre" class="mb-3 d-none">
      <label class="form-label" for="nombre">Nombre completo</label>
      <input type="text" class="form-control" id="nombre">
    </div>
    <button type="submit" class="btn btn-brand w-100" id="btnLogin">Ingresar</button>
    <div id="msgLogin" class="msg-feedback"></div>
  </form>
</div>

<script>
const API_AUTH = 'api/auth.php';
const API_USUARIOS = 'api/usuarios.php';
let modoSetup = false;

async function fetchJson(url, options = {}) {
  const resp = await fetch(url, { credentials: 'same-origin', ...options });
  const data = await resp.json().catch(() => ({}));
  return { resp, data };
}

async function verificarSesion() {
  const { resp, data } = await fetchJson(`${API_AUTH}?accion=me`);
  if (resp.ok && data.ok) {
    window.location.href = 'index.php';
  }
}

async function detectarSetup() {
  const msg = document.getElementById('msgLogin');
  const { resp, data } = await fetchJson(`${API_AUTH}?accion=setup`);

  if (!resp.ok) {
    msg.innerHTML = (data.error || 'Error de configuración.') +
      ' <a href="install.php" class="fw-semibold">Instalar tabla usuarios</a>';
    msg.className = 'alert alert-warning small mt-3 mb-0';
    document.getElementById('btnLogin').disabled = true;
    return;
  }

  if (data.ok && data.needs_setup) {
    activarModoSetup();
  }
}

function activarModoSetup() {
  modoSetup = true;
  document.getElementById('loginSubtitulo').textContent =
    'No hay usuarios. Cree la cuenta de administrador inicial.';
  document.getElementById('campoNombre').classList.remove('d-none');
  document.getElementById('campoConfirmar').classList.remove('d-none');
  document.getElementById('btnLogin').textContent = 'Crear administrador';
  document.getElementById('nombre').required = true;
  document.getElementById('passwordConfirm').required = true;
}

document.getElementById('formLogin').addEventListener('submit', async (e) => {
  e.preventDefault();
  const msg = document.getElementById('msgLogin');
  const btn = document.getElementById('btnLogin');
  msg.textContent = '';
  msg.className = 'msg-feedback';

  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;

  btn.disabled = true;

  try {
    if (modoSetup) {
      const nombre = document.getElementById('nombre').value.trim();
      const confirm = document.getElementById('passwordConfirm').value;
      if (password !== confirm) throw new Error('Las contraseñas no coinciden.');
      if (password.length < 8) throw new Error('La contraseña debe tener al menos 8 caracteres.');

      const { resp, data } = await fetchJson(API_USUARIOS, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre, email, password, rol: 'admin' }),
      });
      if (!resp.ok || !data.ok) throw new Error(data.error || 'No se pudo crear el administrador.');
      window.location.href = 'index.php';
      return;
    }

    const { resp, data } = await fetchJson(`${API_AUTH}?accion=login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    if (!resp.ok || !data.ok) throw new Error(data.error || 'Credenciales incorrectas.');
    window.location.href = 'index.php';
  } catch (err) {
    msg.textContent = err.message;
    msg.className = 'msg-feedback text-danger';
  } finally {
    btn.disabled = false;
  }
});

verificarSesion();
detectarSetup();

if (new URLSearchParams(window.location.search).get('instalado') === '1') {
  document.getElementById('alertInstalado').classList.remove('d-none');
}
</script>
</body>
</html>
