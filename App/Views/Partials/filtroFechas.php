<?php
/**
 * App/Views/Partials/filtro_fechas.php
 *
 * Filtro de rango de fechas (Fecha inicio / Fecha fin) para el Módulo 5.
 * Lo usan: Movimientos/por_cuenta.php y Reportes/balance_general.php.
 * Usa solo clases que ya existen en base.css (.formulario, .formulario__grid, .campo, .btn--primario).
 *
 * VARIABLES que la pantalla define ANTES de incluir este archivo (todas opcionales):
 *   $fechaInicio   string 'AAAA-MM-DD'  valor actual de "Fecha inicio"
 *   $fechaFin      string 'AAAA-MM-DD'  valor actual de "Fecha fin"
 *   $errorFiltro   string               mensaje de error a mostrar (ej. "La fecha de inicio no puede ser mayor...")
 *   $filtroOculto  array                campos extra a conservar, ej. ['id' => 3] para por_cuenta.php
 *   $filtroBoton   string               texto del botón (por defecto "Generar reporte")
 *   $filtroTitulo  string               título del recuadro (ej. "Rango de fechas"). Vacío = diseño simple
 *
 * USO:
 *   $fechaInicio = $_GET['inicio'] ?? '';
 *   $fechaFin    = $_GET['fin']    ?? '';
 *   require __DIR__ . '/../../Partials/filtro_fechas.php';   // desde Views/Contabilidad/<Carpeta>/
 *
 * VALIDACIÓN (la hace la pantalla, no este archivo):
 *   if ($fechaInicio !== '' && $fechaFin !== '' && $fechaInicio > $fechaFin) {
 *       $errorFiltro = 'La fecha de inicio no puede ser mayor que la fecha de fin.';
 *   }
 *   (El formato AAAA-MM-DD se puede comparar como texto.)
 */

$fechaInicio  = $fechaInicio  ?? '';
$fechaFin     = $fechaFin     ?? '';
$errorFiltro  = $errorFiltro  ?? '';
$filtroOculto = $filtroOculto ?? [];
$filtroBoton  = $filtroBoton  ?? 'Generar reporte';
$filtroTitulo = $filtroTitulo ?? '';   // si se define, el filtro se dibuja con recuadro y título (fieldset/legend)

$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<?php $conMarco = $filtroTitulo !== ''; ?>
<form class="formulario<?= $conMarco ? ' filtro-fechas' : '' ?>" method="get" action="">

    <?php if ($errorFiltro !== ''): ?>
        <p role="alert"><?= $esc($errorFiltro) ?></p>
    <?php endif; ?>

    <?php foreach ($filtroOculto as $nombre => $valor): ?>
        <input type="hidden" name="<?= $esc($nombre) ?>" value="<?= $esc($valor) ?>">
    <?php endforeach; ?>

    <?php if ($conMarco): ?><fieldset class="filtro-fechas__marco"><legend class="filtro-fechas__leyenda"><?= $esc($filtroTitulo) ?></legend><?php endif; ?>

    <div class="formulario__grid">
        <div class="campo">
            <label for="inicio">Fecha inicio<?php if ($conMarco): ?> <span class="filtro-fechas__req" aria-hidden="true">*</span><?php endif; ?></label>
            <input type="date" id="inicio" name="inicio" value="<?= $esc($fechaInicio) ?>"
                   <?= $fechaFin !== '' ? 'max="' . $esc($fechaFin) . '"' : '' ?> required>
        </div>

        <div class="campo">
            <label for="fin">Fecha fin<?php if ($conMarco): ?> <span class="filtro-fechas__req" aria-hidden="true">*</span><?php endif; ?></label>
            <input type="date" id="fin" name="fin" value="<?= $esc($fechaFin) ?>"
                   <?= $fechaInicio !== '' ? 'min="' . $esc($fechaInicio) . '"' : '' ?> required>
        </div>

        <?php if ($conMarco): ?>
        <div class="filtro-fechas__boton">
            <button type="submit" class="btn--primario"><?= $esc($filtroBoton) ?></button>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($conMarco): ?></fieldset><?php else: ?>
    <div class="formulario__acciones">
        <button type="submit" class="btn--primario"><?= $esc($filtroBoton) ?></button>
    </div>
    <?php endif; ?>
</form>