<?php
/**
 * Pestaña "Educación continua" del Perfil médico (P4 · Miguel).
 * Variable esperada: $educacion (arreglo de actividades). Puede estar vacía.
 * Cada actividad: nombre_actividad, verificada (bool), certificado_url,
 * institucion, fecha, lugar, tipo, horas.
 * La vista NO llama a la API; los datos los pasa el controlador.
 */
$educacion = $educacion ?? [];
?>
<section class="perfil-tab" id="educacion-continua" aria-labelledby="educacion-continua-titulo">
    <div class="perfil-tab__header">
        <h2 class="perfil-tab__title" id="educacion-continua-titulo">Educación continua</h2>
        <button type="button" class="btn btn--primary" disabled title="Disponible cuando el servicio de datos esté listo">
            Agregar actividad
        </button>
    </div>

    <?php if (empty($educacion)): ?>
        <p class="alert perfil-tab__vacio">Aún no hay actividades de educación continua registradas.</p>
    <?php else: ?>
        <div class="perfil-tab__scroll">
            <table class="table perfil-tabla">
                <caption class="perfil-tabla__caption">Actividades de educación continua</caption>
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
                        <tr class="perfil-tabla__fila">
                            <td>
                                <?= e($act['nombre_actividad'] ?? '') ?>
                                <?php if (empty($act['verificada'])): ?>
                                    <span class="badge estado estado--pendiente">Por verificar</span>
                                <?php else: ?>
                                    <span class="badge estado estado--ok">Verificada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($act['certificado_url'])): ?>
                                    <a href="<?= e($act['certificado_url']) ?>" target="_blank" rel="noopener">Ver PDF</a>
                                <?php else: ?>
                                    <span class="perfil-tabla__vacio">Sin certificado</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($act['institucion'] ?? '') ?></td>
                            <td><?= e($act['fecha'] ?? '') ?></td>
                            <td><?= e($act['lugar'] ?? '') ?></td>
                            <td><?= e($act['tipo'] ?? '') ?></td>
                            <td><?= e($act['horas'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
