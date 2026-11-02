<?php
/**
 * Vista de Edición de Mi Perfil Médico.
 * Solo permite modificar información no sensible (contacto y dirección).
 * Variables recibidas: $medico, $provincias, $distritos, $corregimientos, $csrf, $flash.
 */
$dir = $medico['direccion'] ?? [];
?>
<div class="perfil-medico">
    <?php if (!empty($flash)): ?>
        <div class="alerta alerta--<?= e($flash['tipo'] === 'error' ? 'peligro' : 'exito') ?>" style="margin-bottom: 20px;">
            <?= e($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <header class="perfil__cabecera" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1>Editar Información de Contacto</h1>
            <p>Actualiza tus teléfonos, correo electrónico y dirección residencial.</p>
        </div>
        <a href="/index.php?c=perfil&a=index" class="btn btn--secundario">Cancelar</a>
    </header>

    <form action="/index.php?c=perfil&a=actualizar" method="post" class="formulario">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

        <!-- Información de Identificación (Solo lectura) -->
        <fieldset class="formulario__seccion">
            <legend>Información Personal (Solo Lectura)</legend>
            <div class="formulario__grid">
                <div class="campo">
                    <label>Nombre Completo</label>
                    <input type="text" value="<?= e($medico['nombre_completo'] ?? (($medico['nombre'] ?? '') . ' ' . ($medico['apellido'] ?? ''))) ?>" readonly class="input-readonly">
                </div>
                <div class="campo">
                    <label>Cédula</label>
                    <input type="text" value="<?= e($medico['cedula'] ?? '—') ?>" readonly class="input-readonly">
                </div>
                <div class="campo">
                    <label>Número de Idoneidad</label>
                    <input type="text" value="<?= e($medico['numero_idoneidad'] ?? ($medico['registro_medico'] ?? '—')) ?>" readonly class="input-readonly">
                </div>
            </div>
            <p class="nota" style="margin-top: 10px; font-size: 0.85rem; color: #64748b;">
                <em>Los datos de identificación y registro oficial solo pueden ser modificados por el Administrador del CMP previa solicitud formal.</em>
            </p>
        </fieldset>

        <!-- Información de Contacto (Editable) -->
        <fieldset class="formulario__seccion">
            <legend>Información de Contacto Personal (Modificable)</legend>
            <div class="formulario__grid">
                <div class="campo">
                    <label for="celular">Teléfono Celular *</label>
                    <input type="tel" id="celular" name="celular" value="<?= e($medico['celular'] ?? '') ?>" required>
                </div>
                <div class="campo">
                    <label for="telefono_residencial">Teléfono Residencial</label>
                    <input type="tel" id="telefono_residencial" name="telefono_residencial" value="<?= e($medico['telefono_residencial'] ?? '') ?>">
                </div>
                <div class="campo">
                    <label for="email_personal">Correo Electrónico Personal *</label>
                    <input type="email" id="email_personal" name="email_personal" value="<?= e($medico['email_personal'] ?? '') ?>" required>
                </div>
            </div>
        </fieldset>

        <!-- Dirección Residencial (Editable) -->
        <fieldset class="formulario__seccion">
            <legend>Dirección Residencial</legend>
            <div class="formulario__grid">
                <div class="campo">
                    <label for="id_provincia">Provincia</label>
                    <select id="id_provincia" name="id_provincia">
                        <option value="">Seleccione una provincia</option>
                        <?php foreach ($provincias as $p): ?>
                            <option value="<?= e($p['id'] ?? $p['id_provincia'] ?? '') ?>"
                                <?= (!empty($dir['id_provincia']) && ($dir['id_provincia'] == ($p['id'] ?? $p['id_provincia'] ?? ''))) ? 'selected' : '' ?>>
                                <?= e($p['nombre'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="id_distrito">Distrito</label>
                    <select id="id_distrito" name="id_distrito">
                        <option value="">Seleccione un distrito</option>
                        <?php foreach ($distritos as $d): ?>
                            <option value="<?= e($d['id'] ?? $d['id_distrito'] ?? '') ?>"
                                <?= (!empty($dir['id_distrito']) && ($dir['id_distrito'] == ($d['id'] ?? $d['id_distrito'] ?? ''))) ? 'selected' : '' ?>>
                                <?= e($d['nombre'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="id_corregimiento">Corregimiento</label>
                    <select id="id_corregimiento" name="id_corregimiento">
                        <option value="">Seleccione un corregimiento</option>
                        <?php foreach ($corregimientos as $c): ?>
                            <option value="<?= e($c['id'] ?? $c['id_corregimiento'] ?? '') ?>"
                                <?= (!empty($dir['id_corregimiento']) && ($dir['id_corregimiento'] == ($c['id'] ?? $c['id_corregimiento'] ?? ''))) ? 'selected' : '' ?>>
                                <?= e($c['nombre'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="calle">Calle / Avenida</label>
                    <input type="text" id="calle" name="calle" value="<?= e($dir['calle'] ?? '') ?>">
                </div>
                <div class="campo">
                    <label for="edificio">Edificio / PH / Residencial</label>
                    <input type="text" id="edificio" name="edificio" value="<?= e($dir['edificio'] ?? '') ?>">
                </div>
                <div class="campo">
                    <label for="numero">Número de Casa o Apartamento</label>
                    <input type="text" id="numero" name="numero" value="<?= e($dir['numero'] ?? '') ?>">
                </div>
            </div>
        </fieldset>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
            <a href="/index.php?c=perfil&a=index" class="btn btn--secundario">Cancelar</a>
            <button type="submit" class="btn btn--primario">Guardar Cambios</button>
        </div>
    </form>
</div>
