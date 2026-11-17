<?php
/**
 * Formulario del médico para pedir cambios en su perfil.
 * Variables: $perfil, $errorApi, $catalogos, $csrf, $flash.
 *
 * Solo se envían los campos que el médico modifique; el controlador compara con
 * el perfil actual. Las secciones de registros existentes no crean filas nuevas:
 * la API procesa cambios sobre registros ya registrados.
 */
$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fecha = static fn($v): string => substr((string) $v, 0, 10);
$registrosPorSeccion = [
    'lugar_trabajo' => ['perfil' => 'lugares_trabajo', 'titulo' => 'Lugar de trabajo', 'id' => 'id_lugar_trabajo'],
    'estudio_medico' => ['perfil' => 'estudios', 'titulo' => 'Estudios', 'id' => 'id_estudio'],
    'certificaciones' => ['perfil' => 'certificaciones', 'titulo' => 'Certificaciones', 'id' => 'id_certificacion'],
];
$relaciones = [
    'especialidades' => ['perfil' => 'especialidades', 'id' => 'id_especialidad', 'titulo' => 'Especialidades'],
    'subespecialidades' => ['perfil' => 'subespecialidades', 'id' => 'id_subespecialidad', 'titulo' => 'Subespecialidades'],
    'asociaciones' => ['perfil' => 'asociaciones', 'id' => 'id_asociacion', 'titulo' => 'Asociaciones'],
];
?>
<section class="panel">
    <header class="panel__cabecera">
        <div>
            <h1 class="panel__titulo">Solicitar cambio de datos</h1>
            <p class="panel__subtitulo">Edita solo lo que necesites. El Colegio revisará cada cambio antes de aplicarlo.</p>
        </div>
    </header>

    <?php if (!empty($flash)): ?>
        <div class="alerta alerta--<?= $h($flash['tipo']) ?>" role="status"><?= $h($flash['texto']) ?></div>
    <?php endif; ?>

    <?php if ($errorApi !== ''): ?>
        <div class="alerta alerta--error" role="alert">
            <strong>No se pudo cargar tu perfil.</strong> <?= $h($errorApi) ?>
        </div>
    <?php else: ?>
    <form method="post" action="/index.php?c=solicitudes&amp;a=crear" class="solicitud__form" novalidate>
        <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">

        <?php foreach ($registrosPorSeccion as $seccion => $meta):
            $lista = is_array($perfil[$meta['perfil']] ?? null) ? $perfil[$meta['perfil']] : [];
        ?>
        <section class="panel__tarjeta solicitud__seccion">
            <h2 class="solicitud__subtitulo"><?= $h($meta['titulo']) ?></h2>
            <?php if ($lista === []): ?>
                <p class="field__ayuda">No tienes registros en esta sección.</p>
            <?php else: ?>
                <?php foreach (array_values($lista) as $indice => $registro): ?>
                <fieldset class="solicitud__registro">
                    <legend class="solicitud__meta">Registro <?= $h($registro[$meta['id']] ?? '') ?></legend>

                    <?php if ($seccion === 'lugar_trabajo'): ?>
                        <div class="field"><label>Institución / empresa
                            <input type="text" name="lugar_trabajo[<?= $h((string) $indice) ?>][empresa_institucion]" value="<?= $h($registro['empresa_institucion'] ?? '') ?>" maxlength="150"></label></div>
                        <div class="field"><label>Cargo
                            <input type="text" name="lugar_trabajo[<?= $h((string) $indice) ?>][cargo]" value="<?= $h($registro['cargo'] ?? '') ?>" maxlength="100"></label></div>
                        <div class="field"><label>Teléfono de trabajo
                            <input type="text" name="lugar_trabajo[<?= $h((string) $indice) ?>][telefono_trabajo]" value="<?= $h($registro['telefono_trabajo'] ?? '') ?>" maxlength="30"></label></div>
                        <div class="field"><label>Correo laboral
                            <input type="email" name="lugar_trabajo[<?= $h((string) $indice) ?>][email_laboral]" value="<?= $h($registro['email_laboral'] ?? '') ?>" maxlength="150"></label></div>

                    <?php elseif ($seccion === 'estudio_medico'): ?>
                        <div class="field"><label>Tipo de estudio
                            <input type="text" name="estudio_medico[<?= $h((string) $indice) ?>][tipo_estudio]" value="<?= $h($registro['tipo_estudio'] ?? '') ?>" maxlength="100"></label></div>
                        <div class="field"><label>Título
                            <input type="text" name="estudio_medico[<?= $h((string) $indice) ?>][titulo]" value="<?= $h($registro['titulo'] ?? '') ?>" maxlength="150"></label></div>
                        <div class="field"><label>Institución
                            <input type="text" name="estudio_medico[<?= $h((string) $indice) ?>][institucion]" value="<?= $h($registro['institucion'] ?? '') ?>" maxlength="150"></label></div>
                        <div class="field"><label>Lugar
                            <input type="text" name="estudio_medico[<?= $h((string) $indice) ?>][lugar]" value="<?= $h($registro['lugar'] ?? '') ?>" maxlength="100"></label></div>
                        <div class="field"><label>Fecha
                            <input type="date" name="estudio_medico[<?= $h((string) $indice) ?>][fecha]" value="<?= $h($fecha($registro['fecha'] ?? '')) ?>"></label></div>

                    <?php else: ?>
                        <div class="field"><label>Vigente
                            <select name="certificaciones[<?= $h((string) $indice) ?>][vigente]">
                                <option value="1" <?= !empty($registro['vigente']) ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= empty($registro['vigente']) ? 'selected' : '' ?>>No</option>
                            </select></label></div>
                        <div class="field"><label>Fecha de obtención
                            <input type="date" name="certificaciones[<?= $h((string) $indice) ?>][fecha_obtencion]" value="<?= $h($fecha($registro['fecha_obtencion'] ?? '')) ?>"></label></div>
                        <div class="field"><label>Puntaje
                            <input type="number" name="certificaciones[<?= $h((string) $indice) ?>][puntaje]" value="<?= $h($registro['puntaje'] ?? '') ?>" min="0" step="1"></label></div>
                        <div class="field"><label>Fecha de expiración
                            <input type="date" name="certificaciones[<?= $h((string) $indice) ?>][fecha_expiracion]" value="<?= $h($fecha($registro['fecha_expiracion'] ?? '')) ?>"></label></div>
                    <?php endif; ?>
                </fieldset>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>

        <?php foreach ($relaciones as $seccion => $meta):
            $actuales = array_map(
                static fn($fila) => is_array($fila) ? (int) ($fila[$meta['id']] ?? 0) : 0,
                is_array($perfil[$meta['perfil']] ?? null) ? $perfil[$meta['perfil']] : []
            );
            $opciones = is_array($catalogos[$seccion] ?? null) ? $catalogos[$seccion] : [];
        ?>
        <section class="panel__tarjeta solicitud__seccion">
            <h2 class="solicitud__subtitulo"><?= $h($meta['titulo']) ?></h2>
            <?php if ($opciones === []): ?>
                <p class="field__ayuda">No hay opciones disponibles en este momento.</p>
            <?php else: ?>
                <p class="field__ayuda">Marca lo que debe quedar en tu perfil. Lo que desmarques se quitará.</p>
                <div class="solicitud__checks">
                    <?php foreach ($opciones as $opcion): ?>
                        <?php $id = (int) ($opcion['id'] ?? 0); ?>
                        <label class="solicitud__check">
                            <input type="checkbox" name="<?= $h($seccion) ?>[]" value="<?= $h((string) $id) ?>"
                                <?= in_array($id, $actuales, true) ? 'checked' : '' ?>>
                            <?= $h($opcion['nombre'] ?? '') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>

        <p class="field__ayuda">Solo se enviarán los campos que hayas modificado; los campos vacíos se ignoran.</p>
        <button type="submit" class="btn">Enviar solicitud</button>
    </form>
    <?php endif; ?>
</section>
