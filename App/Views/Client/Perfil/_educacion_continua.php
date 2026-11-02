<?php
/**
 * Pestaña "Educación continua" del Perfil médico (recuperada del commit 24b3131).
 * Variable esperada: $educacion (arreglo de actividades). Puede estar vacía.
 * Cada actividad: nombre_actividad, verificada (bool), certificado_url,
 * institucion, fecha, lugar, tipo, horas.
 */
$educacion = $educacion ?? [];
?>
<section class="perfil-tab-contenido" id="tab-educacion-continua" role="tabpanel" aria-labelledby="tab-btn-educacion">
    <div class="perfil-tab__header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 class="perfil-tab__title" style="margin: 0;">Educación continua</h2>
        <a href="/index.php?c=solicitudes&a=crear" class="btn btn--primario">
            Solicitar inclusión de actividad
        </a>
    </div>

    <?php if (empty($educacion)): ?>
        <p class="alerta alerta--info">Aún no hay actividades de educación continua registradas para este período.</p>
    <?php else: ?>
        <div class="perfil-tab__scroll" style="overflow-x: auto;">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Actividad</th>
                        <th scope="col">Certificado</th>
                        <th scope="col">Institución</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Lugar</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Horas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($educacion as $act): ?>
                        <tr>
                            <td>
                                <strong><?= e($act['nombre_actividad'] ?? '') ?></strong>
                                <?php if (empty($act['verificada'])): ?>
                                    <span class="badge badge--pendiente">Por verificar</span>
                                <?php else: ?>
                                    <span class="badge badge--ok">Verificada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($act['certificado_url'])): ?>
                                    <a href="<?= e($act['certificado_url']) ?>" target="_blank" rel="noopener">Ver PDF</a>
                                <?php else: ?>
                                    <span class="texto-atenuado">Sin certificado</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($act['institucion'] ?? '—') ?></td>
                            <td><?= e($act['fecha'] ?? '—') ?></td>
                            <td><?= e($act['lugar'] ?? '—') ?></td>
                            <td><?= e($act['tipo'] ?? '—') ?></td>
                            <td><?= e($act['horas'] ?? '—') ?> hrs</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
