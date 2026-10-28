<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión · SiColMed</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/Assets/CSS/Dedicated/Auth/auth.css">
</head>
<body>
<div class="auth">

  <aside class="panel panel--login">
    <div class="brand">
      <span class="brand__logo"><svg class="i" viewBox="0 0 24 24"><path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/></svg></span>
      <div><div class="brand__name">SiColMed</div><div class="brand__sub">Colegio Médico de Panamá</div></div>
    </div>

    <div class="panel__main">
      <div class="glass glass--img">
        <img src="/Assets/IMG/login.jpg" alt="Comunidad médica conectada">
      </div>
      <div>
        <h2>Bienvenidos<br>a SiColMed</h2>
        <p class="panel__lead">Gestión eficiente para el<br>Colegio Médico de Panamá.</p>
      </div>
    </div>

    <div class="panel__foot">
      <div class="secure"><svg class="i" viewBox="0 0 24 24"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>Seguridad y confidencialidad</div>
      <p>Sistema de Información para la Gestión<br>del Colegio Médico de Panamá</p>
    </div>
  </aside>

  <section class="side">
    <header class="side__top">
      <div class="brand">
        <span class="brand__logo"><svg class="i" viewBox="0 0 24 24"><path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/></svg></span>
        <div><div class="brand__name">SiColMed</div><div class="brand__sub">Colegio Médico de Panamá</div></div>
      </div>
    </header>

    <main class="side__main">
      <div class="form-wrap">
        <h1>Iniciar sesión</h1>
        <p class="sub">Ingresa tus credenciales para acceder a SiColMed.</p>

        <form id="login-form" action="#" method="post">
          <div class="field">
            <label class="lbl" for="usuario">Correo electrónico o usuario</label>
            <div class="control">
              <svg class="i" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              <input id="usuario" name="usuario" type="text" placeholder="Ingresa tu correo o usuario" autocomplete="username" required>
            </div>
          </div>

          <div class="field">
            <label class="lbl" for="password">Contraseña</label>
            <div class="control">
              <svg class="i" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input id="password" name="password" type="password" placeholder="Ingresa tu contraseña" autocomplete="current-password" required>
              <button class="toggle" type="button" aria-label="Mostrar u ocultar contraseña" data-toggle="password">
                <svg class="i" viewBox="0 0 24 24"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>

          <div class="row">
            <label class="check"><input type="checkbox" name="recordar"> Recordar sesión</label>
            <a href="#">¿Olvidaste tu contraseña?</a>
          </div>

          <button class="btn" type="submit">Iniciar sesión <svg class="i" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></button>
        </form>

        <div class="divider"></div>
        <p class="alt" style="margin:0">¿No tienes cuenta? <a href="/register">Regístrate como médico</a></p>
        <div class="pill"><svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>Se requiere idoneidad validada</div>
      </div>
    </main>

    <footer class="legal">
      <p>© 2026 SiColMed · Todos los derechos reservados</p>
      <a href="#">Política de Privacidad</a><span>•</span><a href="#">Términos de Servicio</a>
    </footer>
  </section>

</div>
<script type="module" src="/Assets/JavaScript/Dedicated/Auth/login.js"></script>
<script>
  // Respaldo para navegadores o servidores que no carguen el módulo ES.
  document.addEventListener('submit', async function (event) {
    const form = event.target;
    if (form.id !== 'login-form' || form.dataset.loginBound === 'true') return;
    event.preventDefault();
    form.dataset.loginBound = 'true';
    form.dataset.loginSubmitting = 'true';
    const button = form.querySelector('button[type="submit"]');
    const usuario = form.querySelector('#usuario').value.trim();
    const password = form.querySelector('#password').value;
    const recordar = form.querySelector('input[name="recordar"]')?.checked ?? false;
    if (button) button.disabled = true;
    let status = form.querySelector('[data-auth-status]');
    if (!status) {
      status = document.createElement('p');
      status.setAttribute('data-auth-status', '');
      status.setAttribute('role', 'status');
      form.prepend(status);
    }
    status.textContent = 'Iniciando sesión...';
    try {
      const response = await fetch('http://localhost:8000/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ username: usuario, password })
      });
      const body = await response.json().catch(() => null);
      if (!response.ok) {
        throw new Error(body?.detail || 'No fue posible iniciar sesión.');
      }
      if (!body?.access_token) {
        throw new Error('La respuesta no contiene un token JWT.');
      }
      const storage = recordar ? localStorage : sessionStorage;
      localStorage.removeItem('sicolmed_access_token');
      sessionStorage.removeItem('sicolmed_access_token');
      storage.setItem('sicolmed_access_token', body.access_token);
      sessionStorage.setItem('sicolmed_token_storage', recordar ? 'local' : 'session');
      status.textContent = 'Sesión iniciada correctamente.';
    } catch (error) {
      status.textContent = error instanceof Error ? error.message : 'No fue posible iniciar sesión.';
      form.dataset.loginBound = '';
    } finally {
      form.dataset.loginSubmitting = '';
      if (button) button.disabled = false;
    }
  });

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-toggle="password"]');
    if (!button) return;
    const input = document.getElementById(button.dataset.toggle);
    if (!input) return;
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
  });
</script>
</body>
</html>
