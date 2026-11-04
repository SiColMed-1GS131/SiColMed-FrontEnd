document.addEventListener('DOMContentLoaded', () => {
  /* Carga masiva por CSV: el archivo se envía completo; la API lo valida, registra
     cada fila y deja la carga en auditoría (POST /medicos/importar). */
  const csvInput = document.getElementById('csv-medicos');
  const csvEstado = document.getElementById('csv-medicos-estado');
  const csvEndpoint = window.SICOLMED_CSV_ENDPOINT;
  const loginUrl = document.body.dataset.loginUrl || '/index.php?c=login&a=login';

  function csvMensaje(texto, esError, detalles = []) {
    if (!csvEstado) return;
    csvEstado.hidden = false;
    csvEstado.classList.toggle('is-error', esError);
    csvEstado.textContent = texto;
    if (detalles.length > 0) {
      const lista = document.createElement('ul');
      detalles.forEach((d) => {
        const item = document.createElement('li');
        item.textContent = d;
        lista.appendChild(item);
      });
      csvEstado.appendChild(lista);
    }
  }

  async function importarCsv(archivo) {
    if (!csvEndpoint) throw new Error('No se configuró el endpoint de importación.');
    const datos = new FormData();
    datos.append('archivo', archivo);

    const respuesta = await fetch(csvEndpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
      body: datos
    });

    // Solo un 401 (token vencido o inválido) lleva al login. 400, 422 o 500 se muestran
    // en pantalla y la sesión sigue abierta.
    if (respuesta.status === 401) {
      window.location.replace(loginUrl);
      return;
    }
    const resultado = await respuesta.json().catch(() => null);
    if (!respuesta.ok || !resultado?.ok) {
      csvMensaje(resultado?.error || `No se pudo procesar el archivo (error ${respuesta.status}).`, true);
      return;
    }

    const resumen = `Archivo ${resultado.archivo}: ${resultado.registrados} de ${resultado.procesados} médicos registrados.`;
    if (resultado.errores.length > 0) {
      csvMensaje(`${resumen} Filas con error:`, true,
        resultado.errores.map((e) => (e.fila > 0 ? `Línea ${e.fila}: ${e.error}` : e.error)));
      return;
    }
    csvMensaje(resumen, false);
    window.location.assign(window.location.href);   // recarga la lista con los médicos nuevos
  }

  if (csvInput) {
    csvInput.addEventListener('change', async () => {
      const archivo = csvInput.files[0];
      csvInput.value = '';
      if (!archivo) return;
      if (!/\.csv$/i.test(archivo.name)) {
        csvMensaje('Selecciona un archivo con extensión .csv.', true);
        return;
      }
      csvMensaje('Subiendo y registrando el archivo CSV...', false);
      try {
        await importarCsv(archivo);
      } catch (error) {
        csvMensaje(error.message || 'No se pudo enviar el archivo.', true);
      }
    });
  }

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

  /* Dirección: provincia → distrito → corregimiento (IDs del catálogo de la API) */
  const urlCatalogo = form ? form.getAttribute('action').replace(/a=guardar\b/, 'a=catalogo') : null;

  function vaciarSelect(select) {
    if (!select) return;
    select.length = 1;   // deja solo "Seleccione"
    select.disabled = true;
    const siguiente = select.dataset.destino ? document.getElementById(select.dataset.destino) : null;
    vaciarSelect(siguiente);
  }

  document.querySelectorAll('select[data-cascada]').forEach((select) => {
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
        // Sin catálogo el select queda vacío; el backend avisará que falta el corregimiento
      }
    });
  });

  /* Especialidades → subespecialidades: misma cascada que la dirección, pero con select
     múltiple. Se conserva lo ya elegido que siga disponible, y cada clic pide la lista de
     nuevo: solo se aplica la respuesta más reciente. */
  document.querySelectorAll('select[data-cascada-multiple]').forEach((select) => {
    const destino = document.getElementById(select.dataset.destino);
    if (!destino) return;
    let turno = 0;

    function llenarDestino(lista) {
      const elegidos = new Set(Array.from(destino.selectedOptions, (opcion) => opcion.value));
      destino.length = 0;
      lista.forEach((item) => {
        const id = String(item.id);
        destino.add(new Option(item.nombre, id, false, elegidos.has(id)));
      });
      destino.size = Math.max(3, Math.min(8, lista.length));
      destino.disabled = lista.length === 0;
    }

    select.addEventListener('change', async () => {
      const miTurno = ++turno;
      const ids = Array.from(select.selectedOptions, (opcion) => opcion.value);
      if (ids.length === 0) {
        llenarDestino([]);
        return;
      }
      if (!urlCatalogo) return;
      try {
        const url = `${urlCatalogo}&recurso=${encodeURIComponent(select.dataset.cascadaMultiple)}&id=${encodeURIComponent(ids.join(','))}`;
        const respuesta = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        if (!respuesta.ok) return;
        const lista = await respuesta.json();
        if (miTurno === turno) llenarDestino(lista);
      } catch (error) {
        // Si falla se deja la lista como estaba; el backend valida lo que se envíe
      }
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