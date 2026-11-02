<?php
/**
 * Vista: Balance General (5.3.a).
 * Variables que prepara ReportesController:
 *   $fechaInicio, $fechaFin (AAAA-MM-DD), $errorFiltro, $errorGeneral,
 *   $consultado (bool), $filas, $totales, $tituloRango
 */
?>
<section class="reportes">

  <div class="reportes__cabecera">
    <h1 class="reportes__titulo">Balance General</h1>
  </div>

  <!-- Filtro compartido de fechas (Partials/filtroFechas.php). c y a se conservan para que el envío vuelva a esta pantalla. -->
  <div class="reportes__filtro">
    <?php
    $filtroOculto = ['c' => 'reportes', 'a' => 'balance'];
    $filtroBoton  = 'Generar reporte';
    $filtroTitulo = 'Rango de fechas';   // recuadro con título, como el diseño original
    require __DIR__ . '/../../../Partials/filtroFechas.php';
    ?>
  </div>

  <?php if ($errorGeneral !== ''): ?>
    <p class="reportes__alerta reportes__alerta--error" role="alert"><?= e($errorGeneral) ?></p>
  <?php endif; ?>

  <?php if ($consultado && $errorGeneral === ''): ?>

    <div class="reportes__encabezado-reporte">
      <h2 class="reportes__subtitulo"><?= e($tituloRango) ?></h2>
      <?php if (!empty($filas)): ?>
        <button type="button" class="btn--primario reportes__btn reportes__btn--imprimir" id="btn-imprimir">Imprimir</button>
      <?php endif; ?>
    </div>

    <?php if (empty($filas)): ?>
      <p class="reportes__vacio">No hay movimientos en el rango de fechas seleccionado.</p>
    <?php else: ?>
      <div class="reportes__tabla-scroll">
        <table class="reportes__tabla">
          <caption class="reportes__caption"><?= e($tituloRango) ?></caption>
          <thead>
            <tr>
              <th scope="col">Cuenta</th>
              <th scope="col" class="reportes__num">Total débito</th>
              <th scope="col" class="reportes__num">Total crédito</th>
              <th scope="col" class="reportes__num">Saldo</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filas as $f): ?>
              <tr>
                <th scope="row"><?= e($f['cuenta']) ?></th>
                <td class="reportes__num"><?= e($f['debito']) ?></td>
                <td class="reportes__num"><?= e($f['credito']) ?></td>
                <td class="reportes__num<?= $f['negativo'] ? ' reportes__negativo' : '' ?>">
                  <?= e($f['saldo']) ?><?php if ($f['negativo']): ?><span class="reportes__sr"> (saldo negativo)</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th scope="row">Totales</th>
              <td class="reportes__num"><?= e($totales['debito']) ?></td>
              <td class="reportes__num"><?= e($totales['credito']) ?></td>
              <td class="reportes__num<?= $totales['negativo'] ? ' reportes__negativo' : '' ?>"><?= e($totales['saldo']) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</section>