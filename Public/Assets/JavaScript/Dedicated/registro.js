document.addEventListener('DOMContentLoaded', () => {
  const csvInput = document.getElementById('csv-medicos');
  const csvEstado = document.getElementById('csv-medicos-estado');
  const csvEndpoint = window.SICOLMED_CSV_ENDPOINT;

  function csvError(message) {
    if (!csvEstado) return;
    csvEstado.hidden = false;
    csvEstado.classList.add('is-error');
    csvEstado.textContent = message;
  }

  function parseCsv(text) {
    const filas = [];
    let fila = [];
    let campo = '';
    let entreComillas = false;

    for (let i = 0; i < text.length; i++) {
      const caracter = text[i];
      const siguiente = text[i + 1];
      if (caracter === '"' && entreComillas && siguiente === '"') {
        campo += '"';
        i++;
      } else if (caracter === '"') {
        entreComillas = !entreComillas;
      } else if (caracter === ',' && !entreComillas) {
        fila.push(campo);
        campo = '';
      } else if ((caracter === '\n' || caracter === '\r') && !entreComillas) {
        if (caracter === '\r' && siguiente === '\n') i++;
        fila.push(campo);
        if (fila.some((valor) => valor.trim() !== '')) filas.push(fila);
        fila = [];
        campo = '';
      } else {
        campo += caracter;
      }
    }
    if (campo !== '' || fila.length > 0) {
      fila.push(campo);
      if (fila.some((valor) => valor.trim() !== '')) filas.push(fila);
    }
    return filas;
  }

  function convertirFilas(filas) {
    if (filas.length < 2) throw new Error('El CSV debe incluir encabezados y al menos un médico.');
    const encabezados = filas[0].map((valor) => valor.replace(/^\uFEFF/, '').trim());
    if (encabezados.some((valor) => valor === '')) throw new Error('El CSV contiene un encabezado vacío.');
    return filas.slice(1).map((fila, indice) => {
      const datos = {};
      encabezados.forEach((encabezado, columna) => {
        const valor = (fila[columna] ?? '').trim();
        const partes = encabezado.replace(/\]/g, '').split('[');
        let destino = datos;
        partes.forEach((parte, posicion) => {
          if (posicion === partes.length - 1) {
            destino[parte] = valor;
            return;
          }
          if (!destino[parte] || typeof destino[parte] !== 'object') destino[parte] = {};
          destino = destino[parte];
        });
      });
      if (!datos.nombre || !datos.apellido_paterno || !datos.cedula ||
          !datos.registro_medico || !datos.fecha_nacimiento) {
        throw new Error(`La fila ${indice + 2} no contiene todos los campos obligatorios.`);
      }
      return datos;
    });
  }

  async function importarCsv(archivo) {
    if (!csvEndpoint) throw new Error('No se configuró el endpoint de importación.');
    const contenido = await archivo.text();
    const medicos = convertirFilas(parseCsv(contenido));
    let registrados = 0;
    const errores = [];

    for (let i = 0; i < medicos.length; i++) {
      if (csvEstado) csvEstado.textContent = `Registrando médico ${i + 1} de ${medicos.length}...`;
      const respuesta = await fetch(csvEndpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(medicos[i])
      });
      const resultado = await respuesta.json().catch(() => null);
      if (respuesta.ok && resultado?.ok) {
        registrados++;
      } else {
        errores.push(`Fila ${i + 2}: ${resultado?.error || 'No se pudo registrar el médico.'}`);
      }
    }

    if (errores.length > 0) {
      csvError(`Se registraron ${registrados} de ${medicos.length}. ${errores.join(' ')}`);
      return;
    }
    window.location.assign(window.location.href);
  }

  if (csvInput) {
    csvInput.addEventListener('change', async () => {
      const archivo = csvInput.files[0];
      csvInput.value = '';
      if (!archivo) return;
      if (!/\.csv$/i.test(archivo.name)) {
        csvError('Selecciona un archivo con extensión .csv.');
        return;
      }
      if (csvEstado) {
        csvEstado.hidden = false;
        csvEstado.classList.remove('is-error');
        csvEstado.textContent = 'Leyendo archivo CSV...';
      }
      try {
        await importarCsv(archivo);
      } catch (error) {
        csvError(error.message);
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
      } catch (error) {
        // Sin catálogo el select queda vacío; el backend avisará que falta el corregimiento
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