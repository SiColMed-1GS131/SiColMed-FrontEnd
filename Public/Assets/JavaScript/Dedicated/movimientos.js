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
        
        // 0. Registro de partida doble: solo fecha y descripción aquí; las líneas se validan en validarDetalle()
        if (form.id === 'form-movimiento') {
            return { fecha: reglaFecha, descripcion: reglaDescripcion };
        }

        // 1. Formulario de registro simple (ya no se usa en crear)
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

    /* ==========================================================================
       2b. PARTIDA DOBLE (detalle[] del formulario de registro)
       ========================================================================== */

    function centavos(texto) {
        var n = Number(texto);
        return isNaN(n) ? 0 : Math.round(n * 100);
    }

    function dinero(c) {
        return (c / 100).toFixed(2);
    }

    // Suma débitos y créditos, los muestra y devuelve el estado
    function actualizarCuadre(form) {
        var tot = { DEBITO: 0, CREDITO: 0 };
        var lineas = form.querySelectorAll('[data-linea]');
        Array.prototype.forEach.call(lineas, function (fila) {
            var tipo = fila.querySelector('select[name$="[tipo]"]').value;
            var monto = fila.querySelector('input[name$="[monto]"]').value;
            if (tot[tipo] !== undefined) { tot[tipo] += centavos(monto); }
        });
        form.querySelector('[data-total="DEBITO"]').textContent = dinero(tot.DEBITO);
        form.querySelector('[data-total="CREDITO"]').textContent = dinero(tot.CREDITO);
        var estado = form.querySelector('[data-cuadre]');
        var cuadra = tot.DEBITO > 0 && tot.DEBITO === tot.CREDITO;
        estado.textContent = cuadra ? 'El movimiento cuadra.' : 'Aún no cuadra: la diferencia es ' + dinero(Math.abs(tot.DEBITO - tot.CREDITO)) + '.';
        return { debito: tot.DEBITO, credito: tot.CREDITO, cuadra: cuadra, lineas: lineas.length };
    }

    function validarDetalle(form) {
        var estado = actualizarCuadre(form);
        var mensaje = '';
        var tipos = Array.prototype.map.call(form.querySelectorAll('[data-linea] select[name$="[tipo]"]'), function (s) { return s.value; });

        if (estado.lineas < 2 || tipos.indexOf('DEBITO') === -1 || tipos.indexOf('CREDITO') === -1) {
            mensaje = 'Debe haber al menos una línea de débito y una de crédito.';
        } else {
            var vacio = null;
            Array.prototype.forEach.call(form.querySelectorAll('[data-linea] select[name$="[id_cuenta]"], [data-linea] input[name$="[monto]"]'), function (el) {
                if (!vacio && (!el.value || (el.type === 'number' && Number(el.value) <= 0))) { vacio = el; }
            });
            if (vacio) {
                mensaje = 'Completa la cuenta y un monto mayor que cero en cada línea.';
                vacio.focus();
            } else if (!estado.cuadra) {
                mensaje = 'El movimiento no cuadra: los débitos (' + dinero(estado.debito) + ') deben ser iguales a los créditos (' + dinero(estado.credito) + ').';
            }
        }
        var caja = form.querySelector('[data-error-for="detalle"]');
        if (caja) { caja.textContent = mensaje; }
        return mensaje === '';
    }

    function iniciarDetalle(form) {
        var contenedor = form.querySelector('#detalle-lineas');
        var plantilla = form.querySelector('#detalle-plantilla');
        var agregar = form.querySelector('#detalle-agregar');
        if (!contenedor || !plantilla || !agregar) { return; }

        agregar.addEventListener('click', function () {
            var siguiente = Number(contenedor.getAttribute('data-siguiente') || 0);
            contenedor.setAttribute('data-siguiente', String(siguiente + 1));
            contenedor.insertAdjacentHTML('beforeend', plantilla.innerHTML.replace(/__IDX__/g, String(siguiente)));
            actualizarCuadre(form);
        });
        contenedor.addEventListener('click', function (ev) {
            var btn = ev.target.closest('[data-quitar-linea]');
            if (btn && contenedor.querySelectorAll('[data-linea]').length > 2) {
                btn.closest('[data-linea]').remove();
                actualizarCuadre(form);
            }
        });
        contenedor.addEventListener('input', function () { actualizarCuadre(form); });
        contenedor.addEventListener('change', function () { actualizarCuadre(form); });
        actualizarCuadre(form);
    }

    /**
     * Conecta los eventos de validación a un formulario.
     */
    function iniciar(form) {
        var reglas = reglasDe(form);
        if (!reglas) { return; }
        
        var esRegistro = form.id === 'form-movimiento' || !!(form.elements.monto && form.elements.descripcion);
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

            // Partida doble: líneas completas y débitos = créditos
            if (form.id === 'form-movimiento' && !validarDetalle(form)) {
                ev.preventDefault();
                return;
            }

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
        Array.prototype.forEach.call(document.querySelectorAll('form'), function (form) {
            iniciar(form);
            if (form.id === 'form-movimiento') { iniciarDetalle(form); }
        });
    });
})();