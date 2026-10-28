(function () {
  'use strict';

  const form = document.getElementById('formulario-cuenta');

  // Muestra (o borra) el error de un campo
  function mostrarError(campo, mensaje) {
    const input = form.querySelector('[name="' + campo + '"]');
    const salida = form.querySelector('[data-error-for="' + campo + '"]');
    if (salida) salida.textContent = mensaje;
    if (input) {
      input.closest('.field')?.classList.toggle('field--error', mensaje !== '');
      if (mensaje) input.setAttribute('aria-invalid', 'true');
      else input.removeAttribute('aria-invalid');
    }
  }

  // Revisa nombre y tipo
  function validar() {
    const errores = {};
    const nombre = form.elements.nombre.value.trim();
    const tipo = form.elements.tipo;

    if (nombre === '') errores.nombre = 'El nombre de la cuenta es obligatorio.';
    else if (nombre.length > 100) errores.nombre = 'El nombre no puede superar 100 caracteres.';

    // Si el tipo está bloqueado, no se revisa
    if (!tipo.disabled && tipo.value === '') errores.tipo = 'Elige el tipo de cuenta: Débito o Crédito.';

    ['nombre', 'tipo'].forEach(function (campo) {
      mostrarError(campo, errores[campo] || '');
    });
    return errores;
  }

  if (form) {
    // Al enviar: si hay errores no se envía
    form.addEventListener('submit', function (evento) {
      const errores = validar();
      const primero = Object.keys(errores)[0];
      if (primero) {
        evento.preventDefault();
        form.elements[primero].focus();
        return;
      }
      // Cambia el botón a "Guardando…" para no enviar dos veces
      const boton = form.querySelector('button[type="submit"]');
      if (boton) {
        boton.dataset.textoOriginal = boton.textContent.trim(); 
        boton.setAttribute('aria-busy', 'true');
        boton.textContent = boton.dataset.textoCargando || 'Guardando…';
      }
    });

    // Quita el error cuando el usuario corrige el campo
    ['nombre', 'tipo'].forEach(function (campo) {
      const input = form.elements[campo];
      if (!input) return;
      input.addEventListener(campo === 'tipo' ? 'change' : 'input', function () {
        if (input.getAttribute('aria-invalid') === 'true') validar();
      });
    });

    // Pone el cursor en el formulario al editar
    if (form.dataset.enfocar === '1') {
      const objetivo = form.querySelector('[aria-invalid="true"]') || form.elements.nombre;
      objetivo.focus();
    }
  }
  
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('[aria-busy="true"]').forEach(function (boton) {
      boton.removeAttribute('aria-busy');
      if (boton.dataset.textoOriginal) boton.textContent = boton.dataset.textoOriginal;
    });
  });

  // Pide confirmación antes de eliminar
  document.querySelectorAll('.form-eliminar').forEach(function (formEliminar) {
    formEliminar.addEventListener('submit', function (evento) {
      const nombre = formEliminar.dataset.nombre || 'esta cuenta';
      const conMovimientos = formEliminar.dataset.movimientos === '1';
      const mensaje = conMovimientos
        ? '¿Eliminar la cuenta "' + nombre + '"?\n\nTiene movimientos registrados, por lo que se desactivará en lugar de eliminarse.'
        : '¿Eliminar la cuenta "' + nombre + '"?\n\nEsta acción no se puede deshacer.';
      if (!window.confirm(mensaje)) {
        evento.preventDefault(); // canceló
        return;
      }
      formEliminar.querySelector('button')?.setAttribute('aria-busy', 'true');
    });
  });
})();