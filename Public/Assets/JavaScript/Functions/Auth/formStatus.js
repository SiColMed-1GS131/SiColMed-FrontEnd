// Mensajes de estado compartidos por los formularios de autenticación.
// `type` puede ser 'info' | 'success' | 'error' (true/false se aceptan por compatibilidad).
export function showStatus(form, message, type = 'info') {
  const kind = type === true ? 'error' : (type === false ? 'info' : type);
  let status = form.querySelector('[data-auth-status]');
  if (!status) {
    status = document.createElement('p');
    status.setAttribute('data-auth-status', '');
    form.insertBefore(status, form.firstChild);
  }
  status.className = `auth-status auth-status--${kind}`;
  status.setAttribute('role', kind === 'error' ? 'alert' : 'status');
  status.textContent = message;
}
