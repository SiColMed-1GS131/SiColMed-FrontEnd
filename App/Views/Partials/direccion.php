<?php

$medico     = $medico     ?? [];
$errores    = $errores    ?? [];
$dirPrefijo = $dirPrefijo ?? 'direccion';
$dirTitulo  = $dirTitulo  ?? 'Dirección';

// Lista de provincias de la API: [['id_provincia' => 1, 'nombre' => 'Panamá'], ...]
$dirCatalogos = (isset($catalogos) && is_array($catalogos)) ? $catalogos : [];
$provincias   = $dirCatalogos['provincias'] ?? [];

// Funciones auxiliares
$esc   = fn($texto) => htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
$valor = fn($campo) => $esc($medico[$dirPrefijo . '_' . $campo] ?? '');
$error = fn($campo) => $esc($errores[$dirPrefijo . '_' . $campo] ?? '');
$pref  = $esc($dirPrefijo);
$provinciaActual    = (string) ($medico[$dirPrefijo . '_id_provincia'] ?? '');
$distritoActual     = (string) ($medico[$dirPrefijo . '_id_distrito'] ?? '');
$corregimientoActual = (string) ($medico[$dirPrefijo . '_id_corregimiento'] ?? '');
?>

<div class="registro__subgrupo" role="group" aria-labelledby="titulo-<?= $pref ?>">
  <h3 class="registro__subtitulo" id="titulo-<?= $pref ?>"><?= $esc($dirTitulo) ?></h3>

  <div class="registro__grid registro__grid--3">


    <!-- Provincia → distrito → corregimiento: los dos últimos se cargan por JS (registro.js) desde ?c=registro&a=catalogo.
         La API solo recibe id_corregimiento; provincia y distrito sirven para llegar a él. -->
    <div class="field">
      <label for="<?= $pref ?>_id_provincia">Provincia</label>
      <select id="<?= $pref ?>_id_provincia" name="<?= $pref ?>_id_provincia" data-geo="provincia" data-prefijo="<?= $pref ?>">
        <option value="">Seleccione</option>
        <?php foreach ($provincias as $provincia): ?>
          <?php $idProv = (string) ($provincia['id_provincia'] ?? ''); ?>
          <option value="<?= $esc($idProv) ?>"<?= $provinciaActual === $idProv ? ' selected' : '' ?>>
            <?= $esc($provincia['nombre'] ?? '') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <span class="field__error"><?= $error('id_provincia') ?></span>
    </div>

    <div class="field">
      <label for="<?= $pref ?>_id_distrito">Distrito</label>
      <select id="<?= $pref ?>_id_distrito" name="<?= $pref ?>_id_distrito" data-geo="distrito" data-prefijo="<?= $pref ?>" data-seleccionado="<?= $esc($distritoActual) ?>" disabled>
        <option value="">Seleccione una provincia</option>
      </select>
      <span class="field__error"><?= $error('id_distrito') ?></span>
    </div>

    <div class="field">
      <label for="<?= $pref ?>_id_corregimiento">Corregimiento</label>
      <select id="<?= $pref ?>_id_corregimiento" name="<?= $pref ?>_id_corregimiento" data-geo="corregimiento" data-prefijo="<?= $pref ?>" data-seleccionado="<?= $esc($corregimientoActual) ?>" disabled>
        <option value="">Seleccione un distrito</option>
      </select>
      <span class="field__error"><?= $error('id_corregimiento') ?></span>
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
