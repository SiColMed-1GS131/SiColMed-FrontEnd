<?php
/**
 * Inicio del administrador. Lo arma PanelController (header, sidebar y footer los pone el controlador).
 * Variables: $estadisticas, $solicitudes, $esEjemplo, $hayPendientes, $urls.
 */
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="panel">

    <header class="panel__cabecera">
        <div>
            <h1 class="panel__titulo">Panel del Administrador</h1>
            <p class="panel__subtitulo">Administración del Colegio Médico de Panamá.</p>
        </div>
        <a class="panel__boton" href="<?= $e($urls['nuevo']) ?>">
            <span class="panel__mas" aria-hidden="true">+</span> Nuevo médico
        </a>
    </header>

    <section class="panel__stats" aria-label="Resumen">
        <?php foreach ($estadisticas as $est): ?>
            <article class="panel__tarjeta panel__stat">
                <h2 class="panel__stat-etiqueta"><?= $e($est['etiqueta']) ?></h2>
                <p class="panel__stat-valor"><?= $e($est['valor']) ?></p>
            </article>
        <?php endforeach; ?>
    </section>
    <?php if ($hayPendientes): ?>
        <p class="panel__nota">Los indicadores con «—» se mostrarán cuando la API esté conectada.</p>
    <?php endif; ?>

    <h2 class="panel__seccion">Accesos rápidos</h2>
    <section class="panel__accesos">
        <a class="panel__tarjeta panel__acceso" href="<?= $e($urls['nuevo']) ?>">
            <span class="panel__icono panel__icono--editar" aria-hidden="true"></span>
            <span><strong>Registrar médico</strong><small>Alta de un nuevo asociado</small></span>
        </a>
        <div class="panel__tarjeta panel__acceso panel__acceso--off" aria-disabled="true">
            <span class="panel__icono panel__icono--mensaje" aria-hidden="true"></span>
            <span><strong>Solicitudes de cambio</strong><small>Próximamente</small></span>
        </div>
        <a class="panel__tarjeta panel__acceso" href="<?= $e($urls['directorio']) ?>">
            <span class="panel__icono panel__icono--lupa" aria-hidden="true"></span>
            <span><strong>Directorio médico</strong><small>Buscar y consultar</small></span>
        </a>
        <a class="panel__tarjeta panel__acceso" href="<?= $e($urls['cuentas']) ?>">
            <span class="panel__icono panel__icono--libro" aria-hidden="true"></span>
            <span><strong>Cuentas</strong><small>Crear, editar o inactivar</small></span>
        </a>
        <a class="panel__tarjeta panel__acceso" href="<?= $e($urls['movimientos']) ?>">
            <span class="panel__icono panel__icono--calendario" aria-hidden="true"></span>
            <span><strong>Movimientos</strong><small>Registro diario por fecha</small></span>
        </a>
        <a class="panel__tarjeta panel__acceso" href="<?= $e($urls['balance']) ?>">
            <span class="panel__icono panel__icono--estadisticas" aria-hidden="true"></span>
            <span><strong>Balance General</strong><small>Reporte por rango de fechas</small></span>
        </a>
    </section>

    <h2 class="panel__seccion">
        Solicitudes de cambio recientes
        <?php if ($esEjemplo): ?><span class="panel__etiqueta-ejemplo">datos de ejemplo</span><?php endif; ?>
    </h2>
    <div class="panel__tarjeta panel__tabla-envoltura">
        <table class="panel__tabla">
            <thead>
                <tr><th scope="col">Médico</th><th scope="col">Dato a cambiar</th><th scope="col">Fecha</th><th scope="col">Estado</th></tr>
            </thead>
            <tbody>
                <?php if (!$solicitudes): ?>
                    <tr><td class="panel__vacio" colspan="4">Todavía no hay solicitudes para mostrar.</td></tr>
                <?php else: ?>
                    <?php foreach ($solicitudes as $s): ?>
                        <tr>
                            <th scope="row"><?= $e($s['medico']) ?></th>
                            <td><?= $e($s['campo']) ?></td>
                            <td><?= $e($s['fecha']) ?></td>
                            <td><span class="panel__estado panel__estado--<?= $s['estado'] === 'Procesada' ? 'ok' : 'pendiente' ?>"><?= $e($s['estado']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
