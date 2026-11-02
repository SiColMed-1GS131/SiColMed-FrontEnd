//   1) Protege la vista: si no hay un token vigente, manda al login.
//   2) Cierra sesión con cualquier elemento que tenga el atributo data-logout.
//
// Esto solo mejora la experiencia (la autorización real la hace el backend con el JWT).
import { isAuthenticated, clearToken } from '../Functions/Auth/token.js';

const body = document.body;
const loginUrl = body.dataset.loginUrl || '/index.php?c=login&a=login';

function irAlLogin() {
  window.location.replace(loginUrl);
}

// 1) Proteger la vista. header.php pone data-auth-required="1" cuando NO está el modo de prueba.
if (body.dataset.authRequired === '1') {
  if (isAuthenticated()) {
    body.classList.add('auth-ok');   // el CSS del header oculta la página hasta este punto
  } else {
    irAlLogin();
  }
}

// 2) Cerrar sesión
document.addEventListener('click', async event => {
  const boton = event.target.closest('[data-logout]');
  if (!boton) return;
  event.preventDefault();
  clearToken();
  try {
    // Cierra también la sesión de PHP (rol y token guardados en el servidor)
    await fetch('/index.php?c=sesion&a=cerrar', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: '{}'
    });
  } catch (_e) { /* si falla, igual se sale al login */ }
  irAlLogin();
});