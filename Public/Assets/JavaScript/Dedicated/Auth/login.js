// Comportamiento de la vista de inicio de sesión.
// El navegador solo envía usuario y contraseña a PHP (SesionController). PHP habla con la API
// y guarda el JWT en la sesión del servidor: aquí no se guarda ni se lee ningún token.
import { initializePasswordToggles } from '../../Functions/Auth/passwordToggle.js';
import { showStatus } from '../../Functions/Auth/formStatus.js';

const REQUEST_TIMEOUT_MS = 15000;

async function iniciarSesion(form, credenciales) {
  const sessionUrl = form.dataset.sessionUrl;
  if (!sessionUrl) throw new Error('No se pudo preparar la sesión. Inténtalo de nuevo.');

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
  let response;
  try {
    response = await fetch(sessionUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(credenciales),
      signal: controller.signal
    });
  } catch (error) {
    if (error && error.name === 'AbortError') {
      throw new Error('El servidor tardó demasiado en responder. Inténtalo de nuevo.');
    }
    throw new Error('No se pudo conectar con el servidor. Verifica tu conexión e inténtalo de nuevo.');
  } finally {
    clearTimeout(timer);
  }

  let body = null;
  try {
    body = await response.json();
  } catch (_error) {
    // El mensaje genérico de abajo evita mostrar respuestas no JSON al usuario.
  }
  if (!response.ok || !body || body.ok !== true) {
    throw new Error(body && typeof body.mensaje === 'string'
      ? body.mensaje
      : 'No fue posible iniciar sesión. Inténtalo de nuevo.');
  }
  return body;
}

let intentosFallidos = 0;

export function initializeLoginForm() {
  const form = document.getElementById('login-form');
  if (!form) return;

  const usernameInput = form.querySelector('#usuario');
  const passwordInput = form.querySelector('#password');
  const button = form.querySelector('button[type="submit"]');

  // Modal de recuperación de contraseña
  const enlaceOlvido = document.getElementById('enlace-olvido');
  const modalRecuperar = document.getElementById('modal-recuperar');
  const btnCerrarModal = document.getElementById('cerrar-modal-recuperar');

  if (enlaceOlvido && modalRecuperar) {
    enlaceOlvido.addEventListener('click', (e) => {
      e.preventDefault();
      modalRecuperar.showModal();
    });
  }

  if (btnCerrarModal && modalRecuperar) {
    btnCerrarModal.addEventListener('click', () => {
      modalRecuperar.close();
    });
  }

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (form.getAttribute('aria-busy') === 'true') return;

    if (intentosFallidos >= 5) {
      showStatus(form, 'Has superado los 5 intentos fallidos permitidos. Tu acceso está temporalmente pausado por seguridad. Utiliza la opción de recuperación de contraseña.', 'error');
      return;
    }

    const username = usernameInput.value.trim();
    const password = passwordInput.value;
    const recordar = form.querySelector('input[name="recordar"]')?.checked ?? false;

    // "required" no detecta un campo con solo espacios.
    if (!username || !password) {
      showStatus(form, 'Ingresa tu correo o usuario y tu contraseña.', 'error');
      (username ? passwordInput : usernameInput).focus();
      return;
    }

    form.setAttribute('aria-busy', 'true');
    if (button) button.disabled = true;
    showStatus(form, 'Iniciando sesión...', 'info');
    try {
      const sesion = await iniciarSesion(form, { username, password, recordar });
      intentosFallidos = 0;
      passwordInput.value = '';
      const destino = sesion.destino || form.dataset.destination;
      if (destino) window.location.assign(destino);
    } catch (error) {
      intentosFallidos++;
      let mensajeError = error.message;
      if (intentosFallidos >= 5) {
        mensajeError = 'Has superado los 5 intentos fallidos. Por seguridad, contacta a soporte o solicita la recuperación de tu contraseña.';
      } else if (intentosFallidos >= 3) {
        mensajeError += ` (Intento fallido ${intentosFallidos} de 5).`;
      }
      showStatus(form, mensajeError, 'error');
      passwordInput.focus();
    } finally {
      form.removeAttribute('aria-busy');
      if (button) button.disabled = false;
    }
  });
}

function start() {
  initializePasswordToggles();
  initializeLoginForm();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', start, { once: true });
} else {
  start();
}
