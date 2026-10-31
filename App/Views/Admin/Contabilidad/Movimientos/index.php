<?php
/**
 * Movimientos del día (5.2)
 * Variables: $fecha, $fecha_ui, $grupos, $totales, $errorFecha, $errorApi, $flash
 * Solo muestra datos. Sin llamadas a la API ni cálculos.
 */
$activa = 'dia';

// ADAPTACIÓN 1: Ruta limpia para el botón de crear
$urlCrear = '/movimientos/crear?' . http_build_query(['fecha' => $fecha]);
?>
<section class="movs">
    <header class="movs__cabecera">
        <h1>Movimientos diarios</h1>
        <a class="btn btn--primary" href="<?= e($urlCrear) ?>">Registrar movimiento</a>
    </header>

    <?php require __DIR__ . '/_subnav.php'; ?>

    <?php if (!empty($flash)): ?>
        <div class="alert alert--<?= e($flash['tipo']) ?>" role="status"><?= e($flash['texto']) ?></div>
    <?php endif; ?>

    <!-- ADAPTACIÓN 2: Action limpio y eliminación de inputs ocultos (c y a) -->
    <form class="filtro filtro--dia" method="get" action="/movimientos" novalidate>
        <div class="field">
            <label for="fecha">Fecha <span class="req" aria-hidden="true">*</span></label>
            <input type="date" id="fecha" name="fecha" value="<?= e($fecha) ?>" required aria-describedby="fecha-error">
            <p class="field__error" id="fecha-error" data-error-for="fecha" role="alert"><?= e($errorFecha ?? '') ?></p>
        </div>
        <button class="btn btn--secondary" type="submit">Ver movimientos</button>
    </form>

    <?php if ($errorApi): ?>
        <div class="alert alert--error" role="alert">
            <strong>No se pudieron cargar los movimientos.</strong>
            <?= e($errorApi) ?> Intenta de nuevo en unos minutos.
            <!-- ADAPTACIÓN 3: Ruta limpia para reintentar -->
            <a href="<?= e('/movimientos?' . http_build_query(['fecha' => $fecha])) ?>">Reintentar</a>
        </div>

    <?php elseif ($errorFecha): ?>
        <!-- El mensaje ya se muestra junto al campo de fecha. -->

    <?php elseif (empty($grupos)): ?>
        <div class="vacio">
            <p class="vacio__titulo">No hay movimientos registrados el <?= e($fecha_ui) ?>.</p>
            <p>Puedes elegir otra fecha o registrar el primer movimiento de este día.</p>
            <a class="btn btn--primary" href="<?= e($urlCrear) ?>">Registrar movimiento</a>
        </div>

    <?php else: ?>
        <h2 class="movs__subtitulo">Movimientos del <?= e($fecha_ui) ?></h2>

        <div class="tabla-envoltura" role="region" aria-label="Tabla de movimientos del día" tabindex="0">
            <table class="tabla tabla-mov">
                <thead>
                    <tr>
                        <th scope="col">Descripción</th>
                        <th scope="col">Cuenta</th>
                        <th scope="col">Tipo</th>
                        <th scope="col" class="tabla__num">Monto</th>
                    </tr>
                </thead>
                <?php foreach ($grupos as $g): ?>
                    <tbody class="tabla-mov__grupo">
                    <?php foreach ($g['lineas'] as $i => $l): ?>
                        <tr>
                            <?php if ($i === 0): ?>
                                <th scope="rowgroup" class="tabla-mov__desc" rowspan="<?= count($g['lineas']) ?>"><?= e($g['descripcion']) ?></th>
                            <?php endif; ?>
                            <td><?= e($l['nombre_cuenta']) ?></td>
                            <td><?php View::partial('badge_tipo', ['tipo' => $l['tipo']]); ?></td>
                            <td class="tabla__num"><?= e(dinero($l['monto'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                <?php endforeach; ?>
                <tfoot>
                    <tr class="tabla-mov__total">
                        <th scope="row" colspan="3">Total débito del día</th>
                        <td class="tabla__num"><?= e(dinero($totales['debito'])) ?></td>
                    </tr>
                    <tr class="tabla-mov__total">
                        <th scope="row" colspan="3">Total crédito del día</th>
                        <td class="tabla__num"><?= e(dinero($totales['credito'])) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</section>