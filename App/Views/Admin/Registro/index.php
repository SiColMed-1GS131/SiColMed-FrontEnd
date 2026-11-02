<?php
/**
 * Registro médico · lista de médicos.
 * Solo contenido: RegistroController::render() arma el layout (header, sidebar, main y footer).
 * Variables: $medicos (array), $mensaje (string, opcional), $entrada (URL de index.php)
 */
$medicos = $medicos ?? [];
$entrada = $entrada ?? 'index.php';
$esc = static fn($t): string => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
?>
<section class="registro-lista">
    <header class="registro-lista__cabecera">
        <div>
            <h1>Registro Médico</h1>
            <p>Administración y listado de médicos en el sistema.</p>
        </div>
        <a href="<?= $esc($entrada . '?c=registro&a=nuevo') ?>" class="btn btn--primario">Nuevo médico</a>
    </header>

    <?php if (!empty($mensaje)): ?>
        <div class="alerta alerta--exito" role="status"><?= $esc($mensaje) ?></div>
    <?php endif; ?>

    <div class="registro-lista__panel">
        <p class="registro-lista__total"><?= count($medicos) ?> médico(s) registrado(s)</p>

        <?php if (empty($medicos)): ?>
            <p class="registro-lista__vacio">Todavía no hay médicos registrados. Usa el botón «Nuevo médico» para agregar el primero.</p>
        <?php else: ?>
            <div class="registro-lista__scroll">
                <table class="registro-tabla">
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Cédula</th>
                            <th scope="col">Registro médico</th>
                            <th scope="col">Especialidad</th>
                            <th scope="col"><span class="visualmente-oculto">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicos as $m): ?>
                            <tr>
                                <td><?= $esc(trim(($m['nombre'] ?? '') . ' ' . ($m['apellido'] ?? ''))) ?></td>
                                <td><?= $esc($m['cedula'] ?? '') ?></td>
                                <td><?= $esc($m['registro_medico'] ?? '') ?></td>
                                <td><?= $esc($m['especialidad'] ?? '') ?></td>
                                <td class="registro-tabla__acciones">
                                    <a href="<?= $esc($entrada . '?' . http_build_query(['c' => 'registro', 'a' => 'editar', 'id' => $m['id'] ?? 0])) ?>">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>