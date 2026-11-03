<?php
/**
 * Perfil público de un médico (Directorio).
 * Solo contenido: DirectorioController::render() arma el layout.
 * Variables: $medico, $errorGeneral, $entrada (URL de index.php)
 */
$entrada = $entrada ?? 'index.php';
$volver  = htmlspecialchars($entrada . '?c=directorio&a=' . ($accionIndex ?? 'index'), ENT_QUOTES, 'UTF-8');
$avatar  = htmlspecialchars(rtrim(dirname($entrada), '/') . '/Assets/IMG/ICONS/usuario.png', ENT_QUOTES, 'UTF-8');
// Si por alguna razón no llega el médico, mostramos un error
if (!isset($medico) || empty($medico)) {
    $mensaje = $errorGeneral ?? 'No se pudo cargar la información del médico.';
    echo "<div class='alerta alerta--error'>" . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</div>';
    echo "<a href='" . $volver . "' class='btn btn--primario'>Volver al directorio</a>";
    return;
}
// Sin sesión la API solo manda la ficha pública: las filas de contacto y registro
// médico se muestran únicamente si el dato llegó (aunque sea vacío).
$hay = static fn(string $clave): bool => array_key_exists($clave, $medico);
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
                    <span><?= htmlspecialchars(($medico['empresa_institucion'] ?? '') ?: 'No registrado') ?></span>
                </li>
                <?php if ($hay('direccion_laboral')): ?>
                <li>
                    <strong>Dirección laboral:</strong>
                    <span><?= htmlspecialchars($medico['direccion_laboral'] ?? 'No registrada') ?></span>
                </li>
                <?php endif; ?>
                <?php if ($hay('telefono_trabajo')): ?>
                <li>
                    <strong>Teléfono de consultorio:</strong>
                    <span><?= htmlspecialchars($medico['telefono_trabajo'] ?: 'No registrado') ?></span>
                </li>
                <?php endif; ?>
                <?php if ($hay('email_laboral')): ?>
                <li>
                    <strong>Correo laboral:</strong>
                    <span><a href="mailto:<?= htmlspecialchars($medico['email_laboral'] ?? '') ?>"><?= htmlspecialchars($medico['email_laboral'] ?: 'No registrado') ?></a></span>
                </li>
                <?php endif; ?>
            </ul>
        </section>

        <!-- Tarjeta: Credenciales Médicas -->
        <section class="tarjeta-info">
            <h2 class="tarjeta-info__titulo">Credenciales Profesionales</h2>
            <ul class="lista-datos">
                <?php if (!empty($medico['numero_idoneidad'])): ?>
                <li>
                    <strong>N.º de idoneidad:</strong>
                    <span><?= htmlspecialchars($medico['numero_idoneidad']) ?></span>
                </li>
                <?php endif; ?>
                <?php if (!empty($medico['certificado_pdf'])): ?>
                <?php // Solo llega con el perfil completo (admin o el propio médico); la ficha pública no lo trae
                $urlCertificado = \Auth::es(\Auth::ADMIN)
                    ? $entrada . '?' . http_build_query(['c' => 'registro', 'a' => 'certificado', 'id' => $medico['id'] ?? 0])
                    : $entrada . '?c=perfil&a=certificado'; ?>
                <li>
                    <strong>Certificado de idoneidad:</strong>
                    <span><a href="<?= htmlspecialchars($urlCertificado, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Ver PDF</a></span>
                </li>
                <?php endif; ?>
                <?php if ($hay('registro_medico')): ?>
                <li>
                    <strong>Registro Médico:</strong>
                    <span><?= htmlspecialchars($medico['registro_medico'] ?: 'N/A') ?></span>
                </li>
                <?php endif; ?>
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