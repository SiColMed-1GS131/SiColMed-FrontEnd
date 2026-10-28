<?php
/**
 * Vista: Balance General (5.3.a).
 * Variables que prepara ReportesController:
 *   $inicio, $fin (AAAA-MM-DD), $consultado (bool), $errores (campo => mensaje),
 *   $errorGeneral, $filas, $totales, $tituloRango
 * Esta vista no calcula nada ni llama a la API.
 */
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
?>
<section class="reportes">

  <div class="reportes__cabecera">
    <h1 class="reportes__titulo">Balance General</h1>
  </div>

  <!-- Filtro de fechas. Pendiente: reemplazar por Partials/filtro_fechas.php (Lione) -->
  <form class="reportes__filtro" id="form-balance" method="get" action="/reportes/balance" novalidate>
    <fieldset class="reportes__fieldset">
      <legend class="reportes__legend">Rango de fechas</legend>

      <div class="reportes__campo">
        <label for="inicio">Fecha inicio <span class="reportes__req" aria-hidden="true">*</span></label>
        <input type="date" id="inicio" name="inicio" value="<?= $h($inicio) ?>" required
               aria-describedby="error-inicio">
        <p class="reportes__error" id="error-inicio" role="alert"><?= $h($errores['inicio'] ?? '') ?></p>
      </div>

      <div class="reportes__campo">
        <label for="fin">Fecha fin <span class="reportes__req" aria-hidden="true">*</span></label>
        <input type="date" id="fin" name="fin" value="<?= $h($fin) ?>" required
               aria-describedby="error-fin">
        <p class="reportes__error" id="error-fin" role="alert"><?= $h($errores['fin'] ?? '') ?></p>
      </div>

      <div class="reportes__acciones">
        <button type="submit" class="btn btn--primary reportes__btn">Generar reporte</button>
      </div>
    </fieldset>
  </form>

  <?php if ($errorGeneral !== ''): ?>
    <p class="reportes__alerta reportes__alerta--error" role="alert"><?= $h($errorGeneral) ?></p>
  <?php endif; ?>

  <?php if ($consultado && $errorGeneral === ''): ?>

    <div class="reportes__encabezado-reporte">
      <h2 class="reportes__subtitulo"><?= $h($tituloRango) ?></h2>
      <?php if (!empty($filas)): ?>
        <button type="button" class="btn reportes__btn reportes__btn--imprimir" id="btn-imprimir">Imprimir</button>
      <?php endif; ?>
    </div>

    <?php if (empty($filas)): ?>
      <p class="reportes__vacio">No hay movimientos en el rango de fechas seleccionado.</p>
    <?php else: ?>
      <div class="reportes__tabla-scroll">
        <table class="reportes__tabla">
          <caption class="reportes__caption"><?= $h($tituloRango) ?></caption>
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
                <th scope="row"><?= $h($f['cuenta']) ?></th>
                <td class="reportes__num"><?= $h($f['debito']) ?></td>
                <td class="reportes__num"><?= $h($f['credito']) ?></td>
                <td class="reportes__num<?= $f['negativo'] ? ' reportes__negativo' : '' ?>">
                  <?= $h($f['saldo']) ?><?php if ($f['negativo']): ?><span class="reportes__sr"> (saldo negativo)</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th scope="row">Totales</th>
              <td class="reportes__num"><?= $h($totales['debito']) ?></td>
              <td class="reportes__num"><?= $h($totales['credito']) ?></td>
              <td class="reportes__num<?= $totales['negativo'] ? ' reportes__negativo' : '' ?>"><?= $h($totales['saldo']) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</section>