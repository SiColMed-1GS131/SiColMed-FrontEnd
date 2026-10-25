document.addEventListener('DOMContentLoaded', () => {
  /*Pestañas*/
  const tabs = Array.from(document.querySelectorAll('.registro__tab'));
  const paneles = Array.from(document.querySelectorAll('.registro__tabpanel'));

  function activarTab(tab) {
    tabs.forEach((t) => {
      const activa = t === tab;
      t.classList.toggle('is-active', activa);
      t.setAttribute('aria-selected', activa ? 'true' : 'false');
      t.tabIndex = activa ? 0 : -1;
    });
    paneles.forEach((p) => {
      p.hidden = p.id !== tab.getAttribute('aria-controls');
    });
  }

  tabs.forEach((tab, i) => {
    tab.addEventListener('click', () => activarTab(tab));
    tab.addEventListener('keydown', (e) => {
      let destino = null;
      if (e.key === 'ArrowRight') destino = tabs[(i + 1) % tabs.length];
      if (e.key === 'ArrowLeft') destino = tabs[(i - 1 + tabs.length) % tabs.length];
      if (destino) { activarTab(destino); destino.focus(); }
    });
  });

  // Si un campo obligatorio falla en una pestaña oculta, abre esa pestaña
  const form = document.querySelector('.registro__form');
  if (form) {
    form.addEventListener('invalid', (e) => {
      const panel = e.target.closest('.registro__tabpanel');
      if (panel && panel.hidden) {
        const tab = tabs.find((t) => t.getAttribute('aria-controls') === panel.id);
        if (tab) activarTab(tab);
      }
    }, true);
  }

  /* Edad */
  const inputNacimiento = document.getElementById('fecha_nacimiento');
  const inputEdad = document.getElementById('edad');

  function calcularEdad() {
    if (!inputNacimiento || !inputEdad) return;
    if (!inputNacimiento.value) { inputEdad.value = ''; return; }
    const hoy = new Date();
    const nac = new Date(inputNacimiento.value + 'T00:00:00');
    let edad = hoy.getFullYear() - nac.getFullYear();
    const mes = hoy.getMonth() - nac.getMonth();
    if (mes < 0 || (mes === 0 && hoy.getDate() < nac.getDate())) edad--;
    inputEdad.value = edad >= 0 ? edad + ' años' : '';
  }
  if (inputNacimiento) {
    inputNacimiento.addEventListener('input', calcularEdad);
    calcularEdad();
  }

  /* Foto */
  const inputFoto = document.getElementById('foto');
  const imgFoto = document.getElementById('foto_preview');
  const textoSinFoto = document.getElementById('foto_vacio');
  const MAX_FOTO_MB = 2;
  let urlFoto = null;

  function mostrarFoto(url) {
    if (url) {
      imgFoto.src = url;
      imgFoto.hidden = false;
      if (textoSinFoto) textoSinFoto.hidden = true;
    } else {
      imgFoto.hidden = true;
      imgFoto.removeAttribute('src');
      if (textoSinFoto) textoSinFoto.hidden = false;
    }
  }

  if (inputFoto && imgFoto) {
    inputFoto.addEventListener('change', () => {
      const archivo = inputFoto.files[0];
      if (urlFoto) { URL.revokeObjectURL(urlFoto); urlFoto = null; }

      if (!archivo) { mostrarFoto(null); return; }

      if (!['image/jpeg', 'image/png'].includes(archivo.type)) {
        alert('La foto debe ser JPG o PNG.');
        inputFoto.value = '';
        mostrarFoto(null);
        return;
      }
      if (archivo.size > MAX_FOTO_MB * 1024 * 1024) {
        alert('La foto no debe superar ' + MAX_FOTO_MB + ' MB.');
        inputFoto.value = '';
        mostrarFoto(null);
        return;
      }
      urlFoto = URL.createObjectURL(archivo);
      mostrarFoto(urlFoto);
    });
  }

  /* Estudios */
  document.querySelectorAll('[data-filas]').forEach((contenedor) => {
    const grupo = contenedor.getAttribute('data-filas');
    const plantilla = document.getElementById('plantilla-' + grupo);
    const btnAgregar = document.querySelector('[data-agregar="' + grupo + '"]');
    if (!plantilla || !btnAgregar) return;

    let contador = contenedor.querySelectorAll('[data-fila]').length;

    btnAgregar.addEventListener('click', () => {
      const html = plantilla.innerHTML.replaceAll('__IDX__', String(contador++));
      contenedor.insertAdjacentHTML('beforeend', html);
    });

    contenedor.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-quitar]');
      if (btn) btn.closest('[data-fila]').remove();
    });
  });

  /* Certificado PDF */
  const inputPdf = document.getElementById('certificado_idoneidad');
  const textoPdf = document.getElementById('certificado_nombre');
  if (inputPdf && textoPdf) {
    const textoOriginal = textoPdf.textContent;
    const MAX_PDF_MB = 5;

    inputPdf.addEventListener('change', () => {
      const archivo = inputPdf.files[0];
      if (!archivo) { textoPdf.textContent = textoOriginal; return; }
      if (archivo.type !== 'application/pdf') {
        alert('El certificado debe ser un archivo PDF.');
        inputPdf.value = '';
        textoPdf.textContent = textoOriginal;
        return;
      }
      if (archivo.size > MAX_PDF_MB * 1024 * 1024) {
        alert('El PDF no debe superar ' + MAX_PDF_MB + ' MB.');
        inputPdf.value = '';
        textoPdf.textContent = textoOriginal;
        return;
      }
      textoPdf.textContent = archivo.name;
    });
  }
});