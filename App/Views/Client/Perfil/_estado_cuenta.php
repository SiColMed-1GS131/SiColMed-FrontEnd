<?php
/**
 * Pestaña "Estado de cuenta" del Perfil médico (recuperada del commit 24b3131).
 * Variables esperadas: $cuotas, $actividades.
 */
$cuotas = $cuotas ?? [];
$actividades = $actividades ?? [];

if (!function_exists('estadoCuentaBadge')) {
    function estadoCuentaBadge(string $estado): string
    {
        $esPagada = strtolower($estado) === 'pagada';
        return '<span class="badge ' . ($esPagada ? 'badge--ok' : 'badge--pendiente') . '">'
            . ($esPagada ? 'Pagada' : 'Pendiente') . '</span>';
    }
}
?>
<section class="perfil-tab-contenido" id="tab-estado-cuenta" role="tabpanel" aria-labelledby="tab-btn-cuenta">
    <div class="perfil-tab__header" style="margin-bottom: 1.5rem;">
        <h2 class="perfil-tab__title" style="margin: 0;">Estado de cuenta</h2>
        <p class="texto-atenuado">Resumen de cuotas gremiales y pagos de actividades en el Colegio Médico de Panamá.</p>
    </div>

    <div class="formulario__seccion" style="margin-bottom: 2rem;">
        <legend>Cuotas Gremiales</legend>
        <?php if (empty($cuotas)): ?>
            <p class="alerta alerta--info">No hay cuotas gremiales registradas pendientes o cobradas.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">Concepto</th>
                            <th scope="col">Período</th>
                            <th scope="col">Monto</th>
                            <th scope="col">Vencimiento</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuotas as $c): ?>
                            <tr>
                                <td><strong><?= e($c['concepto'] ?? '') ?></strong></td>
                                <td><?= e($c['periodo'] ?? '') ?></td>
                                <td>B/. <?= dinero($c['monto'] ?? 0) ?></td>
                                <td><?= e($c['vencimiento'] ?? '') ?></td>
                                <td><?= estadoCuentaBadge((string) ($c['estado'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="formulario__seccion">
        <legend>Actividades y Eventos Especiales</legend>
        <?php if (empty($actividades)): ?>
            <p class="alerta alerta--info">No hay actividades con cobro registradas.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">Actividad</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Monto</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividades as $a): ?>
                            <tr>
                                <td><strong><?= e($a['nombre'] ?? '') ?></strong></td>
                                <td><?= e($a['fecha'] ?? '') ?></td>
                                <td>B/. <?= dinero($a['monto'] ?? 0) ?></td>
                                <td><?= estadoCuentaBadge((string) ($a['estado'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
