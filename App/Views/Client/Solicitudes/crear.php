<?php
/**
 * Formulario para Solicitar Cambio de Información Médica (Médico).
 * Variables: $csrf, $flash.
 */
?>
<div class="perfil-medico">
    <?php if (!empty($flash)): ?>
        <div class="alerta alerta--<?= e($flash['tipo'] === 'error' ? 'peligro' : 'exito') ?>" style="margin-bottom: 20px;">
            <?= e($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <header class="perfil__cabecera" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h1>Solicitar Cambio de Información Profesional</h1>
            <p>Envía una petición al Administrador del Colegio Médico de Panamá para modificar datos de tu registro.</p>
        </div>
        <a href="/index.php?c=perfil&a=index" class="btn btn--secundario">Volver al Perfil</a>
    </header>

    <form action="/index.php?c=solicitudes&a=guardar" method="post" class="formulario">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

        <fieldset class="formulario__seccion">
            <legend>Detalle de la Solicitud</legend>
            <div class="formulario__grid">
                <div class="campo campo--full">
                    <label for="campo">Campo que deseas modificar *</label>
                    <select id="campo" name="campo" required>
                        <option value="">Selecciona una opción...</option>
                        <option value="lugar_trabajo">Lugar de Trabajo / Institución</option>
                        <option value="cargo">Cargo Laboral</option>
                        <option value="telefono_institucional">Teléfono Institucional</option>
                        <option value="especialidad">Nueva Especialidad o Subespecialidad</option>
                        <option value="asociacion">Asociación Médica Afiliada</option>
                        <option value="educacion">Nueva Actividad de Educación Continua</option>
                        <option value="otro">Otro cambio administrativo</option>
                    </select>
                </div>

                <div class="campo campo--full">
                    <label for="valor_nuevo">Nuevo Valor Solicitado *</label>
                    <input type="text" id="valor_nuevo" name="valor_nuevo" placeholder="Ej: Hospital Punta Pacífica, Especialista en Cardiología" required>
                </div>

                <div class="campo campo--full">
                    <label for="motivo">Motivo o Justificación del Cambio</label>
                    <textarea id="motivo" name="motivo" rows="4" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px;" placeholder="Describe brevemente el motivo o adjunta referencias necesarias para el CMP."></textarea>
                </div>
            </div>
        </fieldset>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
            <a href="/index.php?c=perfil&a=index" class="btn btn--secundario">Cancelar</a>
            <button type="submit" class="btn btn--primario">Enviar Solicitud al CMP</button>
        </div>
    </form>
</div>
