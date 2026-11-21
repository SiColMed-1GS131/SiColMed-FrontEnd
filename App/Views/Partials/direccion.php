<?php

$medico     = $medico     ?? [];
$errores    = $errores    ?? [];
$catalogos  = $catalogos  ?? [];
$dirPrefijo = $dirPrefijo ?? 'direccion';
$dirTitulo  = $dirTitulo  ?? 'Dirección';
// true: muestra "Apartado postal" (<prefijo>_apartado_postal). La residencia lo tiene en Contacto.
$dirApartado = $dirApartado ?? false;
// true: marca con * y exige provincia, distrito, corregimiento, calle, número y apartado postal (registro de un médico nuevo).
$dirObligatorio = $dirObligatorio ?? false;
$dirReq = $dirObligatorio ? ' <span style="color:#c0392b" aria-hidden="true">*</span>' : '';
$dirRq  = $dirObligatorio ? ' required' : '';

// Listas con ['id' => int, 'nombre' => string] (RegistroController::catalogosFormulario).
// Distritos y corregimientos solo traen lo ya elegido; registro.js carga el resto al cambiar el select.
// Un select sin opciones queda deshabilitado: no se elige distrito sin provincia ni corregimiento sin distrito.
$provincias     = $catalogos['provincias'] ?? [];
$distritos      = $catalogos['distritos'][$dirPrefijo] ?? [];
$corregimientos = $catalogos['corregimientos'][$dirPrefijo] ?? [];

// Funciones auxiliares
$esc   = fn($texto) => htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
$valor = fn($campo) => $esc($medico[$dirPrefijo . '_' . $campo] ?? '');
$error = fn($campo) => $esc($errores[$dirPrefijo . '_' . $campo] ?? '');
$pref  = $esc($dirPrefijo);
$opciones = function (array $lista, string $campo) use ($medico, $dirPrefijo, $esc): void {
    $actual = (string) ($medico[$dirPrefijo . '_' . $campo] ?? '');
    foreach ($lista as $item) {
        $id = (string) $item['id'];
        echo '<option value="' . $esc($id) . '"' . ($actual === $id ? ' selected' : '') . '>' . $esc($item['nombre']) . '</option>';
    }
};
?>

<div class="registro__subgrupo" role="group" aria-labelledby="titulo-<?= $pref ?>">
  <h3 class="registro__subtitulo" id="titulo-<?= $pref ?>"><?= $esc($dirTitulo) ?></h3>

  <div class="registro__grid registro__grid--3">


    <div class="field">
      <label for="<?= $pref ?>_provincia">Provincia<?= $dirReq ?></label>
      <select id="<?= $pref ?>_provincia" name="<?= $pref ?>_provincia"
              data-cascada="distritos" data-destino="<?= $pref ?>_distrito"<?= $dirRq ?>>
        <option value="">Seleccione</option>
        <?php $opciones($provincias, 'provincia'); ?>
      </select>
      <span class="field__error"><?= $error('provincia') ?></span>
    </div>


    <div class="field">
      <label for="<?= $pref ?>_distrito">Distrito<?= $dirReq ?></label>
      <select id="<?= $pref ?>_distrito" name="<?= $pref ?>_distrito"
              data-cascada="corregimientos" data-destino="<?= $pref ?>_corregimiento"<?= $distritos === [] ? ' disabled' : '' ?><?= $dirRq ?>>
        <option value="">Seleccione</option>
        <?php $opciones($distritos, 'distrito'); ?>
      </select>
      <span class="field__error"><?= $error('distrito') ?></span>
    </div>

    <div class="field">
      <label for="<?= $pref ?>_corregimiento">Corregimiento<?= $dirReq ?></label>
      <select id="<?= $pref ?>_corregimiento" name="<?= $pref ?>_corregimiento"<?= $corregimientos === [] ? ' disabled' : '' ?><?= $dirRq ?>>
        <option value="">Seleccione</option>
        <?php $opciones($corregimientos, 'corregimiento'); ?>
      </select>
      <span class="field__error"><?= $error('corregimiento') ?></span>
    </div>

    <div class="field">
      <label for="<?= $pref ?>_calle">Calle<?= $dirReq ?></label>
      <input type="text" id="<?= $pref ?>_calle" name="<?= $pref ?>_calle" value="<?= $valor('calle') ?>"<?= $dirRq ?>>
      <span class="field__error"><?= $error('calle') ?></span>
    </div>

    <div class="field">
      <label for="<?= $pref ?>_edificio">Edificio</label>
      <input type="text" id="<?= $pref ?>_edificio" name="<?= $pref ?>_edificio" value="<?= $valor('edificio') ?>">
      <span class="field__error"><?= $error('edificio') ?></span>
    </div>


    <div class="field">
      <label for="<?= $pref ?>_numero">Número<?= $dirReq ?></label>
      <input type="text" id="<?= $pref ?>_numero" name="<?= $pref ?>_numero" value="<?= $valor('numero') ?>"<?= $dirRq ?>>
      <span class="field__error"><?= $error('numero') ?></span>
    </div>

    <?php if ($dirApartado): ?>
    <div class="field">
      <label for="<?= $pref ?>_apartado_postal">Apartado postal<?= $dirReq ?></label>
      <input type="text" id="<?= $pref ?>_apartado_postal" name="<?= $pref ?>_apartado_postal" value="<?= $valor('apartado_postal') ?>"<?= $dirRq ?>>
      <span class="field__error"><?= $error('apartado_postal') ?></span>
    </div>
    <?php endif; ?>

  </div>
</div>