// Cierra sesión con cualquier elemento que tenga el atributo data-logout.
// La protección de las pantallas la hace el servidor (Auth::exigirSesion en header.php):
// aquí ya no se guarda ni se lee ningún token en el navegador.
const loginUrl = document.body.dataset.loginUrl || '/index.php?c=login&a=login';

document.addEventListener('click', async event => {
  const boton = event.target.closest('[data-logout]');
  if (!boton) return;
  event.preventDefault();
  try {
    // Cierra la sesión de PHP (rol y token guardados en el servidor)
    await fetch('/index.php?c=sesion&a=cerrar', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: '{}'
    });
  } catch (_e) { /* si falla, igual se sale al login */ }
  window.location.replace(loginUrl);
});
