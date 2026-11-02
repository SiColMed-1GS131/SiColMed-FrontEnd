<?php
/**
 * Bandeja de Solicitudes de Cambio (Administrador CMP).
 * Variables: $solicitudes, $filtroEstado, $flash, $error.
 */
?>
<div class="registro-container">
    <?php if (!empty($flash)): ?>
        <div class="alerta alerta--<?= e($flash['tipo'] === 'error' ? 'peligro' : 'exito') ?>" style="margin-bottom: 20px;">
            <?= e($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta--peligro" style="margin-bottom: 20px;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <header class="registro-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h1>Bandeja de Solicitudes de Cambio</h1>
            <p>Revisa y procesa las modificaciones solicitadas por los médicos a sus datos profesionales.</p>
        </div>
    </header>

    <!-- Filtro por estado -->
    <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
        <span style="font-weight: 600; color: #1e293b;">Filtrar por estado:</span>
        <a href="/index.php?c=solicitudes&a=index" class="btn <?= $filtroEstado === '' ? 'btn--primario' : 'btn--secundario' ?>" style="padding: 6px 12px; font-size: 0.85rem;">Todas</a>
        <a href="/index.php?c=solicitudes&a=index&estado=PENDIENTE" class="btn <?= $filtroEstado === 'PENDIENTE' ? 'btn--primario' : 'btn--secundario' ?>" style="padding: 6px 12px; font-size: 0.85rem;">Pendientes</a>
        <a href="/index.php?c=solicitudes&a=index&estado=PROCESADO" class="btn <?= $filtroEstado === 'PROCESADO' ? 'btn--primario' : 'btn--secundario' ?>" style="padding: 6px 12px; font-size: 0.85rem;">Procesadas</a>
    </div>

    <?php if (empty($solicitudes)): ?>
        <p class="alerta alerta--info">No hay solicitudes de cambio registradas con este criterio.</p>
    <?php else: ?>
        <div style="overflow-x: auto; background: white; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <table class="tabla" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #e2e8f0; background: #f8fafc; text-align: left;">
                        <th style="padding: 12px 16px;">ID</th>
                        <th style="padding: 12px 16px;">Médico</th>
                        <th style="padding: 12px 16px;">Fecha</th>
                        <th style="padding: 12px 16px;">Campos Solicitados</th>
                        <th style="padding: 12px 16px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $s): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 16px; font-weight: 600;">#<?= e($s['id_solicitud_cambio'] ?? '') ?></td>
                            <td style="padding: 12px 16px;"><?= e($s['nombre_completo'] ?? '—') ?></td>
                            <td style="padding: 12px 16px;"><?= e(isset($s['fecha_solicitud']) ? date('d/m/Y H:i', strtotime((string)$s['fecha_solicitud'])) : '—') ?></td>
                            <td style="padding: 12px 16px;">
                                <?php
                                $cambios = $s['cambios'] ?? [];
                                if (is_array($cambios)) {
                                    foreach ($cambios as $k => $c) {
                                        $nuevo = is_array($c) ? ($c['valor_nuevo'] ?? '') : (string)$c;
                                        echo '<div><strong>' . e(ucfirst(str_replace('_', ' ', (string)$k))) . ':</strong> ' . e($nuevo) . '</div>';
                                    }
                                }
                                ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php $esPendiente = strtoupper((string)($s['estado'] ?? '')) === 'PENDIENTE'; ?>
                                <span class="badge <?= $esPendiente ? 'badge--pendiente' : 'badge--ok' ?>">
                                    <?= e($s['estado'] ?? 'PENDIENTE') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
