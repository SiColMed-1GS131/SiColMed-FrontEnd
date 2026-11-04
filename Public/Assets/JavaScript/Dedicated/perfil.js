/* "Mi perfil": dirección provincia → distrito → corregimiento. Mismo patrón que registro.js,
   que no se puede reutilizar: su catálogo es solo del administrador y depende de otro formulario. */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-perfil');
  if (!form) return;
  const urlCatalogo = form.dataset.catalogo;
  const aviso = document.getElementById('perfil-aviso-direccion');

  function vaciarSelect(select) {
    if (!select) return;
    select.length = 1;   // deja solo la primera opción (el nombre del nivel)
    select.disabled = true;
    const siguiente = select.dataset.destino ? document.getElementById(select.dataset.destino) : null;
    vaciarSelect(siguiente);
  }

  form.querySelectorAll('select[data-cascada]').forEach((select) => {
    select.addEventListener('change', async () => {
      const destino = document.getElementById(select.dataset.destino);
      vaciarSelect(destino);
      if (!destino || !select.value || !urlCatalogo) return;
      try {
        const url = `${urlCatalogo}&recurso=${encodeURIComponent(select.dataset.cascada)}&id=${encodeURIComponent(select.value)}`;
        const respuesta = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const lista = respuesta.ok ? await respuesta.json() : [];
        lista.forEach((item) => destino.add(new Option(item.nombre, String(item.id))));
        destino.disabled = lista.length === 0;   // sin opciones (ej. Naso Tjër Di) sigue deshabilitado
      } catch (error) {
        // Sin catálogo el select queda vacío; al guardar se avisa que falta elegirlo
      }
    });
  });

  // Un select deshabilitado no lo revisa "required": por eso se comprueban los tres aquí
  form.addEventListener('submit', (evento) => {
    const faltante = Array.from(form.querySelectorAll('select[data-direccion]')).find((select) => !select.value);
    aviso.hidden = !faltante;
    if (!faltante) return;
    evento.preventDefault();
    aviso.textContent = `Selecciona ${faltante.dataset.direccion} de tu dirección residencial.`;
    aviso.scrollIntoView({ block: 'center' });
  });
});
