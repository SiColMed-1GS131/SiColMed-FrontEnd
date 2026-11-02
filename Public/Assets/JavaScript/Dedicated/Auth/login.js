import { initializePasswordToggles } from '../../Functions/Auth/passwordToggle.js';
// Comportamiento de la vista de inicio de sesión.
import { login } from '../../Functions/Auth/auth.js';
import { mockMode } from '../../Functions/Auth/mockAuth.js';
import { showStatus } from '../../Functions/Auth/formStatus.js';
import { getToken, clearToken } from '../../Functions/Auth/token.js';

async function openPhpSession(form, token) {
  const sessionUrl = form.dataset.sessionUrl;
  if (!sessionUrl || !token) {
    throw new Error('No se pudo preparar la sesión. Inténtalo de nuevo.');
  }

  const response = await fetch(sessionUrl, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ token })
  });
  let body = null;
  try {
    body = await response.json();
  } catch (_error) {
    // El mensaje genérico de abajo evita mostrar respuestas no JSON al usuario.
  }
  if (!response.ok || !body || body.ok !== true) {
    throw new Error(body && typeof body.mensaje === 'string'
      ? body.mensaje
      : 'No se pudo abrir la sesión en el servidor.');
  }
  return body;
}

export function initializeLoginForm() {
  const form = document.getElementById('login-form');
  if (!form) return;

  const usernameInput = form.querySelector('#usuario');
  const passwordInput = form.querySelector('#password');
  const button = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (form.getAttribute('aria-busy') === 'true') return;

    const username = usernameInput.value.trim();
    const password = passwordInput.value;
    const rememberSession = form.querySelector('input[name="recordar"]')?.checked ?? false;

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
      await login(username, password, rememberSession);
      passwordInput.value = '';
      if (!mockMode) {
        try {
          const session = await openPhpSession(form, getToken());
          const destination = session.destino || form.dataset.destination;
          if (typeof window.SICOLMED_AFTER_LOGIN === 'function') {
            window.SICOLMED_AFTER_LOGIN();
          } else if (destination) {
            window.location.assign(destination);
          }
          return;
        } catch (sessionError) {
          clearToken();
          throw sessionError;
        }
      }
      showStatus(form, mockMode ? 'Sesión iniciada (modo de prueba).' : 'Sesión iniciada correctamente.', 'success');
      if (typeof window.SICOLMED_AFTER_LOGIN === 'function') window.SICOLMED_AFTER_LOGIN();
    } catch (error) {
      showStatus(form, error.message, 'error');
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
