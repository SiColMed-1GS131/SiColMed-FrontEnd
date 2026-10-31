(function () {
  'use strict';

  var contenedor = document.querySelector('.reportes__filtro');
  var form = contenedor ? contenedor.querySelector('form') : null;
  var inicio = document.getElementById('inicio');
  var fin = document.getElementById('fin');
  var btnImprimir = document.getElementById('btn-imprimir');

  // Muestra el mensaje de error encima del formulario.
  function mostrarError(mensaje) {
    var caja = form.querySelector('[data-error-filtro]');
    if (!mensaje) {
      if (caja) { caja.remove(); }
      return;
    }
    if (!caja) {
      caja = document.createElement('p');
      caja.setAttribute('role', 'alert');
      caja.setAttribute('data-error-filtro', '');
      form.insertBefore(caja, form.firstChild);
    }
    caja.textContent = mensaje;
  }

  if (form && inicio && fin) {
    form.addEventListener('submit', function (evento) {
      var mensaje = '';

      if (!inicio.value || !fin.value) {
        mensaje = 'La fecha de inicio y la fecha fin son obligatorias.';
      } else if (inicio.value > fin.value) {
        // Las fechas AAAA-MM-DD se pueden comparar como texto.
        mensaje = 'La fecha de inicio no puede ser mayor que la fecha fin.';
      }

      mostrarError(mensaje);
      if (mensaje) {
        evento.preventDefault();
        (!inicio.value ? inicio : fin).focus();
      }
    });
  }

  if (btnImprimir) {
    btnImprimir.addEventListener('click', function () { window.print(); });
  }
})();