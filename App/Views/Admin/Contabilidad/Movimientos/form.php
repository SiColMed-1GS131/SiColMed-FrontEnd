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
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          
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

        <?php // Partida doble: el mismo monto va al débito de una cuenta y al crédito de otra ?>
        <?php foreach (['id_cuenta_debito' => 'Cuenta a debitar', 'id_cuenta_credito' => 'Cuenta a acreditar'] as $campo => $etiqueta): ?>
        <div class="field <?= $hayError($campo) ? 'field--error' : '' ?>">
            <label for="<?= e($campo) ?>"><?= e($etiqueta) ?> <span class="req" aria-hidden="true">*</span></label>
            <select id="<?= e($campo) ?>" name="<?= e($campo) ?>" required
                    aria-describedby="<?= e($campo) ?>-error" <?= $hayError($campo) ? 'aria-invalid="true"' : '' ?>>
                <option value="">Selecciona una cuenta</option>
                <?php foreach ($cuentas as $c): ?>
                    <option value="<?= e($c['id_cuenta']) ?>" <?= (string) $old[$campo] === (string) $c['id_cuenta'] ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?> (<?= $c['tipo'] === 'DEBITO' ? 'Débito' : 'Crédito' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="field__error" id="<?= e($campo) ?>-error" data-error-for="<?= e($campo) ?>" role="alert"><?= e($errores[$campo] ?? '') ?></p>
        </div>
        <?php endforeach; ?>

        <div class="field <?= $hayError('monto') ? 'field--error' : '' ?>">
            <label for="monto">Monto <span class="req" aria-hidden="true">*</span></label>
            <input type="number" id="monto" name="monto" value="<?= e($old['monto']) ?>"
                   min="0.01" max="99999999.99" step="0.01" inputmode="decimal" required
                   aria-describedby="monto-ayuda monto-error" <?= $hayError('monto') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__ayuda" id="monto-ayuda"></p>
            <p class="field__error" id="monto-error" data-error-for="monto" role="alert"><?= e($errores['monto'] ?? '') ?></p>
        </div>

        <div class="formulario__acciones">
            <button class="btn btn--primary" type="submit" <?= $puedeEnviar ? '' : 'disabled' ?>>Registrar movimiento</button>
            <a class="btn btn--secondary" href="<?= e($urlCancelar) ?>">Cancelar</a>
        </div>
    </form>
    <?php endif; ?>
</section>