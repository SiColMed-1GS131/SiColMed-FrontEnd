/*
 * movimientos.js · Módulo 5 · P2 Pablo
 * Validación en el navegador (la validación real y final ocurre en el backend).
 * 
 * Reglas aplicadas:
 *   - Registrar movimiento: fecha, descripción y cuenta obligatorias; monto > 0 (máx 2 decimales); tipo seleccionado.
 *   - Filtro fecha del día: fecha obligatoria.
 *   - Filtro rango de cuenta: inicio y fin obligatorios; inicio <= fin.
 * 
 * NOTA TÉCNICA: El script detecta de qué formulario se trata leyendo los atributos 'name'
 * de sus inputs. Esto permite que funcione dinámicamente sin depender de clases o IDs fijos.
 */
(function () {
    'use strict';

    // Constantes de validación
    var MONTO_MAXIMO = 99999999.99;
    var RE_MONTO = /^\d+(\.\d{1,2})?$/;

    /* ==========================================================================
       1. UTILIDADES DEL DOM
       ========================================================================== */

    /**
     * Busca (o crea) el elemento <p> donde se mostrará el mensaje de error.
     */
    function contenedorError(form, nombre) {
        var caja = form.querySelector('[data-error-for="' + nombre + '"]');
        if (caja) { return caja; }

        var campo = form.elements[nombre];
        var primero = campo && campo.length && !campo.tagName ? campo[0] : campo;
        if (!primero) { return null; }
        
        var padre = primero.closest('.field') || primero.parentNode;
        caja = document.createElement('p');
        caja.className = 'field__error';
        caja.setAttribute('data-error-for', nombre);
        caja.setAttribute('role', 'alert');
        padre.appendChild(caja);
        
        return caja;
    }

    /**
     * Aplica el estado visual de error al campo y muestra el texto.
     */
    function marcar(form, nombre, mensaje) {
        var caja = contenedorError(form, nombre);
        if (caja) { caja.textContent = mensaje || ''; }

        var campo = form.elements[nombre];
        var lista = campo && !campo.tagName ? Array.prototype.slice.call(campo) : [campo];
        
        lista.forEach(function (el) {
            if (!el) { return; }
            if (mensaje) { 
                el.setAttribute('aria-invalid', 'true'); 
            } else { 
                el.removeAttribute('aria-invalid'); 
            }
        });

        var envoltura = (lista[0] && lista[0].closest) ? lista[0].closest('.field') : null;
        if (envoltura) { 
            envoltura.classList.toggle('field--error', !!mensaje); 
        }
    }

    /**
     * Devuelve el nodo DOM del campo solicitado (útil para grupos de radio buttons).
     */
    function primerCampo(form, nombre) {
        var campo = form.elements[nombre];
        return campo && !campo.tagName ? campo[0] : campo;
    }

    /* ==========================================================================
       2. REGLAS DE VALIDACIÓN POR CAMPO
       Devuelven '' si la validación es correcta, o un string con el error.
       ========================================================================== */

    function reglaFecha(form) {
        return form.elements.fecha.value ? '' : 'La fecha es obligatoria.';
    }

    function reglaDescripcion(form) {
        return form.elements.descripcion.value.trim() ? '' : 'La descripción es obligatoria.';
    }

    function reglaCuenta(form) {
        return form.elements.id_cuenta.value ? '' : 'Selecciona una cuenta.';
    }

    function reglaTipo(form) {
        return form.querySelector('input[name="tipo"]:checked') ? '' : 'Elige Débito o Crédito.';
    }

    function reglaMonto(form) {
        var el = form.elements.monto;
        var v = el.value.trim();

        if (el.validity && el.validity.badInput) { 
            return 'Escribe un número válido, por ejemplo 1250.75.'; 
        }
        if (v === '') { 
            return 'El monto es obligatorio.'; 
        }

        var n = Number(v);
        if (isNaN(n)) { return 'Escribe un número válido, por ejemplo 1250.75.'; }
        if (n <= 0) { return 'El monto debe ser mayor que cero.'; }

        var partes = v.split('.');
        if (partes[1] && partes[1].length > 2) { 
            return 'El monto admite como máximo 2 decimales.'; 
        }
        if (!RE_MONTO.test(v)) { 
            return 'Escribe el monto solo con números y punto decimal, por ejemplo 1250.75.'; 
        }
        if (n > MONTO_MAXIMO) { 
            return 'El monto máximo es 99,999,999.99.'; 
        }
        
        return '';
    }

    function reglaInicio(form) {
        return form.elements.inicio.value ? '' : 'La fecha de inicio es obligatoria.';
    }

    function reglaFin(form) {
        var ini = form.elements.inicio.value;
        var fin = form.elements.fin.value;
        
        if (!fin) { return 'La fecha fin es obligatoria.'; }
        if (ini && fin && ini > fin) { 
            return 'La fecha de inicio no puede ser mayor que la fecha fin.'; 
        }
        return '';
    }

    function reglaCuentaHistorial(form) {
        return form.elements.id.value ? '' : 'Elige una cuenta.';
    }

    /* ==========================================================================
       3. IDENTIFICADOR DE FORMULARIOS Y BINDING
       ========================================================================== */

    /**
     * Evalúa los 'name' del formulario para saber qué bloque de validación usar.
     */
    function reglasDe(form) {
        var e = form.elements;
        
        // 1. Formulario de registro (crear)
        if (e.monto && e.descripcion) {
            return { 
                fecha: reglaFecha, 
                descripcion: reglaDescripcion, 
                id_cuenta: reglaCuenta, 
                monto: reglaMonto, 
                tipo: reglaTipo 
            };
        }
        
        // 2. Formulario de historial por rango
        if (e.inicio && e.fin) {
            var reglas = { inicio: reglaInicio, fin: reglaFin };
            if (e.id) { reglas.id = reglaCuentaHistorial; }
            return reglas;
        }
        
        // 3. Formulario de movimientos del día
        if (e.fecha) {
            return { fecha: reglaFecha };
        }
        
        return null;
    }

    /**
     * Conecta los eventos de validación a un formulario.
     */
    function iniciar(form) {
        var reglas = reglasDe(form);
        if (!reglas) { return; }
        
        var esRegistro = !!(form.elements.monto && form.elements.descripcion);
        var intentado = false; // Bandera para no gritarle al usuario antes de que intente enviar

        function validarCampo(nombre) {
            var mensaje = reglas[nombre](form);
            marcar(form, nombre, mensaje);
            
            // Si cambió la fecha de inicio, debemos reevaluar la fecha fin obligatoriamente
            if (nombre === 'inicio' && reglas.fin && form.elements.fin.value) { 
                marcar(form, 'fin', reglas.fin(form)); 
            }
            return mensaje;
        }

        // Validación "en vivo" pero solo DESPUÉS de un primer intento de envío
        Object.keys(reglas).forEach(function (nombre) {
            var campo = form.elements[nombre];
            var lista = campo && !campo.tagName ? Array.prototype.slice.call(campo) : [campo];
            
            lista.forEach(function (el) {
                if (!el) { return; }
                function revisar() {
                    if (intentado || el.getAttribute('aria-invalid') === 'true') { 
                        validarCampo(nombre); 
                    }
                }
                el.addEventListener('change', revisar);
                el.addEventListener('input', revisar);
            });
        });

        // Validación global al enviar el formulario
        form.addEventListener('submit', function (ev) {
            intentado = true;
            var primeroConError = null;
            
            Object.keys(reglas).forEach(function (nombre) {
                if (validarCampo(nombre) && !primeroConError) { 
                    primeroConError = nombre; 
                }
            });

            // Si hay error, detenemos el envío y enfocamos el primer campo mal llenado
            if (primeroConError) {
                ev.preventDefault();
                var el = primerCampo(form, primeroConError);
                if (el && el.focus) { el.focus(); }
                return;
            }

            // Prevención de múltiple envío al registrar
            if (esRegistro) {
                var boton = form.querySelector('button[type="submit"]');
                if (boton) { 
                    setTimeout(function () { 
                        boton.disabled = true; 
                        boton.textContent = 'Registrando…'; 
                    }, 0); 
                }
            }
        });
    }

    // Inicializar todos los formularios al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(document.querySelectorAll('form'), iniciar);
    });
})();