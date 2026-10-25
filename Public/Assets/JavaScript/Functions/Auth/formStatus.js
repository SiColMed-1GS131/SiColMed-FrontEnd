// Mensajes de estado compartidos por los formularios de autenticación.
export function showStatus(form, message, isError = false) {
  let status = form.querySelector('[data-auth-status]');
  if (!status) {
    status = document.createElement('p');
    status.setAttribute('data-auth-status', '');
    status.setAttribute('role', 'alert');
    form.insertBefore(status, form.firstChild);
  }
  status.textContent = message;
  status.style.color = isError ? '#b42318' : '#027a48';
}
