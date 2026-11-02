<?php
// Protección de la vista por sesión (JWT): solo cuando NO está el modo de prueba (USE_MOCK).
// Con USE_MOCK=1 las pantallas se pueden abrir sin login para que el equipo las pruebe.
require_once __DIR__ . '/../../Middleware/Auth.php';
$hdrExigeSesion = !Auth::modoPrueba();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiColMed</title>

    <!-- 1. CSS base (cuadrícula, colores, formularios; importa sidebar.css) -->
    <link rel="stylesheet" href="/Assets/CSS/base.css">

    <!-- 2. CSS dedicado de la pantalla activa -->
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="/Assets/CSS/Dedicated/<?= htmlspecialchars($extraCss, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <?php if ($hdrExigeSesion): ?>
    <!-- La página se muestra solo cuando sesion.js confirma que hay un token vigente -->
    <style>body[data-auth-required="1"]:not(.auth-ok) { visibility: hidden; }</style>
    <?php endif; ?>

    <!-- 3. Sesión: protege la vista y cierra sesión (usa token.js) -->
    <script type="module" src="/Assets/JavaScript/Dedicated/sesion.js"></script>
</head>
<body data-login-url="/index.php?c=login&amp;a=login" data-auth-required="<?= $hdrExigeSesion ? '1' : '0' ?>">
