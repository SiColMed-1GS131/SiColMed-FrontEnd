<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiColMed</title>
    
    <!-- 1. Cargamos el CSS base (El que tiene la cuadrícula y colores generales) -->
    <link rel="stylesheet" href="/SiColMed-FrontEnd/Public/Assets/CSS/base.css">
    
    <!-- 2. Cargamos el CSS dedicado de la pantalla activa -->
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="/SiColMed-FrontEnd/Public/Assets/CSS/Dedicated/<?= $extraCss ?>">
    <?php endif; ?>
</head>
<body>