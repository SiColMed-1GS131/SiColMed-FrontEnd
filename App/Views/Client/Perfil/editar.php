<?php
$currentPage = 'perfil';
require_once __DIR__ . '/../../Partials/header.php';

// Datos de PerfilController::editar: $medico (GET /medicos/{id} del médico con sesión),
// $direccion (opciones de los selects) y $errorPerfil
$medico = $medico ?? [];
$direccion = $direccion ?? [];
$certificado = $certificado ?? ['url' => null, 'flash' => null, 'csrf' => '', 'foto_url' => null];
$v = static fn(string $campo): string => e($medico[$campo] ?? '');
$estudio = $medico['educacion']['basico'][0] ?? [];
// Mensaje del guardado: arriba del formulario. El de foto y certificado sigue junto a sus formularios
$flash = $certificado['flash'];
$flashDatos = ($flash['seccion'] ?? '') === 'datos';
// Opciones de un select de dirección, con la actual preseleccionada
$opcionesDireccion = static function (array $lista, string $campo) use ($medico): void {
    foreach ($lista as $item) {
        $elegida = (string) $item['id'] === (string) ($medico[$campo] ?? '') ? ' selected' : '';
        echo '<option value="' . e($item['id']) . '"' . $elegida . '>' . e($item['nombre']) . '</option>';
    }
};
?>

<link rel="stylesheet" href="/Assets/CSS/Dedicated/perfil.css">
<div class="app-layout">

    <?php
    require_once __DIR__ . '/../../Partials/sidebar.php';
    ?>

    <main class="main-content">

        <div class="perfil-medico">
            <header class="perfil__cabecera">
                <h1>Mi Perfil Médico</h1>
                <p>Consulta y actualiza tu información profesional.</p>
            </header>

            <?php if (!empty($errorPerfil)): ?>
                <div class="alerta alerta--error" role="alert"><?= e($errorPerfil) ?></div>
            <?php endif; ?>

            <?php if ($flash && $flashDatos): ?>
                <div class="alerta alerta--<?= $flash['tipo'] === 'success' ? 'exito' : 'error' ?>" role="status">
                    <?= e($flash['texto']) ?>
                </div>
            <?php endif; ?>

        <!-- Formulario Base del usuario general: PerfilController::actualizar → PATCH /medicos/{id}/perfil -->
        <form id="form-perfil" action="/index.php?c=perfil&amp;a=actualizar" method="post" class="formulario"
              data-catalogo="/index.php?c=perfil&amp;a=catalogo">
                <input type="hidden" name="csrf" value="<?= e($certificado['csrf']) ?>">

                <fieldset class="formulario__seccion">
                    <legend>Información Personal</legend>
                    <div class="formulario__grid">
                        <div class="campo">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" value="<?= $v('nombre') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="segundo_nombre">Segundo Nombre</label>
                            <input type="text" id="segundo_nombre" name="segundo_nombre" value="<?= $v('segundo_nombre') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="apellido_paterno">Apellido Paterno</label>
                            <input type="text" id="apellido_paterno" name="apellido" value="<?= $v('apellido_paterno') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="apellido_materno">Apellido Materno</label>
                            <input type="text" id="apellido_materno" name="segundo_apellido" value="<?= $v('apellido_materno') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="cedula">Cédula</label>
                            <input type="text" id="cedula" name="cedula" value="<?= $v('cedula') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="registro_medico">Número de Registro Médico</label>
                            <input type="text" id="registro_medico" name="registro_medico" value="<?= $v('registro_medico') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= $v('fecha_nacimiento') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="edad">Edad</label>
                            <input type="number" id="edad" name="edad" value="<?= $v('edad') ?>" readonly class="input-readonly">
                        </div>

                        <!-- Campos editables de contacto (la foto se sube en su propio formulario, abajo) -->
                        <div class="campo campo--full">
                            <label>Dirección Residencial</label>
                            <div class="direccion-grid">
                                <select id="residencia_provincia" name="residencia_provincia" aria-label="Provincia" style="width: 100%;"
                                        data-direccion="la provincia" data-cascada="distritos" data-destino="residencia_distrito">
                                    <option value="">Provincia</option>
                                    <?php $opcionesDireccion($direccion['provincias'] ?? [], 'residencia_provincia'); ?>
                                </select>
                                <select id="residencia_distrito" name="residencia_distrito" aria-label="Distrito" style="width: 100%;"
                                        data-direccion="el distrito" data-cascada="corregimientos" data-destino="residencia_corregimiento"<?= empty($direccion['distritos']) ? ' disabled' : '' ?>>
                                    <option value="">Distrito</option>
                                    <?php $opcionesDireccion($direccion['distritos'] ?? [], 'residencia_distrito'); ?>
                                </select>
                                <select id="residencia_corregimiento" name="residencia_corregimiento" aria-label="Corregimiento" style="width: 100%;"
                                        data-direccion="el corregimiento"<?= empty($direccion['corregimientos']) ? ' disabled' : '' ?>>
                                    <option value="">Corregimiento</option>
                                    <?php $opcionesDireccion($direccion['corregimientos'] ?? [], 'residencia_corregimiento'); ?>
                                </select>
                                <input type="text" name="residencia_calle" placeholder="Calle" value="<?= $v('residencia_calle') ?>" required>
                                <input type="text" name="residencia_edificio" placeholder="Edificio" value="<?= $v('residencia_edificio') ?>">
                                <input type="text" name="residencia_numero" placeholder="Número (Apto/Casa)" value="<?= $v('residencia_numero') ?>" required>
                            </div>
                            <p id="perfil-aviso-direccion" class="alerta alerta--error" role="alert" hidden></p>
                        </div>
                        <div class="campo">
                            <label for="telefono_residencial">Teléfono residencial</label>
                            <input type="tel" id="telefono_residencial" name="telefono_residencial" value="<?= $v('telefono_residencial') ?>">
                        </div>
                        <div class="campo">
                            <label for="celular">Celular</label>
                            <input type="tel" id="celular" name="celular" value="<?= $v('celular') ?>">
                        </div>
                        <div class="campo">
                            <label for="email_personal">Email personal</label>
                            <input type="email" id="email_personal" name="email_personal" value="<?= $v('email_personal') ?>">
                        </div>
                        <div class="campo">
                            <label for="apartado_postal">Apartado postal</label>
                            <input type="text" id="apartado_postal" name="apartado_postal" value="<?= $v('apartado_postal') ?>" required>
                        </div>
                    </div>
                </fieldset>


                <fieldset class="formulario__seccion">
                    <legend>Educación</legend>

                    <div class="formulario__grid">
                        <div class="campo">
                            <label>Título Básico</label>
                            <input type="text" value="<?= e($estudio['titulo'] ?? '') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Institución</label>
                            <input type="text" value="<?= e($estudio['institucion'] ?? '') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Lugar</label>
                            <input type="text" value="<?= e($estudio['lugar'] ?? '') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Fecha</label>
                            <input type="date" value="<?= e($estudio['fecha'] ?? '') ?>" readonly class="input-readonly">
                        </div>
                    </div>
                    <p class="nota"><em>Nota: Para agregar o modificar especialidades y sub-especialidades, contacte al CMP.</em></p>
                </fieldset>


                <fieldset class="formulario__seccion">
                    <legend>Información Laboral</legend>
                    <div class="formulario__grid">
                        <div class="campo campo--full">
                            <label for="empresa">Empresa o Institución</label>
                            <input type="text" id="empresa" name="empresa" value="<?= $v('empresa') ?>" disabled>
                        </div>
                        <div class="campo">
                            <label for="cargo">Cargo</label>
                            <input type="text" id="cargo" name="cargo" value="<?= $v('cargo') ?>" disabled>
                        </div>
                        <div class="campo">
                            <label for="tel_trabajo">Teléfono de trabajo</label>
                            <input type="tel" id="tel_trabajo" name="tel_trabajo" value="<?= $v('telefono_trabajo') ?>" disabled>
                        </div>
                        <div class="campo">
                            <label for="email_laboral">Email laboral</label>
                            <input type="email" id="email_laboral" name="email_laboral" value="<?= $v('email_laboral') ?>" disabled>
                        </div>
                        <div class="campo campo--full">
                            <label>Dirección Laboral</label>
                            <textarea name="dir_laboral" rows="2" disabled><?= $v('direccion_laboral') ?></textarea>
                        </div>
                    </div>
                    <p class="nota"><em>Nota: La información laboral todavía no se puede editar desde aquí. Si necesitas cambiarla, contacta al CMP.</em></p>
                </fieldset>


                <fieldset class="formulario__seccion">
                    <legend>Organizaciones a las que pertenece</legend>
                    <div class="formulario__grid">
                        <div class="campo campo--full">
                            <label for="asociaciones">Asociaciones</label>
                            <input type="text" id="asociaciones" value="<?= $v('organizaciones') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="profesor">Profesor Universitario</label>
                            <input type="text" id="profesor" name="profesor" disabled>
                        </div>
                        <div class="campo">
                            <label for="gremios">Gremios</label>
                            <input type="text" id="gremios" name="gremios" disabled>
                        </div>
                        <div class="campo">
                            <label for="sociedades">Sociedades</label>
                            <input type="text" id="sociedades" name="sociedades" disabled>
                        </div>
                        <div class="campo">
                            <label for="clubes">Clubes Cívicos</label>
                            <input type="text" id="clubes" name="clubes" disabled>
                        </div>
                        <div class="campo campo--full">
                            <label for="otras_org">Otros...</label>
                            <input type="text" id="otras_org" name="otras_org" disabled>
                        </div>
                    </div>
                    <p class="nota"><em>Nota: Esta información todavía no se puede editar desde aquí. Para cambiar tus asociaciones, contacta al CMP.</em></p>
                </fieldset>


                <fieldset class="formulario__seccion">
                    <legend>Información de Certificación (Consulta)</legend>
                    <div class="formulario__grid">
                        <div class="campo">
                            <label>Estado</label>
                            <?php if (($medico['cert_vigente'] ?? '') === 'vigente'): ?>
                                <span class="badge badge--vigente">Vigente</span>
                            <?php elseif (($medico['cert_vigente'] ?? '') === 'no_vigente'): ?>
                                <span class="badge">No vigente</span>
                            <?php else: ?>
                                <span class="badge">Sin certificación</span>
                            <?php endif; ?>
                        </div>
                        <div class="campo">
                            <label>Fecha en que se obtuvo</label>
                            <input type="date" value="<?= $v('cert_fecha_obtencion') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Puntaje</label>
                            <input type="number" value="<?= $v('cert_puntaje') ?>" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Fecha de expiración</label>
                            <input type="date" value="<?= $v('cert_fecha_expiracion') ?>" readonly class="input-readonly">
                        </div>
                    </div>
                </fieldset>

                <div class="formulario__acciones">
                    <button type="submit" class="btn btn--primario">Guardar mis cambios</button>
                </div>
            </form>

            <?php if ($certificado['flash'] && !$flashDatos): ?>
                <div class="alerta alerta--<?= $certificado['flash']['tipo'] === 'success' ? 'exito' : 'error' ?>" role="status">
                    <?= e($certificado['flash']['texto']) ?>
                </div>
            <?php endif; ?>

            <!-- Foto de perfil: formulario aparte (PerfilController::subirFoto → POST /medicos/{id}/foto) -->
            <form action="/index.php?c=perfil&a=subirFoto" method="post" enctype="multipart/form-data" class="formulario">
                <fieldset class="formulario__seccion">
                    <legend>Foto de perfil</legend>
                    <img src="<?= e($certificado['foto_url'] ?: '/Assets/IMG/ICONS/usuario.png') ?>" alt="Tu foto de perfil"
                         width="96" height="96" style="object-fit: cover; border-radius: 50%;">
                    <div class="campo">
                        <label for="foto">Imagen JPG o PNG (máx. 2 MB)</label>
                        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png" required>
                    </div>
                    <input type="hidden" name="csrf" value="<?= e($certificado['csrf']) ?>">
                    <div class="formulario__acciones">
                        <button type="submit" class="btn btn--primario">Subir foto</button>
                    </div>
                </fieldset>
            </form>

            <!-- Certificado de idoneidad: formulario aparte (PerfilController::subirCertificado → API) -->
            <form action="/index.php?c=perfil&a=subirCertificado" method="post" enctype="multipart/form-data" class="formulario">
                <fieldset class="formulario__seccion">
                    <legend>Certificado de idoneidad (PDF)</legend>
                    <?php if ($certificado['url']): ?>
                        <p><a href="<?= e($certificado['url']) ?>" target="_blank" rel="noopener">Ver mi certificado actual (PDF)</a>. Si subes otro, reemplaza al actual.</p>
                    <?php endif; ?>
                    <div class="campo">
                        <label for="certificado_idoneidad">Archivo PDF (máx. 5 MB)</label>
                        <input type="file" id="certificado_idoneidad" name="certificado_idoneidad" accept="application/pdf,.pdf" required>
                    </div>
                    <input type="hidden" name="csrf" value="<?= e($certificado['csrf']) ?>">
                    <div class="formulario__acciones">
                        <button type="submit" class="btn btn--primario">Subir certificado</button>
                    </div>
                </fieldset>
            </form>
        </div>

    </main>

</div>

<script src="/Assets/JavaScript/Dedicated/perfil.js" defer></script>

<?php
require_once __DIR__ . '/../../Partials/footer.php';
?>