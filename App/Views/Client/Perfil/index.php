<?php
/**
 * Vista de Mi Perfil Médico (Cliente / Solo lectura con pestañas).
 * Variables recibidas: $medico, $educacion, $cuotas, $actividades, $flash, $error.
 */
?>
<div class="perfil-medico">
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

    <header class="perfil__cabecera" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1>Mi Perfil Médico</h1>
            <p>Consulta tus datos profesionales, actividades académicas y estado de cuenta.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/index.php?c=solicitudes&a=crear" class="btn btn--secundario">Solicitar Cambio de Datos</a>
            <a href="/index.php?c=perfil&a=editar" class="btn btn--primario">Editar Contacto</a>
        </div>
    </header>

    <!-- Barra de pestañas -->
    <nav class="perfil-tabs-nav" aria-label="Secciones del perfil">
        <button type="button" class="perfil-tab-btn activo" data-tab="tab-general" id="tab-btn-general">
            Información General
        </button>
        <button type="button" class="perfil-tab-btn" data-tab="tab-educacion" id="tab-btn-educacion">
            Educación Continua
        </button>
        <button type="button" class="perfil-tab-btn" data-tab="tab-cuenta" id="tab-btn-cuenta">
            Estado de Cuenta
        </button>
    </nav>

    <!-- Panel 1: Información General -->
    <div class="perfil-tab-panel activo" id="tab-general">
        <div class="formulario__seccion">
            <legend>Datos Personales y de Identificación</legend>
            <div class="formulario__grid">
                <div class="campo">
                    <label>Nombre Completo</label>
                    <p><strong><?= e($medico['nombre_completo'] ?? (($medico['nombre'] ?? '') . ' ' . ($medico['apellido'] ?? ''))) ?></strong></p>
                </div>
                <div class="campo">
                    <label>Cédula</label>
                    <p><?= e($medico['cedula'] ?? '—') ?></p>
                </div>
                <div class="campo">
                    <label>Registro Médico / Idoneidad</label>
                    <p><strong><?= e($medico['numero_idoneidad'] ?? ($medico['registro_medico'] ?? '—')) ?></strong></p>
                </div>
                <div class="campo">
                    <label>Estado de Certificación</label>
                    <p>
                        <?php if (!empty($medico['certificacion_vigente'])): ?>
                            <span class="badge badge--ok">Certificación Vigente</span>
                        <?php else: ?>
                            <span class="badge badge--alerta">No Vigente</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="campo">
                    <label>Fecha de Nacimiento</label>
                    <p><?= e($medico['fecha_nacimiento'] ?? '—') ?></p>
                </div>
                <div class="campo">
                    <label>Celular</label>
                    <p><?= e($medico['celular'] ?? '—') ?></p>
                </div>
                <div class="campo">
                    <label>Teléfono Residencial</label>
                    <p><?= e($medico['telefono_residencial'] ?? '—') ?></p>
                </div>
                <div class="campo">
                    <label>Correo Personal</label>
                    <p><?= e($medico['email_personal'] ?? '—') ?></p>
                </div>
                <div class="campo campo--full">
                    <label>Dirección Residencial</label>
                    <p>
                        <?php
                        $dir = $medico['direccion'] ?? [];
                        if (is_array($dir)) {
                            $partes = array_filter([
                                $dir['calle'] ?? '',
                                $dir['edificio'] ?? '',
                                $dir['numero'] ?? '',
                                $dir['corregimiento'] ?? '',
                                $dir['distrito'] ?? '',
                                $dir['provincia'] ?? '',
                            ]);
                            echo e(implode(', ', $partes) ?: 'No especificada');
                        } else {
                            echo e((string) $dir);
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="formulario__seccion">
            <legend>Información Profesional y Lugar de Trabajo</legend>
            <div class="formulario__grid">
                <div class="campo">
                    <label>Lugar de Trabajo / Institución</label>
                    <p><?= e($medico['lugar_trabajo']['empresa_institucion'] ?? ($medico['empresa'] ?? '—')) ?></p>
                </div>
                <div class="campo">
                    <label>Cargo Laboral</label>
                    <p><?= e($medico['lugar_trabajo']['cargo'] ?? ($medico['cargo'] ?? '—')) ?></p>
                </div>
                <div class="campo">
                    <label>Teléfono Institucional</label>
                    <p><?= e($medico['lugar_trabajo']['telefono'] ?? '—') ?></p>
                </div>
                <div class="campo">
                    <label>Especialidades</label>
                    <p>
                        <?php
                        $esp = $medico['especialidades'] ?? '—';
                        echo e(is_array($esp) ? implode(', ', $esp) : (string) $esp);
                        ?>
                    </p>
                </div>
            </div>
            <p class="nota" style="margin-top: 15px; color: #64748b; font-size: 0.85rem;">
                <em>Nota: Para actualizar información profesional o académica sensible (lugar de trabajo, especialidades, idoneidad), utilice la opción «Solicitar Cambio de Datos».</em>
            </p>
        </div>
    </div>

    <!-- Panel 2: Educación Continua (Pestaña recuperada) -->
    <div class="perfil-tab-panel" id="tab-educacion">
        <?php require __DIR__ . '/_educacion_continua.php'; ?>
    </div>

    <!-- Panel 3: Estado de Cuenta (Pestaña recuperada) -->
    <div class="perfil-tab-panel" id="tab-cuenta">
        <?php require __DIR__ . '/_estado_cuenta.php'; ?>
    </div>
</div>
