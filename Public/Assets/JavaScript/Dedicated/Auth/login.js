import { initializePasswordToggles } from '../../Functions/Auth/passwordToggle.js';
// Comportamiento de la vista de inicio de sesión.
import { login } from '../../Functions/Auth/auth.js';
import { mockMode } from '../../Functions/Auth/mockAuth.js';
import { showStatus } from '../../Functions/Auth/formStatus.js';

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
      // PHP inicia sesión con la API y guarda el JWT solo en el servidor (DEF-008).
      const session = await login(username, password, rememberSession, form.dataset.sessionUrl);
      passwordInput.value = '';
      if (!mockMode) {
        const destination = session.destino || form.dataset.destination;
        if (typeof window.SICOLMED_AFTER_LOGIN === 'function') {
          window.SICOLMED_AFTER_LOGIN();
        } else if (destination) {
          window.location.assign(destination);
        }
        return;
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
