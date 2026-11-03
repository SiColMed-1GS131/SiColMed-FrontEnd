<?php
$currentPage = 'perfil';
require_once __DIR__ . '/../../Partials/header.php';
?>

<link rel="stylesheet" href="/Assets/CSS/Dedicated/perfil.css">


<div class="app-layout">
    <?php require_once __DIR__ . '/../../Partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="perfil-medico">
            <header class="perfil__cabecera" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Mi Perfil Médico</h1>
                    <p>Vista general de tus datos registrados.</p>
                </div>
                <!-- Botón que lleva a la pantalla de edición -->
                <a href="/index.php?c=perfil&a=editar" class="btn btn--primario">Editar Perfil</a>
            </header>

            <?php
            // Datos reales de PerfilController::index (GET /medicos/{id} del médico con sesión)
            $medico = $medico ?? [];
            $v = static fn(string $k): string => htmlspecialchars(trim((string) ($medico[$k] ?? '')) ?: 'No registrado', ENT_QUOTES, 'UTF-8');
            $nombreCompleto = trim(implode(' ', array_filter([$medico['nombre'] ?? '', $medico['segundo_nombre'] ?? '',
                                                              $medico['apellido_paterno'] ?? '', $medico['apellido_materno'] ?? ''])));
            $nacimiento = ($t = strtotime((string) ($medico['fecha_nacimiento'] ?? ''))) ? date('d/m/Y', $t) : '';
            $contacto = implode(' | ', array_filter([
                !empty($medico['celular']) ? 'Cel: ' . $medico['celular'] : null,
                !empty($medico['telefono_residencial']) ? 'Tel: ' . $medico['telefono_residencial'] : null,
            ]));
            ?>
            <?php if (!empty($errorPerfil)): ?>
                <div class="alerta alerta--error" role="alert"><?= htmlspecialchars($errorPerfil, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <!-- Datos en modo lectura (Sin inputs) -->
            <div class="formulario__seccion">
                <legend>Información Personal</legend>
                <!-- Foto subida con POST /medicos/{id}/foto; sin foto se usa el avatar por defecto -->
                <img src="<?= htmlspecialchars(($medico['foto_url'] ?? '') ?: '/Assets/IMG/ICONS/usuario.png', ENT_QUOTES, 'UTF-8') ?>"
                     alt="Foto de perfil" width="120" height="120" style="object-fit: cover; border-radius: 50%;">
                <div class="formulario__grid">
                    <div class="campo">
                        <label>Nombre Completo</label>
                        <p><?= htmlspecialchars($nombreCompleto ?: 'No registrado', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="campo">
                        <label>Cédula</label>
                        <p><?= $v('cedula') ?></p>
                    </div>
                    <div class="campo">
                        <label>Número de Registro Médico</label>
                        <p><?= $v('registro_medico') ?></p>
                    </div>
                    <div class="campo">
                        <label>Fecha de Nacimiento</label>
                        <p><?= htmlspecialchars($nacimiento ? $nacimiento . (isset($medico['edad']) ? ' (' . $medico['edad'] . ' años)' : '') : 'No registrada', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="campo campo--full">
                        <label>Dirección Residencial</label>
                        <p><?= $v('direccion_residencial') ?></p>
                    </div>
                    <div class="campo">
                        <label>Contacto</label>
                        <p><?= htmlspecialchars($contacto ?: 'No registrado', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="campo">
                        <label>Email personal</label>
                        <p><?= $v('email_personal') ?></p>
                    </div>
                </div>
            </div>

            <!-- Certificado de idoneidad: enlace solo si ya hay un PDF subido -->
            <div class="formulario__seccion">
                <legend>Certificado de idoneidad</legend>
                <?php if (!empty($certificado['url'])): ?>
                    <p><a href="<?= htmlspecialchars($certificado['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Ver / descargar certificado (PDF)</a></p>
                <?php else: ?>
                    <p>Todavía no has subido tu certificado. Puedes hacerlo desde «Editar Perfil».</p>
                <?php endif; ?>
            </div>


        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../Partials/footer.php'; ?>