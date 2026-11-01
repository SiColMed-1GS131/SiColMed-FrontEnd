<?php
/**
 * App/Views/Partials/public_header.php · cabecera de las páginas PÚBLICAS (sin sidebar).
 *
 * Abre <html>, <head>, <body> y la barra superior (la misma de la portada).
 * Se cierra con public_footer.php.
 *
 * Variables que debe entregar el controlador:
 *   $base          ruta base de los assets ('' en Docker)
 *   $urls          ['inicio', 'login', 'registro']
 *   $tituloPagina  (opcional) título de la pestaña
 *   $extraCss      (opcional) CSS dedicado de la pantalla, en Assets/CSS/Dedicated/
 */
$pubE = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pubE($tituloPagina ?? 'SiColMed · Colegio Médico de Panamá') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= $pubE($base) ?>/Assets/IMG/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $pubE($base) ?>/Assets/CSS/base.css">
    <link rel="stylesheet" href="<?= $pubE($base) ?>/Assets/CSS/Dedicated/home.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?= $pubE($base) ?>/Assets/CSS/Dedicated/<?= $pubE($extraCss) ?>">
    <?php endif; ?>
</head>
<body class="home home--interior">
<a class="home__saltar" href="#contenido">Saltar al contenido</a>

<!-- ================= BARRA SUPERIOR ================= -->
<header class="topbar" id="topbar">
    <div class="topbar__inner">
        <a class="marca" href="<?= $pubE($urls['inicio']) ?>" aria-label="SiColMed, inicio">
            <img class="marca__logo" src="<?= $pubE($base) ?>/Assets/IMG/LOGO-CM.png" alt="" width="40" height="40">
            <span class="marca__nombre">SICOLMED</span>
        </a>

        <button class="topbar__menu" id="btnMenu" type="button" aria-expanded="false" aria-controls="navegacion" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>

        <nav class="nav" id="navegacion" aria-label="Principal">
            <ul class="nav__lista">
                <li><a class="nav__link" href="<?= $pubE($urls['inicio']) ?>">Inicio</a></li>
                <li><a class="nav__link nav__link--activo" href="#contenido" aria-current="page">Directorio médico</a></li>
            </ul>
            <div class="nav__acciones">
                <a class="boton boton--solido boton--pildora" href="<?= $pubE($urls['registro']) ?>">Regístrate</a>
                <a class="boton boton--borde boton--pildora" href="<?= $pubE($urls['login']) ?>">Iniciar sesión</a>
            </div>
        </nav>
    </div>
</header>
