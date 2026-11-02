<?php
/**
 * App/Views/Partials/public_footer.php · pie de las páginas PÚBLICAS (sin sidebar).
 * Cierra lo que abrió public_header.php. Usa $base y $urls del controlador.
 */
$pubE = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!-- ================= PIE ================= -->
<footer class="pie" id="contacto">
    <div class="pie__inner">
        <div class="pie__marca">
            <a class="marca marca--clara" href="<?= $pubE($urls['inicio']) ?>">
                <img class="marca__logo" src="<?= $pubE($base) ?>/Assets/IMG/LOGO-CM.png" alt="" width="40" height="40">
                <span class="marca__nombre">SICOLMED</span>
            </a>
            <p>Sistema de Información para la Gestión del Colegio Médico de Panamá.</p>
        </div>

        <nav class="pie__col" aria-label="Enlaces del pie">
            <h3>Plataforma</h3>
            <ul>
                <li><a href="<?= $pubE($urls['inicio']) ?>">Inicio</a></li>
                <li><a href="<?= $pubE($urls['login']) ?>">Iniciar sesión</a></li>
                <li><a href="<?= $pubE($urls['registro']) ?>">Regístrate</a></li>
            </ul>
        </nav>

        <div class="pie__col">
            <h3>Contacto</h3>
            <!-- Reemplazar por los datos reales del Colegio (igual que en la portada) -->
            <ul class="pie__contacto">
                <li><span class="icono icono--ubicacion" aria-hidden="true"></span> Ciudad de Panamá, Panamá</li>
                <li><span class="icono icono--telefono" aria-hidden="true"></span> (+507) 000-0000</li>
                <li><span class="icono icono--mail" aria-hidden="true"></span> correo@ejemplo.com</li>
            </ul>
        </div>
    </div>
    <div class="pie__base">
        <p>© <?= date('Y') ?> SiColMed · Colegio Médico de Panamá. Todos los derechos reservados.</p>
        <p><a href="#">Política de privacidad</a> · <a href="#">Términos de servicio</a></p>
    </div>
</footer>

<script src="<?= $pubE($base) ?>/Assets/JavaScript/Dedicated/home.js" defer></script>
</body>
</html>
