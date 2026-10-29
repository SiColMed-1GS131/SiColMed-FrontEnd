// Página de inicio: menú móvil, sombra de la barra, animación de entrada y contadores.
(function () {
    'use strict';

    var reducir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.documentElement.classList.add('js');

    // ---- Menú móvil ----
    var boton = document.getElementById('btnMenu');
    var nav = document.getElementById('navegacion');
    if (boton && nav) {
        function cerrar() {
            nav.classList.remove('is-open');
            boton.setAttribute('aria-expanded', 'false');
            boton.setAttribute('aria-label', 'Abrir menú');
        }
        boton.addEventListener('click', function () {
            var abierto = nav.classList.toggle('is-open');
            boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            boton.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
        });
        nav.addEventListener('click', function (e) {
            if (e.target.closest('a')) { cerrar(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { cerrar(); }
        });
    }

    // ---- Sombra de la barra al hacer scroll ----
    var barra = document.getElementById('topbar');
    function marcarScroll() {
        if (barra) { barra.classList.toggle('is-scrolled', window.scrollY > 8); }
    }
    marcarScroll();
    window.addEventListener('scroll', marcarScroll, { passive: true });

    // ---- Animación de entrada ----
    var animables = document.querySelectorAll(
        '.tarjeta, .servicio, .rol, .noticia, .seccion__cabecera, .llamado__inner, .cifra'
    );
    if ('IntersectionObserver' in window && !reducir) {
        var vigila = new IntersectionObserver(function (items) {
            items.forEach(function (item) {
                if (item.isIntersecting) {
                    item.target.classList.add('is-visible');
                    vigila.unobserve(item.target);
                }
            });
        }, { threshold: 0.12 });
        animables.forEach(function (el) {
            el.classList.add('reveal');
            vigila.observe(el);
        });
    }

    // ---- Contadores de las cifras ----
    var numeros = document.querySelectorAll('[data-contar]');
    function contar(el) {
        var meta = parseInt(el.getAttribute('data-contar'), 10);
        if (isNaN(meta)) { return; }
        var inicio = null, duracion = 1400;
        function paso(t) {
            if (inicio === null) { inicio = t; }
            var p = Math.min((t - inicio) / duracion, 1);
            var ease = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(meta * ease).toLocaleString('es-PA');
            if (p < 1) { window.requestAnimationFrame(paso); }
        }
        window.requestAnimationFrame(paso);
    }
    if ('IntersectionObserver' in window && !reducir) {
        var vigilaNum = new IntersectionObserver(function (items) {
            items.forEach(function (item) {
                if (item.isIntersecting) {
                    contar(item.target);
                    vigilaNum.unobserve(item.target);
                }
            });
        }, { threshold: 0.6 });
        numeros.forEach(function (n) { vigilaNum.observe(n); });
    }
})();
