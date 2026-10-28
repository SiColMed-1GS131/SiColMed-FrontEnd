/* Reporte Balance General (P5): validación del filtro e impresión. */
(function () {
  'use strict';

  var form = document.getElementById('form-balance');
  var inicio = document.getElementById('inicio');
  var fin = document.getElementById('fin');
  var btnImprimir = document.getElementById('btn-imprimir');

  function mostrarError(campo, mensaje) {
    var caja = document.getElementById('error-' + campo.id);
    if (caja) { caja.textContent = mensaje; }
    if (mensaje) {
      campo.setAttribute('aria-invalid', 'true');
    } else {
      campo.removeAttribute('aria-invalid');
    }
  }

  if (form && inicio && fin) {
    form.addEventListener('submit', function (evento) {
      var hayError = false;

      mostrarError(inicio, '');
      mostrarError(fin, '');

      if (!inicio.value) {
        mostrarError(inicio, 'La fecha de inicio es obligatoria.');
        hayError = true;
      }
      if (!fin.value) {
        mostrarError(fin, 'La fecha fin es obligatoria.');
        hayError = true;
      }
      // Las fechas AAAA-MM-DD se pueden comparar como texto.
      if (!hayError && inicio.value > fin.value) {
        mostrarError(fin, 'La fecha de inicio no puede ser mayor que la fecha fin.');
        hayError = true;
      }

      if (hayError) {
        evento.preventDefault();
        (!inicio.value || inicio.getAttribute('aria-invalid') ? inicio : fin).focus();
      }
    });
  }

  if (btnImprimir) {
    btnImprimir.addEventListener('click', function () { window.print(); });
  }
})();