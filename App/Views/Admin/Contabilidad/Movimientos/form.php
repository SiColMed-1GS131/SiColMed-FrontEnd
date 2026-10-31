<?php
/**
 * Registrar movimiento (5.2)
 * Variables: $old, $errores, $cuentas (solo activas), $errorApi
 */
$activa = 'crear';
$hayError = fn(string $campo): bool => !empty($errores[$campo]);

// ADAPTACIÓN 1: Ruta limpia para los botones de volver/cancelar
$urlCancelar = '/movimientos?' . http_build_query(['fecha' => $old['fecha']]);
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

    <!-- ADAPTACIÓN 2: Ruta limpia para el action del formulario -->
    <form class="formulario" id="form-movimiento" method="post"
          action="/movimientos/crear" novalidate>

        <p class="formulario__nota">Los campos con <span class="req" aria-hidden="true">*</span> son obligatorios.</p>

        <div class="field <?= $hayError('fecha') ? 'field--error' : '' ?>">
            <label for="fecha">Fecha <span class="req" aria-hidden="true">*</span> <span class="field__oblig">(obligatoria)</span></label>
            <input type="date" id="fecha" name="fecha" value="<?= e($old['fecha']) ?>" required
                   aria-describedby="fecha-ayuda fecha-error" <?= $hayError('fecha') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__ayuda" id="fecha-ayuda">Se rellena con la fecha de hoy, pero puedes cambiarla.</p>
            <p class="field__error" id="fecha-error" data-error-for="fecha" role="alert"><?= e($errores['fecha'] ?? '') ?></p>
        </div>

        <div class="field <?= $hayError('descripcion') ? 'field--error' : '' ?>">
            <label for="descripcion">Descripción <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="descripcion" name="descripcion" value="<?= e($old['descripcion']) ?>"
                   maxlength="200" required autocomplete="off"
                   aria-describedby="descripcion-error" <?= $hayError('descripcion') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__error" id="descripcion-error" data-error-for="descripcion" role="alert"><?= e($errores['descripcion'] ?? '') ?></p>
        </div>

        <div class="field <?= $hayError('id_cuenta') ? 'field--error' : '' ?>">
            <label for="id_cuenta">Cuenta <span class="req" aria-hidden="true">*</span></label>
            <select id="id_cuenta" name="id_cuenta" required
                    aria-describedby="id_cuenta-ayuda id_cuenta-error" <?= $hayError('id_cuenta') ? 'aria-invalid="true"' : '' ?>>
                <option value="">Selecciona una cuenta</option>
                <?php foreach ($cuentas as $c): ?>
                    <option value="<?= e($c['id_cuenta']) ?>" <?= (string) $old['id_cuenta'] === (string) $c['id_cuenta'] ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?> (<?= $c['tipo'] === 'DEBITO' ? 'Débito' : 'Crédito' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="field__ayuda" id="id_cuenta-ayuda">Solo aparecen las cuentas activas.</p>
            <p class="field__error" id="id_cuenta-error" data-error-for="id_cuenta" role="alert"><?= e($errores['id_cuenta'] ?? '') ?></p>
        </div>

        <div class="field <?= $hayError('monto') ? 'field--error' : '' ?>">
            <label for="monto">Monto <span class="req" aria-hidden="true">*</span></label>
            <input type="number" id="monto" name="monto" value="<?= e($old['monto']) ?>"
                   min="0.01" max="99999999.99" step="0.01" inputmode="decimal" required
                   aria-describedby="monto-ayuda monto-error" <?= $hayError('monto') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__ayuda" id="monto-ayuda">Mayor que cero y con máximo 2 decimales. Ejemplo: 1250.75</p>
            <p class="field__error" id="monto-error" data-error-for="monto" role="alert"><?= e($errores['monto'] ?? '') ?></p>
        </div>

        <fieldset class="field field--grupo <?= $hayError('tipo') ? 'field--error' : '' ?>"
                  aria-describedby="tipo-error">
            <legend>Tipo <span class="req" aria-hidden="true">*</span></legend>
            <div class="opciones">
                <label class="opcion">
                    <input type="radio" name="tipo" value="DEBITO" <?= $old['tipo'] === 'DEBITO' ? 'checked' : '' ?> required>
                    <span>Débito</span>
                </label>
                <label class="opcion">
                    <input type="radio" name="tipo" value="CREDITO" <?= $old['tipo'] === 'CREDITO' ? 'checked' : '' ?>>
                    <span>Crédito</span>
                </label>
            </div>
            <p class="field__error" id="tipo-error" data-error-for="tipo" role="alert"><?= e($errores['tipo'] ?? '') ?></p>
        </fieldset>

        <div class="formulario__acciones">
            <button class="btn btn--primary" type="submit" <?= $puedeEnviar ? '' : 'disabled' ?>>Registrar movimiento</button>
            <a class="btn btn--secondary" href="<?= e($urlCancelar) ?>">Cancelar</a>
        </div>
    </form>
    <?php endif; ?>
</section>