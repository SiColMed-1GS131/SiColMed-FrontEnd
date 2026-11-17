<?php
/**
 * Bandeja del administrador para solicitudes de cambio.
 * Variables: $solicitudes, $errorApi, $estado, $csrf, $flash.
 */
$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$idPorSeccion = [
    'lugar_trabajo' => 'id_lugar_trabajo',
    'estudio_medico' => 'id_estudio',
    'certificaciones' => 'id_certificacion',
];
$relaciones = ['especialidades', 'subespecialidades', 'asociaciones'];
$urlEstado = static function (?string $valor): string {
    return '/index.php?c=solicitudes&a=index' . ($valor === null ? '' : '&estado=' . $valor);
};
$valor = static function ($v): string {
    if ($v === null) {
        return '—';
    }
    if (is_bool($v)) {
        return $v ? 'Sí' : 'No';
    }
    return (string) $v;
};
$filtros = [
    ['valor' => null, 'texto' => 'Todas'],
    ['valor' => 'PENDIENTE', 'texto' => 'Pendientes'],
    ['valor' => 'PROCESADO', 'texto' => 'Procesadas'],
];
?>
<section class="panel">
    <header class="panel__cabecera">
        <div>
            <h1 class="panel__titulo">Solicitudes de cambio</h1>
            <p class="panel__subtitulo">Revisa y decide los cambios pedidos por los médicos.</p>
        </div>
    </header>

    <?php if (!empty($flash)): ?>
        <div class="alerta alerta--<?= $h($flash['tipo']) ?>" role="status"><?= $h($flash['texto']) ?></div>
    <?php endif; ?>

    <nav class="filtro-estado" aria-label="Filtrar solicitudes por estado">
        <?php foreach ($filtros as $filtro): ?>
            <a class="filtro-estado__opcion<?= $estado === $filtro['valor'] ? ' filtro-estado__opcion--activa' : '' ?>"
               href="<?= $h($urlEstado($filtro['valor'])) ?>"
               <?= $estado === $filtro['valor'] ? 'aria-current="page"' : '' ?>><?= $h($filtro['texto']) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($errorApi !== ''): ?>
        <div class="alerta alerta--error" role="alert">
            <strong>No se pudieron cargar las solicitudes.</strong> <?= $h($errorApi) ?>
        </div>
    <?php elseif ($solicitudes === []): ?>
        <div class="vacio">
            <p class="vacio__titulo">No hay solicitudes para mostrar.</p>
        </div>
    <?php else: ?>
        <?php foreach ($solicitudes as $solicitud):
            $id = (int) ($solicitud['id_solicitud_cambio'] ?? 0);
            $estadoSolicitud = (string) ($solicitud['estado'] ?? '');
            $cambios = is_array($solicitud['cambios'] ?? null) ? $solicitud['cambios'] : [];
        ?>
        <article class="panel__tarjeta solicitud">
            <header class="solicitud__cabecera">
                <div>
                    <h2 class="solicitud__titulo">Solicitud N.° <?= $h($id) ?></h2>
                    <p class="solicitud__meta">
                        <?= $h($solicitud['nombre_completo'] ?? '') ?>
                        · Médico <?= $h($solicitud['id_medico'] ?? '') ?>
                        · <?= $h($solicitud['fecha_solicitud'] ?? '') ?>
                    </p>
                </div>
                <span class="panel__estado panel__estado--<?= $estadoSolicitud === 'PROCESADO' ? 'ok' : 'pendiente' ?>">
                    <?= $h($estadoSolicitud) ?>
                </span>
            </header>

            <?php if ($estadoSolicitud === 'PENDIENTE'): ?>
            <form method="post" action="/index.php?c=solicitudes&amp;a=procesar" class="solicitud__form">
                <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
                <input type="hidden" name="id_solicitud_cambio" value="<?= $h($id) ?>">
                <input type="hidden" name="estado" value="<?= $h((string) ($estado ?? '')) ?>">
            <?php else: ?>
            <div class="solicitud__detalle">
            <?php endif; ?>

                <?php foreach ($cambios as $seccion => $contenido):
                    $seccion = (string) $seccion;
                    if (in_array($seccion, $relaciones, true)):
                        $estadoRelacion = (string) ($contenido['estado'] ?? '');
                ?>
                    <section class="solicitud__seccion">
                        <h3 class="solicitud__subtitulo"><?= $h(ucfirst($seccion)) ?></h3>
                        <p class="solicitud__meta">Agregar: <?= $h(implode(', ', (array) ($contenido['agregar'] ?? []))) ?: '—' ?>
                            · Quitar: <?= $h(implode(', ', (array) ($contenido['quitar'] ?? []))) ?: '—' ?></p>
                        <?php if ($estadoSolicitud === 'PENDIENTE' && $estadoRelacion === 'PENDIENTE'): ?>
                            <label class="field__ayuda" for="dec-<?= $h($id . '-' . $seccion) ?>">Decisión</label>
                            <select id="dec-<?= $h($id . '-' . $seccion) ?>" name="decisiones[<?= $h($seccion) ?>][estado]">
                                <option value="RECHAZADO">RECHAZADO</option>
                                <option value="APROBADO">APROBADO</option>
                            </select>
                        <?php else: ?>
                            <span class="panel__estado panel__estado--<?= $estadoRelacion === 'APROBADO' ? 'ok' : 'pendiente' ?>"><?= $h($estadoRelacion) ?></span>
                        <?php endif; ?>
                    </section>
                <?php else:
                    $idCampo = $idPorSeccion[$seccion] ?? 'id';
                    $registros = is_array($contenido) ? array_values($contenido) : [];
                ?>
                    <section class="solicitud__seccion">
                        <h3 class="solicitud__subtitulo"><?= $h(ucfirst($seccion)) ?></h3>
                        <?php foreach ($registros as $indice => $registro): ?>
                            <div class="solicitud__registro">
                                <p class="solicitud__meta">Registro <?= $h($registro[$idCampo] ?? '') ?></p>
                                <?php foreach ((array) $registro as $campo => $detalle):
                                    if ($campo === $idCampo || !is_array($detalle)) {
                                        continue;
                                    }
                                    $estadoCampo = (string) ($detalle['estado'] ?? '');
                                ?>
                                    <div class="solicitud__campo">
                                        <span class="solicitud__campo-nombre"><?= $h($campo) ?>:</span>
                                        <span><?= $h($valor($detalle['valor_anterior'] ?? null)) ?> → <?= $h($valor($detalle['valor_nuevo'] ?? null)) ?></span>
                                        <?php if ($estadoSolicitud === 'PENDIENTE' && $estadoCampo === 'PENDIENTE'): ?>
                                            <select name="decisiones[<?= $h($seccion) ?>][<?= $h((string) $indice) ?>][<?= $h((string) $campo) ?>]"
                                                    aria-label="Decisión para <?= $h($campo) ?>">
                                                <option value="RECHAZADO">RECHAZADO</option>
                                                <option value="APROBADO">APROBADO</option>
                                            </select>
                                        <?php else: ?>
                                            <span class="panel__estado panel__estado--<?= $estadoCampo === 'APROBADO' ? 'ok' : 'pendiente' ?>"><?= $h($estadoCampo) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; endforeach; ?>

                <?php if ($estadoSolicitud === 'PENDIENTE'): ?>
                    <div class="field">
                        <label for="com-<?= $h($id) ?>">Comentario (opcional)</label>
                        <textarea id="com-<?= $h($id) ?>" name="comentario" rows="2" maxlength="500"></textarea>
                    </div>
                    <p class="field__ayuda">Por defecto cada decisión es RECHAZADO. Cambia a APROBADO lo que corresponda.</p>
                    <button type="submit" class="btn">Enviar decisiones</button>
                </form>
                <?php else: ?>
                    <?php if (!empty($solicitud['comentario'])): ?>
                        <p class="solicitud__meta">Comentario: <?= $h($solicitud['comentario']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
        </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
