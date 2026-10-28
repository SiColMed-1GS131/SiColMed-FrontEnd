<?php
/**
 * Historial de una cuenta (5.2)
 * Variables: $cuentas, $cuenta, $idCuenta, $inicio, $fin, $inicio_ui, $fin_ui,
 *            $lineas (null = sin consultar), $errorApi, $errorRango, $errorCuenta
 */
$activa = 'cuenta';

// Selector de cuenta que se pinta dentro del formulario de fechas.
ob_start();
?>
<div class="field">
    <label for="id">Cuenta <span class="req" aria-hidden="true">*</span></label>
    <select id="id" name="id" required aria-describedby="id-error">
        <option value="">Selecciona una cuenta</option>
        <?php foreach ($cuentas as $c): ?>
            <option value="<?= e($c['id_cuenta']) ?>" <?= (int) $idCuenta === (int) $c['id_cuenta'] ? 'selected' : '' ?>>
                <?= e($c['nombre']) ?> (<?= $c['tipo'] === 'DEBITO' ? 'Débito' : 'Crédito' ?><?= $c['activa'] ? '' : ', inactiva' ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <p class="field__error" id="id-error" data-error-for="id" role="alert"><?= e($errorCuenta ?? '') ?></p>
</div>
<?php
$filtro_antes = ob_get_clean();
?>
<section class="movs">
    <header class="movs__cabecera">
        <h1>Movimientos por cuenta</h1>
    </header>

    <?php require __DIR__ . '/_subnav.php'; ?>

    <!-- ADAPTACIÓN: Eliminadas las variables de ruta vieja filtro_c y filtro_a -->
    <form class="filtro filtro--fechas" method="get" action="<?= e($entrada) ?>" novalidate>
        <input type="hidden" name="c" value="movimientos">
        <input type="hidden" name="a" value="porCuenta">
        <?= $filtro_antes ?>
        <div class="field">
            <label for="inicio">Fecha inicio <span class="req" aria-hidden="true">*</span></label>
            <input type="date" id="inicio" name="inicio" value="<?= e($inicio) ?>" required>
        </div>
        <div class="field">
            <label for="fin">Fecha fin <span class="req" aria-hidden="true">*</span></label>
            <input type="date" id="fin" name="fin" value="<?= e($fin) ?>" required>
        </div>
        <button class="btn btn--primary" type="submit">Buscar</button>
    </form>

    <?php if ($errorRango): ?>
        <div class="alert alert--error" role="alert"><?= e($errorRango) ?></div>
    <?php endif; ?>

    <?php if ($errorApi): ?>
        <div class="alert alert--error" role="alert">
            <strong>No se pudo cargar el historial.</strong>
            <?= e($errorApi) ?> Intenta de nuevo en unos minutos.
        </div>

    <?php elseif ($lineas === null && !$errorRango && !$errorCuenta): ?>
        <div class="vacio">
            <p class="vacio__titulo">Elige una cuenta y un rango de fechas.</p>
            <p>Verás todos los movimientos de esa cuenta entre las dos fechas.</p>
        </div>

    <?php elseif ($lineas !== null && empty($lineas)): ?>
        <div class="vacio">
            <p class="vacio__titulo">No hay movimientos de «<?= e($cuenta['nombre']) ?>» entre el <?= e($inicio_ui) ?> y el <?= e($fin_ui) ?>.</p>
            <p>Prueba con un rango de fechas más amplio.</p>
        </div>

    <?php elseif ($lineas !== null): ?>
        <h2 class="movs__subtitulo">
            <?= e($cuenta['nombre']) ?>
            <?php echo badgeTipo($cuenta['tipo']); ?>
            <span class="movs__rango">del <?= e($inicio_ui) ?> al <?= e($fin_ui) ?></span>
        </h2>

        <div class="tabla-envoltura" role="region" aria-label="Historial de la cuenta" tabindex="0">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Tipo</th>
                        <th scope="col" class="tabla__num">Monto</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lineas as $l): ?>
                    <tr>
                        <td class="tabla__fecha"><?= e($l['fecha_ui']) ?></td>
                        <td><?= e($l['descripcion']) ?></td>
                        <td><?php echo badgeTipo($l['tipo']); ?></td>
                        <td class="tabla__num"><?= e(dinero($l['monto'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>