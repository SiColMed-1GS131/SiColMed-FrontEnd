<?php
/**
 * Registrar movimiento (5.2)
 * Variables: $old, $errores, $cuentas (solo activas), $errorApi
 */
$activa = 'crear';
$hayError = fn(string $campo): bool => !empty($errores[$campo]);

// Rutas del sistema: ?c=controlador&a=accion
$urlCancelar = '/index.php?c=movimientos&a=index&' . http_build_query(['fecha' => $old['fecha']]);
$puedeEnviar = empty($errorApi) || !empty($cuentas);

// Una línea del detalle (cuenta, tipo y monto). Se usa para las filas del formulario y para la plantilla de JS.
$lineaMovimiento = function ($i, array $l) use ($cuentas, $errores): string {
    $err = fn(string $campo): string => e($errores["detalle_{$i}_{$campo}"] ?? '');
    ob_start(); ?>
    <div class="registro__fila" data-linea>
        <div class="field">
            <label for="detalle_<?= e($i) ?>_id_cuenta">Cuenta</label>
            <select id="detalle_<?= e($i) ?>_id_cuenta" name="detalle[<?= e($i) ?>][id_cuenta]" required>
                <option value="">Selecciona una cuenta</option>
                <?php foreach ($cuentas as $c): ?>
                    <option value="<?= e($c['id_cuenta']) ?>" <?= (string) $l['id_cuenta'] === (string) $c['id_cuenta'] ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?> (<?= $c['tipo'] === 'DEBITO' ? 'Débito' : 'Crédito' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="field__error" role="alert"><?= $err('id_cuenta') ?></p>
        </div>
        <div class="field">
            <label for="detalle_<?= e($i) ?>_tipo">Tipo</label>
            <select id="detalle_<?= e($i) ?>_tipo" name="detalle[<?= e($i) ?>][tipo]" required>
                <option value="DEBITO" <?= $l['tipo'] === 'DEBITO' ? 'selected' : '' ?>>Débito</option>
                <option value="CREDITO" <?= $l['tipo'] === 'CREDITO' ? 'selected' : '' ?>>Crédito</option>
            </select>
            <p class="field__error" role="alert"><?= $err('tipo') ?></p>
        </div>
        <div class="field">
            <label for="detalle_<?= e($i) ?>_monto">Monto</label>
            <input type="number" id="detalle_<?= e($i) ?>_monto" name="detalle[<?= e($i) ?>][monto]" value="<?= e($l['monto']) ?>"
                   min="0.01" max="99999999.99" step="0.01" inputmode="decimal" required>
            <p class="field__error" role="alert"><?= $err('monto') ?></p>
        </div>
        <button type="button" class="btn registro__quitar" data-quitar-linea>Quitar</button>
    </div>
    <?php
    return (string) ob_get_clean();
};
?>
<section class="movs">
    <header class="movs__cabecera">
        <h1>Registrar movimiento</h1>
    </header>

    <?php require __DIR__ . '/_subnav.php'; ?>

    <?php if ($errorApi): ?>
        <div class="alert alert--error" role="alert">
            <strong>No se pudo completar la operación.</strong>
            <?= e($errorApi) ?> Tus datos siguen en el formulario: intenta de nuevo en unos minutos.
        </div>
    <?php endif; ?>

    <?php if (empty($cuentas)): ?>
        <div class="vacio">
            <?php if (!$errorApi): ?>
                <p class="vacio__titulo">No hay cuentas activas para registrar movimientos.</p>
                <p>Crea o reactiva una cuenta en la sección de Cuentas y vuelve aquí.</p>
            <?php endif; ?>
            <a class="btn btn--secondary" href="<?= e($urlCancelar) ?>">Volver a movimientos</a>
        </div>
    <?php else: ?>

    <?php if (!empty($errores)): ?>
        <div class="alert alert--error" role="alert">Revisa los campos marcados: el movimiento no se registró.</div>
    <?php endif; ?>

    <form class="formulario" id="form-movimiento" method="post"
          action="/index.php?c=movimientos&a=crear" novalidate>
        <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">

        <p class="formulario__nota">Los campos con <span class="req" aria-hidden="true">*</span> son obligatorios.</p>

        <div class="field <?= $hayError('fecha') ? 'field--error' : '' ?>">
            <label for="fecha">Fecha <span class="req" aria-hidden="true">*</span> <span class="field__oblig">(obligatoria)</span></label>
            <input type="date" id="fecha" name="fecha" value="<?= e($old['fecha']) ?>" required
                   aria-describedby="fecha-ayuda fecha-error" <?= $hayError('fecha') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__ayuda" id="fecha-ayuda"></p>
            <p class="field__error" id="fecha-error" data-error-for="fecha" role="alert"><?= e($errores['fecha'] ?? '') ?></p>
        </div>

        <div class="field <?= $hayError('descripcion') ? 'field--error' : '' ?>">
            <label for="descripcion">Descripción <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="descripcion" name="descripcion" value="<?= e($old['descripcion']) ?>"
                   maxlength="200" required autocomplete="off"
                   aria-describedby="descripcion-error" <?= $hayError('descripcion') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__error" id="descripcion-error" data-error-for="descripcion" role="alert"><?= e($errores['descripcion'] ?? '') ?></p>
        </div>

        <!-- Partida doble: varias líneas; los débitos deben ser iguales a los créditos -->
        <fieldset class="field field--grupo <?= $hayError('detalle') ? 'field--error' : '' ?>" aria-describedby="detalle-error">
            <legend>Detalle del movimiento <span class="req" aria-hidden="true">*</span></legend>

            <div id="detalle-lineas" data-siguiente="<?= count($old['detalle']) ?>">
                <?php foreach ($old['detalle'] as $i => $l): ?>
                    <?= $lineaMovimiento($i, $l) ?>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn--secondary" id="detalle-agregar">Agregar línea</button>

            <p class="field__ayuda" id="detalle-cuadre" aria-live="polite">
                Débitos: <strong data-total="DEBITO">0.00</strong> ·
                Créditos: <strong data-total="CREDITO">0.00</strong> ·
                <span data-cuadre></span>
            </p>
            <p class="field__error" id="detalle-error" data-error-for="detalle" role="alert"><?= e($errores['detalle'] ?? '') ?></p>

            <template id="detalle-plantilla"><?= $lineaMovimiento('__IDX__', ['id_cuenta' => '', 'tipo' => 'DEBITO', 'monto' => '']) ?></template>
        </fieldset>

        <div class="formulario__acciones">
            <button class="btn btn--primary" type="submit" <?= $puedeEnviar ? '' : 'disabled' ?>>Registrar movimiento</button>
            <a class="btn btn--secondary" href="<?= e($urlCancelar) ?>">Cancelar</a>
        </div>
    </form>
    <?php endif; ?>
</section>