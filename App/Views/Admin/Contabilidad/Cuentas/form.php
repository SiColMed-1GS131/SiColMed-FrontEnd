<?php
// Formulario para crear o editar una cuenta.
// Si $cuenta viene vacía es para crear; si trae datos es para editar.
$cuenta = $cuenta ?? [];
$errores = $errores ?? [];
$errorGeneral = $errorGeneral ?? '';
$h = $h ?? fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); 

$esEdicion = !empty($cuenta['id_cuenta']);
$tipoBloqueado = $esEdicion && !empty($cuenta['tiene_movimientos']); 
$tipoActual = (string) ($cuenta['tipo'] ?? '');
$hayError = fn(string $campo): bool => !empty($errores[$campo]);
?>
<section class="panel panel--form" id="form-cuenta" aria-labelledby="form-titulo">
  <!-- Título del formulario -->
  <div class="panel__cabecera">
    <div>
      <p class="panel__etiqueta">Formulario</p>
      <h3 class="panel__titulo" id="form-titulo">
        <?= $esEdicion ? 'Editar cuenta: ' . $h($cuenta['nombre'] ?? '') : 'Crear cuenta' ?>
      </h3>
    </div>
    <p class="panel__nota"><span class="req" aria-hidden="true">*</span> Campo obligatorio</p>
  </div>

  <!-- Mensajes de error -->
  <?php if ($errorGeneral !== ''): ?>
    <div class="alerta alerta--error" role="alert">
      <strong>No se pudo guardar la cuenta.</strong> <?= $h($errorGeneral) ?> Tus datos siguen en el formulario.
    </div>
  <?php elseif ($errores !== []): ?>
    <div class="alerta alerta--error" role="alert">Revisa los campos marcados: la cuenta no se guardó.</div>
  <?php endif; ?>

  <form class="formulario" id="formulario-cuenta" method="post" action="/index.php?c=cuentas&a=guardar" novalidate
        <?= !empty($enfocarFormulario) ? 'data-enfocar="1"' : '' ?>>
    <!-- Datos ocultos -->
    <input type="hidden" name="csrf" value="<?= $h($csrf ?? '') ?>">
    <input type="hidden" name="estado" value="<?= $h($estado ?? 'todas') ?>">
    <input type="hidden" name="pagina" value="<?= $h($pagina ?? 1) ?>">
    <?php if ($esEdicion): ?>
      <input type="hidden" name="id_cuenta" value="<?= $h($cuenta['id_cuenta']) ?>">
    <?php endif; ?>

    <!-- Nombre -->
    <div class="field<?= $hayError('nombre') ? ' field--error' : '' ?>">
      <label for="nombre">Nombre de la cuenta <span class="req" aria-hidden="true">*</span></label>
      <input type="text" id="nombre" name="nombre" maxlength="100" required autocomplete="off"
             placeholder="Ej.: Caja general"
             value="<?= $h($cuenta['nombre'] ?? '') ?>"
             aria-describedby="nombre-error" <?= $hayError('nombre') ? 'aria-invalid="true"' : '' ?>>
      <p class="field__error" id="nombre-error" data-error-for="nombre" role="alert"><?= $h($errores['nombre'] ?? '') ?></p>
    </div>

    <!-- Tipo -->
    <div class="field<?= $hayError('tipo') ? ' field--error' : '' ?>">
      <label for="tipo">Tipo de cuenta <span class="req" aria-hidden="true">*</span></label>
      <select id="tipo" name="tipo" required
              aria-describedby="<?= $tipoBloqueado ? 'tipo-nota ' : '' ?>tipo-error"
              <?= $tipoBloqueado ? 'disabled' : '' ?>
              <?= $hayError('tipo') ? 'aria-invalid="true"' : '' ?>>
        <option value="" <?= $tipoActual === '' ? 'selected' : '' ?>>Selecciona una opción</option>
        <option value="DEBITO" <?= $tipoActual === 'DEBITO' ? 'selected' : '' ?>>Débito</option>
        <option value="CREDITO" <?= $tipoActual === 'CREDITO' ? 'selected' : '' ?>>Crédito</option>
      </select>
      <?php if ($tipoBloqueado): ?>
        <p class="field__ayuda" id="tipo-nota">No se puede cambiar el tipo porque la cuenta ya tiene movimientos.</p>
      <?php endif; ?>
      <p class="field__error" id="tipo-error" data-error-for="tipo" role="alert"><?= $h($errores['tipo'] ?? '') ?></p>
    </div>

    <!-- Activa (solo al editar) -->
    <?php if ($esEdicion): ?>
      <div class="field field--casilla">
        <input type="checkbox" id="activa" name="activa" value="1" <?= !empty($cuenta['activa']) ? 'checked' : '' ?>
               aria-describedby="activa-ayuda">
        <label for="activa">Activa</label>
        <p class="field__ayuda" id="activa-ayuda">Las cuentas inactivas no aparecen al registrar movimientos.</p>
      </div>
    <?php endif; ?>

    <!-- Botones -->
    <div class="formulario__acciones">
      <button type="submit" class="btn btn--primary" data-texto-cargando="Guardando…">
        <?= $esEdicion ? 'Guardar cambios' : 'Crear cuenta' ?>
      </button>
      <a class="btn" href="/index.php?c=cuentas&a=index<?= $h($queryLista ?? '') ?>">Cancelar</a>
    </div>
  </form>
</section>