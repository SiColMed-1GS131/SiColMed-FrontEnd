<?php

$medico     = $medico     ?? [];
$errores    = $errores    ?? [];
$dirPrefijo = $dirPrefijo ?? 'direccion';
$dirTitulo  = $dirTitulo  ?? 'Dirección';

$provincias = $catalogos['provincias'] ?? [
    'Bocas del Toro', 'Chiriquí', 'Coclé', 'Colón', 'Darién', 'Herrera',
    'Los Santos', 'Panamá', 'Panamá Oeste', 'Veraguas',
    'Comarca Emberá-Wounaan', 'Comarca Guna Yala', 'Comarca Ngäbe-Buglé',
];

// Funciones auxiliares
$esc   = fn($texto) => htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
$valor = fn($campo) => $esc($medico[$dirPrefijo . '_' . $campo] ?? '');
$error = fn($campo) => $esc($errores[$dirPrefijo . '_' . $campo] ?? '');
$pref  = $esc($dirPrefijo);
$provinciaActual = (string) ($medico[$dirPrefijo . '_provincia'] ?? '');
?>

<div class="registro__subgrupo" role="group" aria-labelledby="titulo-<?= $pref ?>">
  <h3 class="registro__subtitulo" id="titulo-<?= $pref ?>"><?= $esc($dirTitulo) ?></h3>

  <div class="registro__grid registro__grid--3">


    <div class="field">
      <label for="<?= $pref ?>_provincia">Provincia</label>
      <select id="<?= $pref ?>_provincia" name="<?= $pref ?>_provincia">
        <option value="">Seleccione</option>
        <?php foreach ($provincias as $provincia): ?>
          <option value="<?= $esc($provincia) ?>"<?= $provinciaActual === (string) $provincia ? ' selected' : '' ?>>
            <?= $esc($provincia) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <span class="field__error"><?= $error('provincia') ?></span>
    </div>


    <div class="field">
      <label for="<?= $pref ?>_distrito">Distrito</label>
      <input type="text" id="<?= $pref ?>_distrito" name="<?= $pref ?>_distrito" value="<?= $valor('distrito') ?>">
      <span class="field__error"><?= $error('distrito') ?></span>
    </div>

    <div class="field">
      <label for="<?= $pref ?>_corregimiento">Corregimiento</label>
      <input type="text" id="<?= $pref ?>_corregimiento" name="<?= $pref ?>_corregimiento" value="<?= $valor('corregimiento') ?>">
      <span class="field__error"><?= $error('corregimiento') ?></span>
    </div>

    <div class="field">
      <label for="<?= $pref ?>_calle">Calle</label>
      <input type="text" id="<?= $pref ?>_calle" name="<?= $pref ?>_calle" value="<?= $valor('calle') ?>">
      <span class="field__error"><?= $error('calle') ?></span>
    </div>

    <div class="field">
      <label for="<?= $pref ?>_edificio">Edificio</label>
      <input type="text" id="<?= $pref ?>_edificio" name="<?= $pref ?>_edificio" value="<?= $valor('edificio') ?>">
      <span class="field__error"><?= $error('edificio') ?></span>
    </div>


    <div class="field">
      <label for="<?= $pref ?>_numero">Número</label>
      <input type="text" id="<?= $pref ?>_numero" name="<?= $pref ?>_numero" value="<?= $valor('numero') ?>">
      <span class="field__error"><?= $error('numero') ?></span>
    </div>

  </div>
</div>
