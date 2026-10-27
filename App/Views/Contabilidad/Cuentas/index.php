<?php
// Pantalla de Gestión de Cuentas: formulario a la izquierda y lista a la derecha.
// Solo muestra datos; no llama a la API.
$cuentas = $cuentas ?? [];
$estado = $estado ?? 'todas';
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');

// Etiqueta Débito / Crédito (usa badge_tipo.php cuando exista, cambiar en caso de hacerse de otra forma :D)
$badgeTipo = static function (string $tipoCuenta) use ($h): void {
    $partial = __DIR__ . '/../../Partials/badge_tipo.php';
    if (is_file($partial)) {
        $tipo = $tipoCuenta;
        require $partial;
        return;
    }
    $esDebito = $tipoCuenta === 'DEBITO';
    echo '<span class="badge ' . ($esDebito ? 'badge--debito' : 'badge--credito') . '">'
        . $h($esDebito ? 'Débito' : 'Crédito') . '</span>';
};

// Opciones del filtro
$filtros = ['todas' => 'Todas', 'activas' => 'Solo activas', 'inactivas' => 'Solo inactivas'];

// Arma el enlace de la lista con filtro y página
$urlLista = static function (array $params): string {
    $params = array_filter($params, static fn($v) => $v !== null && $v !== '' && $v !== 'todas' && $v !== 1);
    return '/cuentas' . ($params === [] ? '' : '?' . http_build_query($params));
};
?>
<section class="cuentas">

  <!-- Títulos -->
  <header class="cuentas__cabecera">
    <h1 class="cuentas__titulo">Gestión Financiera y Contable</h1>
    <h2 class="cuentas__subtitulo">Gestión de Cuentas</h2>
  </header>

  <!-- Mensaje de éxito o aviso -->
  <?php if (!empty($flash)): ?>
    <div class="alerta alerta--<?= $h($flash['tipo']) ?>" role="status"><?= $h($flash['texto']) ?></div>
  <?php endif; ?>

  <div class="cuentas__grid">

    <!-- Formulario -->
    <?php require __DIR__ . '/form.php'; ?>

    <!-- Lista -->
    <section class="panel panel--lista" aria-labelledby="lista-titulo">
      <div class="panel__cabecera">
        <div>
          <p class="panel__etiqueta">Listado general</p>
          <h3 class="panel__titulo" id="lista-titulo">Cuentas existentes</h3>
        </div>
        <a class="btn btn--primary" href="/cuentas/nueva<?= $h($queryLista) ?>#form-cuenta">Nueva cuenta</a>
      </div>

      <!-- Filtro -->
      <nav class="filtro-estado" aria-label="Filtrar cuentas por estado">
        <?php foreach ($filtros as $clave => $texto): ?>
          <a class="filtro-estado__opcion<?= $estado === $clave ? ' filtro-estado__opcion--activa' : '' ?>"
             href="<?= $h($urlLista(['estado' => $clave])) ?>"
             <?= $estado === $clave ? 'aria-current="page"' : '' ?>><?= $h($texto) ?></a>
        <?php endforeach; ?>
      </nav>

      <?php if (!empty($errorApi)): ?>
        <!-- Error de la API -->
        <div class="alerta alerta--error" role="alert">
          <strong>No se pudieron cargar las cuentas.</strong>
          <?= $h($errorApi) ?> Intenta de nuevo en unos minutos.
          <a class="alerta__enlace" href="<?= $h($urlLista(['estado' => $estado])) ?>">Reintentar</a>
        </div>

      <?php elseif ($cuentas === []): ?>
        <!-- Lista vacía -->
        <div class="vacio">
          <?php if ($estado === 'todas'): ?>
            <p class="vacio__titulo">Aún no hay cuentas. Crea la primera.</p>
            <p>Usa el formulario para registrar una cuenta de Débito o de Crédito.</p>
          <?php else: ?>
            <p class="vacio__titulo">No hay cuentas <?= $estado === 'activas' ? 'activas' : 'inactivas' ?>.</p>
            <a class="alerta__enlace" href="/cuentas">Ver todas las cuentas</a>
          <?php endif; ?>
        </div>

      <?php else: ?>
        <!-- Tabla de cuentas -->
        <div class="tabla-envoltura" role="region" aria-label="Tabla de cuentas" tabindex="0">
          <table class="tabla-cuentas">
            <caption class="sr-only">Cuentas contables registradas</caption>
            <thead>
              <tr>
                <th scope="col">Nombre de la cuenta</th>
                <th scope="col">Tipo</th>
                <th scope="col">Estado</th>
                <th scope="col">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cuentas as $c): ?>
                <tr class="tabla-cuentas__fila<?= $c['activa'] ? '' : ' tabla-cuentas__fila--inactiva' ?>">
                  <th scope="row" class="tabla-cuentas__nombre"><?= $h($c['nombre']) ?></th>
                  <td><?php $badgeTipo($c['tipo']); ?></td>
                  <td>
                    <?php if ($c['activa']): ?>
                      <span class="estado estado--activa">Activa</span>
                    <?php else: ?>
                      <span class="estado estado--inactiva" title="Cuenta inactiva: conserva su historial de movimientos">Inactiva</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="tabla-cuentas__acciones">
                      <!-- Editar -->
                      <a class="btn"
                         href="/cuentas/editar?<?= $h(http_build_query(array_filter(['id' => $c['id_cuenta'], 'estado' => $estado !== 'todas' ? $estado : null, 'pagina' => $pagina > 1 ? $pagina : null]))) ?>#form-cuenta"
                         aria-label="Editar <?= $h($c['nombre']) ?>">Editar</a>

                      <!-- Eliminar (por POST, pide confirmación) -->
                      <form class="form-eliminar" method="post" action="/cuentas/eliminar"
                            data-nombre="<?= $h($c['nombre']) ?>"
                            data-movimientos="<?= $c['tiene_movimientos'] ? '1' : '0' ?>">
                        <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
                        <input type="hidden" name="id_cuenta" value="<?= $h($c['id_cuenta']) ?>">
                        <input type="hidden" name="estado" value="<?= $h($estado) ?>">
                        <input type="hidden" name="pagina" value="<?= $h($pagina) ?>">
                        <button type="submit" class="btn btn--peligro"
                                aria-label="Eliminar <?= $h($c['nombre']) ?>">Eliminar</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- Paginación -->
      <footer class="paginacion">
        <p class="paginacion__texto">Página <?= (int) $pagina ?> de <?= (int) $totalPaginas ?> · <?= (int) ($totalCuentas ?? 0) ?> cuenta(s)</p>
        <nav class="paginacion__nav" aria-label="Paginación de cuentas">
          <?php if ($pagina > 1): ?>
            <a class="btn" href="<?= $h($urlLista(['estado' => $estado, 'pagina' => $pagina - 1])) ?>">Anterior</a>
          <?php else: ?>
            <span class="btn btn--deshabilitado" aria-disabled="true">Anterior</span>
          <?php endif; ?>
          <span class="paginacion__actual" aria-current="page"><?= (int) $pagina ?></span>
          <?php if ($pagina < $totalPaginas): ?>
            <a class="btn" href="<?= $h($urlLista(['estado' => $estado, 'pagina' => $pagina + 1])) ?>">Siguiente</a>
          <?php else: ?>
            <span class="btn btn--deshabilitado" aria-disabled="true">Siguiente</span>
          <?php endif; ?>
        </nav>
      </footer>
    </section>

  </div>
</section>