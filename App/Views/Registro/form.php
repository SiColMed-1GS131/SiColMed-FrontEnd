<?php

$medico  = $medico  ?? [];
$errores = $errores ?? [];
$errorGeneral = $errorGeneral ?? '';

// Funciones auxiliares
// No usar los nombres $valor ni $error, porque direccion.php los redefine
$dato = fn($campo) => htmlspecialchars((string) ($medico[$campo]  ?? ''), ENT_QUOTES, 'UTF-8');
$err  = fn($campo) => htmlspecialchars((string) ($errores[$campo] ?? ''), ENT_QUOTES, 'UTF-8');

// Fila de estudios (se usa para título básico, especialidad y subespecialidad)
$filaEstudio = function (string $grupo, $indice, array $fila, string $etiqueta, bool $quitable = true) {
    $esc = fn($texto) => htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
    $val = fn($campo) => $esc($fila[$campo] ?? '');
    $id  = 'educacion_' . $esc($grupo) . '_' . $esc($indice);
    $nm  = 'educacion[' . $esc($grupo) . '][' . $esc($indice) . ']';
    ?>
    <div class="registro__fila" data-fila>
      <div class="field">
        <label for="<?= $id ?>_titulo"><?= $esc($etiqueta) ?></label>
        <input type="text" id="<?= $id ?>_titulo" name="<?= $nm ?>[titulo]" value="<?= $val('titulo') ?>">
      </div>
      <div class="field">
        <label for="<?= $id ?>_institucion">Institución</label>
        <input type="text" id="<?= $id ?>_institucion" name="<?= $nm ?>[institucion]" value="<?= $val('institucion') ?>">
      </div>
      <div class="field">
        <label for="<?= $id ?>_lugar">Lugar</label>
        <input type="text" id="<?= $id ?>_lugar" name="<?= $nm ?>[lugar]" value="<?= $val('lugar') ?>">
      </div>
      <div class="field">
        <label for="<?= $id ?>_fecha">Fecha</label>
        <input type="date" id="<?= $id ?>_fecha" name="<?= $nm ?>[fecha]" value="<?= $val('fecha') ?>">
      </div>
      <?php if ($quitable): ?>
        <button type="button" class="btn registro__quitar" data-quitar>Quitar</button>
      <?php endif; ?>
    </div>
    <?php
};

// Datos de educación (si no hay, se muestra una fila vacía)
$educacion         = $medico['educacion'] ?? [];
$basico            = $educacion['basico'][0] ?? [];
$especialidades    = !empty($educacion['especialidades'])    ? $educacion['especialidades']    : [[]];
$subespecialidades = !empty($educacion['subespecialidades']) ? $educacion['subespecialidades'] : [[]];
?>
<section class="registro">
  <!-- Puede cambiar en base como se envie desde el backend -->
  <form class="registro__form" method="post" action="/registro-medico/guardar" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
     <?php if (!empty($medico['id'])): ?>
       <input type="hidden" name="id" value="<?= $dato('id') ?>">
     <?php endif; ?>

    <!-- Inicio: título y botones -->
    <div class="registro__cabecera">
      <h1 class="registro__titulo">Registro Médico</h1>
      <div class="registro__acciones">
        <!-- El nombre se puede cambiar despues -->
        <button type="submit" class="btn btn--primary">Guardar / Registrar</button>
        <a href="/registro-medico" class="btn">Cancelar</a>
      </div>
    </div>
    <!-- Fin: título y botones -->

    <!-- Inicio: aviso de error -->
    <?php if ($errorGeneral !== '' || !empty($errores)): ?>
      <div class="registro__alerta" role="alert">
        <?php if ($errorGeneral !== ''): ?>
          <?= htmlspecialchars($errorGeneral, ENT_QUOTES, 'UTF-8') ?>
        <?php else: ?>
          Hay campos con errores. Revisa los mensajes en rojo de cada pestaña.
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <!-- Fin: aviso de error -->

    <!-- Inicio: panel con pestañas -->
    <section class="registro__panel registro__panel--datos">

      <!-- Botones de las pestañas -->
      <div class="registro__tabs" role="tablist" aria-label="Secciones del registro">
        <button type="button" class="registro__tab is-active" role="tab" id="tabbtn-personal" aria-controls="tab-personal" aria-selected="true">Información personal</button>
        <button type="button" class="registro__tab" role="tab" id="tabbtn-laboral" aria-controls="tab-laboral" aria-selected="false" tabindex="-1">Información laboral</button>
        <button type="button" class="registro__tab" role="tab" id="tabbtn-educacion" aria-controls="tab-educacion" aria-selected="false" tabindex="-1">Educación</button>
        <button type="button" class="registro__tab" role="tab" id="tabbtn-organizaciones" aria-controls="tab-organizaciones" aria-selected="false" tabindex="-1">Organizaciones</button>
      </div>

      <!-- Inicio: pestaña 1, información personal -->
      <div class="registro__tabpanel" id="tab-personal" role="tabpanel" aria-labelledby="tabbtn-personal">

        <div class="registro__personal">

          <!-- Foto (puede cambiar el nombre del campo foto_url y tamaño máximo) -->
          <div class="registro__foto">
            <div class="registro__foto-marco">
              <img id="foto_preview" class="registro__foto-img" alt="Vista previa de la foto del médico"
                   src="<?= $dato('foto_url') ?>"<?= $dato('foto_url') === '' ? ' hidden' : '' ?>>
              <span class="registro__foto-vacio" id="foto_vacio"<?= $dato('foto_url') !== '' ? ' hidden' : '' ?>>Sin foto</span>
            </div>
            <label class="btn registro__foto-btn" for="foto">
              <span>Elegir foto</span>
              <input type="file" id="foto" name="foto" accept="image/jpeg,image/png">
            </label>
            <small class="registro__foto-ayuda">JPG o PNG, máx. 2 MB</small>
            <span class="field__error"><?= $err('foto') ?></span>
          </div>

          <div class="registro__personal-campos">

            <!-- Datos básicos (hecho en base al readme y no al Figma) -->
            <div class="registro__subgrupo" role="group" aria-labelledby="titulo-basicos">
              <h3 class="registro__subtitulo" id="titulo-basicos">Datos básicos</h3>
              <div class="registro__grid registro__grid--compacto">
                <div class="field">
                  <label for="nombre">Nombre</label>
                  <input type="text" id="nombre" name="nombre" value="<?= $dato('nombre') ?>" required>
                  <span class="field__error"><?= $err('nombre') ?></span>
                </div>
                <div class="field">
                  <label for="segundo_nombre">Segundo nombre</label>
                  <input type="text" id="segundo_nombre" name="segundo_nombre" value="<?= $dato('segundo_nombre') ?>">
                  <span class="field__error"><?= $err('segundo_nombre') ?></span>
                </div>
                <div class="field">
                  <label for="apellido_paterno">Apellido paterno</label>
                  <input type="text" id="apellido_paterno" name="apellido_paterno" value="<?= $dato('apellido_paterno') ?>" required>
                  <span class="field__error"><?= $err('apellido_paterno') ?></span>
                </div>
                <div class="field">
                  <label for="apellido_materno">Apellido materno</label>
                  <input type="text" id="apellido_materno" name="apellido_materno" value="<?= $dato('apellido_materno') ?>">
                  <span class="field__error"><?= $err('apellido_materno') ?></span>
                </div>
              </div>
            </div>
            <!-- Fin: datos básicos -->

            <!-- Identificación -->
            <div class="registro__subgrupo" role="group" aria-labelledby="titulo-identificacion">
              <h3 class="registro__subtitulo" id="titulo-identificacion">Identificación</h3>
              <div class="registro__grid registro__grid--compacto">
                <div class="field">
                  <label for="cedula">Cédula</label>
                  <input type="text" id="cedula" name="cedula" value="<?= $dato('cedula') ?>" required>
                  <span class="field__error"><?= $err('cedula') ?></span>
                </div>
                <div class="field">
                  <label for="registro_medico">N.º Registro médico</label>
                  <input type="text" id="registro_medico" name="registro_medico" value="<?= $dato('registro_medico') ?>" pattern="[A-Za-z0-9]+" title="Solo letras y números" required>
                  <span class="field__error"><?= $err('registro_medico') ?></span>
                </div>
                <div class="field">
                  <label for="fecha_nacimiento">Fecha de nacimiento</label>
                  <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= $dato('fecha_nacimiento') ?>" required>
                  <span class="field__error"><?= $err('fecha_nacimiento') ?></span>
                </div>
                <!-- La edad se esta calculando en registro.js, no se envía -->
                <div class="field">
                  <label for="edad">Edad</label>
                  <input type="text" id="edad" class="readonly" readonly>
                </div>
              </div>
            </div>
            <!-- Fin: identificación -->

            <!-- Contacto -->
            <div class="registro__subgrupo" role="group" aria-labelledby="titulo-contacto">
              <h3 class="registro__subtitulo" id="titulo-contacto">Contacto</h3>
              <div class="registro__grid registro__grid--compacto">
                <div class="field">
                  <label for="telefono_residencial">Teléfono residencial</label>
                  <input type="tel" id="telefono_residencial" name="telefono_residencial" value="<?= $dato('telefono_residencial') ?>">
                  <span class="field__error"><?= $err('telefono_residencial') ?></span>
                </div>
                <div class="field">
                  <label for="celular">Celular</label>
                  <input type="tel" id="celular" name="celular" value="<?= $dato('celular') ?>">
                  <span class="field__error"><?= $err('celular') ?></span>
                </div>
                <div class="field">
                  <label for="email_personal">Email personal</label>
                  <input type="email" id="email_personal" name="email_personal" value="<?= $dato('email_personal') ?>">
                  <span class="field__error"><?= $err('email_personal') ?></span>
                </div>
                <div class="field">
                  <label for="apartado_postal">Apartado postal</label>
                  <input type="text" id="apartado_postal" name="apartado_postal" value="<?= $dato('apartado_postal') ?>">
                  <span class="field__error"><?= $err('apartado_postal') ?></span>
                </div>
              </div>
            </div>
            <!-- Fin: contacto -->

          </div>
        </div>

        <!-- Dirección residencial -->
        <?php
          $dirPrefijo = 'residencia';
          $dirTitulo  = 'Dirección residencial';
          require __DIR__ . '/../Partials/direccion.php';
        ?>
      </div>
      <!-- Fin: pestaña 1 -->

      <!-- Inicio: pestaña 2, información laboral -->
      <div class="registro__tabpanel" id="tab-laboral" role="tabpanel" aria-labelledby="tabbtn-laboral" hidden>
        <div class="registro__grid">
          <div class="field">
            <label for="empresa">Empresa o institución</label>
            <input type="text" id="empresa" name="empresa" value="<?= $dato('empresa') ?>">
            <span class="field__error"><?= $err('empresa') ?></span>
          </div>
          <div class="field">
            <label for="cargo">Cargo</label>
            <input type="text" id="cargo" name="cargo" value="<?= $dato('cargo') ?>">
            <span class="field__error"><?= $err('cargo') ?></span>
          </div>
          <div class="field">
            <label for="telefono_trabajo">Teléfono de trabajo</label>
            <input type="tel" id="telefono_trabajo" name="telefono_trabajo" value="<?= $dato('telefono_trabajo') ?>">
            <span class="field__error"><?= $err('telefono_trabajo') ?></span>
          </div>
          <div class="field">
            <label for="email_laboral">Email laboral</label>
            <input type="email" id="email_laboral" name="email_laboral" value="<?= $dato('email_laboral') ?>">
            <span class="field__error"><?= $err('email_laboral') ?></span>
          </div>
          
          <?php
            $dirPrefijo = 'trabajo';
            $dirTitulo  = 'Dirección del trabajo';
            require __DIR__ . '/../Partials/direccion.php';
          ?>

        </div>
      </div>
      <!-- Fin: pestaña 2 -->

      <!-- Inicio: pestaña 3, educación -->
      <div class="registro__tabpanel" id="tab-educacion" role="tabpanel" aria-labelledby="tabbtn-educacion" hidden>

        <!-- Título básico -->
        <div class="registro__subgrupo registro__subgrupo--simple" role="group" aria-labelledby="titulo-basico">
          <h3 class="registro__subtitulo" id="titulo-basico">Título básico</h3>
          <?php $filaEstudio('basico', 0, $basico, 'Título básico', false); ?>
        </div>

        <!-- Especialidades (puede cambiar segun el API) -->
        <div class="registro__subgrupo registro__subgrupo--simple" role="group" aria-labelledby="titulo-especialidades">
          <h3 class="registro__subtitulo" id="titulo-especialidades">Especialidades</h3>
          <div data-filas="especialidades">
            <?php foreach ($especialidades as $i => $fila) { $filaEstudio('especialidades', $i, $fila, 'Especialidad'); } ?>
          </div>
          <button type="button" class="btn" data-agregar="especialidades">Agregar especialidad</button>
          <!-- Plantilla que usa registro.js para agregar filas nuevas -->
          <template id="plantilla-especialidades">
            <?php $filaEstudio('especialidades', '__IDX__', [], 'Especialidad'); ?>
          </template>
        </div>

        <!-- Subespecialidades (puede cambiar segun el API) -->
        <div class="registro__subgrupo registro__subgrupo--simple" role="group" aria-labelledby="titulo-subespecialidades">
          <h3 class="registro__subtitulo" id="titulo-subespecialidades">Subespecialidades</h3>
          <div data-filas="subespecialidades">
            <?php foreach ($subespecialidades as $i => $fila) { $filaEstudio('subespecialidades', $i, $fila, 'Subespecialidad'); } ?>
          </div>
          <button type="button" class="btn" data-agregar="subespecialidades">Agregar subespecialidad</button>
          <!-- Plantilla que usa registro.js para agregar filas nuevas -->
          <template id="plantilla-subespecialidades">
            <?php $filaEstudio('subespecialidades', '__IDX__', [], 'Subespecialidad'); ?>
          </template>
        </div>
      </div>
      <!-- Fin: pestaña 3 -->

      <!-- Inicio: pestaña 4, organizaciones (puede cambiar segun el backend) -->
      <div class="registro__tabpanel" id="tab-organizaciones" role="tabpanel" aria-labelledby="tabbtn-organizaciones" hidden>
        <div class="registro__grid">
          <div class="field">
            <label for="profesor_universitario">Profesor universitario (universidad y cátedra)</label>
            <input type="text" id="profesor_universitario" name="profesor_universitario" value="<?= $dato('profesor_universitario') ?>">
            <span class="field__error"><?= $err('profesor_universitario') ?></span>
          </div>
          <div class="field">
            <label for="gremios">Gremios</label>
            <input type="text" id="gremios" name="gremios" value="<?= $dato('gremios') ?>">
            <span class="field__error"><?= $err('gremios') ?></span>
          </div>
          <div class="field">
            <label for="sociedades">Sociedades</label>
            <input type="text" id="sociedades" name="sociedades" value="<?= $dato('sociedades') ?>">
            <span class="field__error"><?= $err('sociedades') ?></span>
          </div>
          <div class="field">
            <label for="clubes_civicos">Clubes cívicos</label>
            <input type="text" id="clubes_civicos" name="clubes_civicos" value="<?= $dato('clubes_civicos') ?>">
            <span class="field__error"><?= $err('clubes_civicos') ?></span>
          </div>
          <div class="field">
            <label for="otras_organizaciones">Otros</label>
            <input type="text" id="otras_organizaciones" name="otras_organizaciones" value="<?= $dato('otras_organizaciones') ?>">
            <span class="field__error"><?= $err('otras_organizaciones') ?></span>
          </div>
        </div>
      </div>
      <!-- Fin: pestaña 4 -->

    </section>
    <!-- Fin: panel con pestañas -->

    <!-- Inicio: certificación (No se si va como lectura o estos datos vienen cargados) -->
    <section class="registro__panel registro__panel--cert" aria-labelledby="titulo-cert">
      <h2 class="registro__panel-titulo" id="titulo-cert">Certificación</h2>

      <div class="registro__cert-campos">
        <div class="field">
          <label for="cert_vigente">Estado / Vigencia</label>
          <select id="cert_vigente" name="cert_vigente">
            <option value="">Seleccione</option>
            <option value="vigente"<?= $dato('cert_vigente') === 'vigente' ? ' selected' : '' ?>>Vigente</option>
            <option value="no_vigente"<?= $dato('cert_vigente') === 'no_vigente' ? ' selected' : '' ?>>No vigente</option>
          </select>
          <span class="field__error"><?= $err('cert_vigente') ?></span>
        </div>
        <div class="field">
          <label for="cert_fecha_obtencion">Fecha de obtención</label>
          <input type="date" id="cert_fecha_obtencion" name="cert_fecha_obtencion" value="<?= $dato('cert_fecha_obtencion') ?>">
          <span class="field__error"><?= $err('cert_fecha_obtencion') ?></span>
        </div>
        <div class="field">
          <label for="cert_fecha_expiracion">Fecha de expiración</label>
          <input type="date" id="cert_fecha_expiracion" name="cert_fecha_expiracion" value="<?= $dato('cert_fecha_expiracion') ?>">
          <span class="field__error"><?= $err('cert_fecha_expiracion') ?></span>
        </div>
        <div class="field">
          <label for="cert_puntaje">Puntaje</label>
          <input type="number" id="cert_puntaje" name="cert_puntaje" min="0" step="1" value="<?= $dato('cert_puntaje') ?>">
          <span class="field__error"><?= $err('cert_puntaje') ?></span>
        </div>
      </div>

      <!-- Certificado de idoneidad en PDF (esto puede cambiar en base al backend) -->
      <label class="registro__adjuntar" for="certificado_idoneidad">
        <span id="certificado_nombre">Adjuntar certificado de idoneidad (PDF)</span>
        <input type="file" id="certificado_idoneidad" name="certificado_idoneidad" accept="application/pdf">
      </label>
      <span class="field__error"><?= $err('certificado_idoneidad') ?></span>
    </section>
    <!-- Fin: certificación -->

  </form>
</section>