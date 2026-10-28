import { initializePasswordToggles } from '../../Functions/Auth/passwordToggle.js';
// Comportamiento de la vista de inicio de sesión.
import { login } from '../../Functions/Auth/auth.js';
import { mockMode } from '../../Functions/Auth/mockAuth.js';
import { showStatus } from '../../Functions/Auth/formStatus.js';

export function initializeLoginForm() {
  const form = document.getElementById('login-form');
  if (!form) return;

  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    if (button) button.disabled = true;
    showStatus(form, 'Iniciando sesión...');
    try {
      const username = form.querySelector('#usuario').value.trim();
      const password = form.querySelector('#password').value;
      const rememberSession = form.querySelector('input[name="recordar"]')?.checked ?? false;
      await login(username, password, rememberSession);
      showStatus(form, mockMode ? 'Sesión iniciada (modo de prueba).' : 'Sesión iniciada correctamente.');
      if (typeof window.SICOLMED_AFTER_LOGIN === 'function') window.SICOLMED_AFTER_LOGIN();
    } catch (error) {
      showStatus(form, error.message, true);
    } finally {
      if (button) button.disabled = false;
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeLoginForm, { once: true });
} else {
  initializeLoginForm();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initializePasswordToggles(), { once: true });
} else {
  initializePasswordToggles();
}
