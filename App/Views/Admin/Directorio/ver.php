<?php
/**
 * Perfil público de un médico (Directorio).
 * Solo contenido: DirectorioController::render() arma el layout.
 * Variables: $medico, $errorGeneral, $entrada (URL de index.php)
 */
$entrada = $entrada ?? 'index.php';
$volver  = htmlspecialchars($entrada . '?c=directorio&a=index', ENT_QUOTES, 'UTF-8');
$avatar  = htmlspecialchars(rtrim(dirname($entrada), '/') . '/Assets/IMG/ICONS/usuario.png', ENT_QUOTES, 'UTF-8');
// Si por alguna razón no llega el médico, mostramos un error
if (!isset($medico) || empty($medico)) {
    $mensaje = $errorGeneral ?? 'No se pudo cargar la información del médico.';
    echo "<div class='alerta alerta--error'>" . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</div>';
    echo "<a href='" . $volver . "' class='btn btn--primario'>Volver al directorio</a>";
    return;
}
?>

<div class="perfil-publico">
    
    <!-- Botón para regresar -->
    <div class="perfil-publico__acciones">
        <a href="<?= $volver ?>" class="btn btn--secundario">&larr; Volver al Directorio</a>
    </div>

    <!-- Encabezado del Perfil -->
    <header class="perfil-header">
        <div class="perfil-header__foto-contenedor">
            <img src="<?= !empty($medico['foto']) ? htmlspecialchars($medico['foto']) : $avatar ?>" alt="Foto del Dr/Dra" class="perfil-header__foto">
        </div>
        <div class="perfil-header__info">
            <h1 class="perfil-header__nombre">Dr(a). <?= htmlspecialchars(trim(($medico['nombre'] ?? '') . ' ' . ($medico['apellido'] ?? ''))) ?></h1>
            <p class="perfil-header__especialidad"><?= htmlspecialchars($medico['especialidad'] ?? 'Especialidad no especificada') ?></p>
            
            <div class="perfil-header__badges">
                <?php if (isset($medico['vigente']) && $medico['vigente']): ?>
                    <span class="etiqueta etiqueta--vigente">Certificación Vigente</span>
                <?php else: ?>
                    <span class="etiqueta etiqueta--no-vigente">Certificación No Vigente</span>
                <?php endif; ?>
                
                <?php if (!empty($medico['provincia'])): ?>
                    <span class="etiqueta etiqueta--neutral"> <?= htmlspecialchars($medico['provincia']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Contenido en cuadrícula (Grid) -->
    <div class="perfil-grid">
        
        <!-- Tarjeta: Información Laboral (Pública) -->
        <section class="tarjeta-info">
            <h2 class="tarjeta-info__titulo">Información de Contacto Laboral</h2>
            <ul class="lista-datos">
                <li>
                    <strong>Lugar de trabajo:</strong> 
                    <span><?= htmlspecialchars($medico['empresa_institucion'] ?? 'No registrado') ?></span>
                </li>
                <li>
                    <strong>Dirección laboral:</strong> 
                    <span><?= htmlspecialchars($medico['direccion_laboral'] ?? 'No registrada') ?></span>
                </li>
                <li>
                    <strong>Teléfono de consultorio:</strong> 
                    <span><?= htmlspecialchars($medico['telefono_trabajo'] ?? 'No registrado') ?></span>
                </li>
                <li>
                    <strong>Correo laboral:</strong> 
                    <span><a href="mailto:<?= htmlspecialchars($medico['email_laboral'] ?? '') ?>"><?= htmlspecialchars($medico['email_laboral'] ?? 'No registrado') ?></a></span>
                </li>
            </ul>
        </section>

        <!-- Tarjeta: Credenciales Médicas -->
        <section class="tarjeta-info">
            <h2 class="tarjeta-info__titulo">Credenciales Profesionales</h2>
            <ul class="lista-datos">
                <li>
                    <strong>Registro Médico:</strong> 
                    <span><?= htmlspecialchars($medico['registro_medico'] ?? 'N/A') ?></span>
                </li>
                <li>
                    <strong>Subespecialidad:</strong> 
                    <span><?= htmlspecialchars($medico['subespecialidad'] ?? 'Ninguna') ?></span>
                </li>
                <li>
                    <strong>Asociaciones/Gremios:</strong> 
                    <span><?= htmlspecialchars($medico['organizaciones'] ?? 'No registradas') ?></span>
                </li>
            </ul>
        </section>

    </div>
</div>