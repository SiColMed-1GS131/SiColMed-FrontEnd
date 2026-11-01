<?php
/** Pestañas de la sección Movimientos. Recibe $activa = 'dia' | 'crear' | 'cuenta'. */
$items = [
    'dia'    => ['Movimientos del día',  '/index.php?c=movimientos&a=index'],
    'crear'  => ['Registrar movimiento', '/index.php?c=movimientos&a=crear'],
    'cuenta' => ['Historial por cuenta', '/index.php?c=movimientos&a=porCuenta'],
];
?>
<nav class="subnav" aria-label="Movimientos">
<?php foreach ($items as $clave => [$texto, $url]): ?>
    <a class="subnav__enlace <?= $activa === $clave ? 'subnav__enlace--activo' : '' ?>" href="<?= e($url) ?>"<?= $activa === $clave ? ' aria-current="page"' : '' ?>><?= e($texto) ?></a>
<?php endforeach; ?>
</nav>