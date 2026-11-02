<?php
// Protección de la vista: sin sesión de PHP se manda al login (antes de imprimir nada).
// Con USE_MOCK=1 (modo de prueba) las pantallas se pueden abrir sin login para que el equipo las pruebe.
require_once __DIR__ . '/../../Middleware/Auth.php';
Auth::exigirSesion();

// Rutas base para que funcione tanto en Docker (raíz) como en WAMP/XAMPP (subcarpeta)
$base    = $base ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$entrada = $entrada ?? ($base . '/index.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiColMed</title>

    <!-- 1. CSS base (cuadrícula, colores, formularios; importa sidebar.css) -->
    <link rel="stylesheet" href="<?= htmlspecialchars($base . '/Assets/CSS/base.css', ENT_QUOTES, 'UTF-8') ?>">

    <!-- 2. CSS dedicado de la pantalla activa -->
    <?php if (isset($extraCss) && $extraCss !== ''): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($base . '/Assets/CSS/Dedicated/' . $extraCss, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <!-- 3. Sesión: cierra sesión con data-logout (la protección de la vista la hace el servidor) -->
    <script type="module" src="<?= htmlspecialchars($base . '/Assets/JavaScript/Dedicated/sesion.js', ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body data-login-url="<?= htmlspecialchars($entrada . '?c=login&a=login', ENT_QUOTES, 'UTF-8') ?>">
