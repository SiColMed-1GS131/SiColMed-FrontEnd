<?php
/**
 * Pestaña "Estado de cuenta" del Perfil médico (P4 · Miguel).
 * Variables esperadas (pueden estar vacías):
 *   $cuotas      → cada una: concepto, periodo, monto, vencimiento, estado ('pagada'|'pendiente')
 *   $actividades → cada una: nombre, fecha, monto, estado ('pagada'|'pendiente')
 * La vista NO llama a la API; los datos los pasa el controlador.
 */
$cuotas = $cuotas ?? [];
$actividades = $actividades ?? [];

if (!function_exists('estadoCuentaEtiqueta')) {
    /** Devuelve [clase, texto] para la etiqueta de estado. */
    function estadoCuentaEtiqueta($estado)
    {
        return strtolower((string) $estado) === 'pagada'
            ? ['estado--ok', 'Pagada']
            : ['estado--pendiente', 'Pendiente'];
    }
}
?>
<section class="perfil-tab" id="estado-cuenta" aria-labelledby="estado-cuenta-titulo">
    <h2 class="perfil-tab__title" id="estado-cuenta-titulo">Estado de cuenta</h2>

    <div class="perfil-tab__bloque">
        <h3 class="perfil-tab__subtitle">Cuotas</h3>
        <?php if (empty($cuotas)): ?>
            <p class="alert perfil-tab__vacio">No hay cuotas registradas.</p>
        <?php else: ?>
            <div class="perfil-tab__scroll">
                <table class="table perfil-tabla">
                    <caption class="perfil-tabla__caption">Cuotas</caption>
                    <thead>
                        <tr>
                            <th scope="col">Concepto</th>
                            <th scope="col">Período</th>
                            <th scope="col">Monto</th>
                            <th scope="col">Vencimiento</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuotas as $c): ?>
                            <?php [$clase, $texto] = estadoCuentaEtiqueta($c['estado'] ?? ''); ?>
                            <tr class="perfil-tabla__fila">
                                <td><?= e($c['concepto'] ?? '') ?></td>
                                <td><?= e($c['periodo'] ?? '') ?></td>
                                <td class="perfil-tabla__monto">B/. <?= e(number_format((float) ($c['monto'] ?? 0), 2)) ?></td>
                                <td><?= e($c['vencimiento'] ?? '') ?></td>
                                <td><span class="badge estado <?= e($clase) ?>"><?= e($texto) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="perfil-tab__bloque">
        <h3 class="perfil-tab__subtitle">Actividades</h3>
        <?php if (empty($actividades)): ?>
            <p class="alert perfil-tab__vacio">No hay actividades con cobro registradas.</p>
        <?php else: ?>
            <div class="perfil-tab__scroll">
                <table class="table perfil-tabla">
                    <caption class="perfil-tabla__caption">Actividades</caption>
                    <thead>
                        <tr>
                            <th scope="col">Actividad</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Monto</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividades as $a): ?>
                            <?php [$clase, $texto] = estadoCuentaEtiqueta($a['estado'] ?? ''); ?>
                            <tr class="perfil-tabla__fila">
                                <td><?= e($a['nombre'] ?? '') ?></td>
                                <td><?= e($a['fecha'] ?? '') ?></td>
                                <td class="perfil-tabla__monto">B/. <?= e(number_format((float) ($a['monto'] ?? 0), 2)) ?></td>
                                <td><span class="badge estado <?= e($clase) ?>"><?= e($texto) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
