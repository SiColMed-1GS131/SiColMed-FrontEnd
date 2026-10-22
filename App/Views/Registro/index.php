<!-- Hola, para las pruebas de registro estuve usando esto directamente en la termina de VSC, en la raíz del proyecto:
$env:USE_MOCK = "1"
php -S localhost:8001 -t Public preview-index.php 
http://localhost:8001/registro-medico
el ultimo es el link, va directo al navegador 
link adicional para el login
http://localhost:8001/login
-->
<?php
$medicos = $medicos ?? [];
$mensaje = $mensaje ?? '';
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
?>
<section class="registro">

  <div class="registro__cabecera">
    <h1 class="registro__titulo">Registro Médico</h1>
    <div class="registro__acciones">
      <a href="/registro-medico/nuevo" class="btn btn--primary">Nuevo médico</a>
    </div>
  </div>

  <?php if ($mensaje !== ''): ?>
    <p class="registro__mensaje" role="status"><?= $h($mensaje) ?></p>
  <?php endif; ?>

  <p class="registro__contador"><?= count($medicos) ?> médico(s) registrado(s)</p>

  <?php if (empty($medicos)): ?>
    <p class="registro__vacio">Todavía no hay médicos registrados. Usa «Nuevo médico» para agregar el primero.</p>
  <?php else: ?>
    <div class="registro__tabla-scroll">
      <table class="registro__tabla">
        <caption class="registro__caption">Lista de médicos registrados</caption>
        <thead>
          <tr>
            <th scope="col">Apellido y nombre</th>
            <th scope="col">Cédula</th>
            <th scope="col">N.º Registro médico</th>
            <th scope="col">Especialidad</th>
            <th scope="col">Certificación</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($medicos as $m): ?>
            <tr>
              <td><?= $h(trim(($m['apellido'] ?? '') . ', ' . ($m['nombre'] ?? ''), ' ,')) ?></td>
              <td><?= $h($m['cedula'] ?? '') ?></td>
              <td><?= $h($m['registro_medico'] ?? '') ?></td>
              <td><?= $h($m['especialidad'] ?? '') ?></td>
              <td>
                <?php if (!empty($m['vigente'])): ?>
                  <span class="registro__badge registro__badge--ok">Vigente</span>
                <?php else: ?>
                  <span class="registro__badge registro__badge--no">No vigente</span>
                <?php endif; ?>
              </td>
              <td>
                <a class="btn registro__editar"
                   href="/registro-medico/editar?id=<?= urlencode((string) ($m['id'] ?? '')) ?>">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</section>