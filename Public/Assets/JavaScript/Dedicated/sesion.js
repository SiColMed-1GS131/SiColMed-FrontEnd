//   1) Protege la vista: si no hay un token vigente, manda al login.
//   2) Cierra sesión con cualquier elemento que tenga el atributo data-logout.
//
// Esto solo mejora la experiencia (la autorización real la hace el backend con el JWT).
import { clearToken } from '../Functions/Auth/token.js';

const body = document.body;
const loginUrl = body.dataset.loginUrl || '/index.php?c=login&a=login';

function irAlLogin() {
  window.location.replace(loginUrl);
}

// DEF-008: el JWT vive solo en la sesión de PHP. Se borra cualquier token que una
// versión anterior haya dejado en sessionStorage/localStorage.
clearToken();

// 1) Proteger la vista. header.php pone data-auth-required="1" cuando NO está el modo de
//    prueba y la sesión de PHP no tiene un usuario: entonces se manda al login.
if (body.dataset.authRequired === '1') {
  irAlLogin();
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