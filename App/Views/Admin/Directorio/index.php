<?php

$entrada = $entrada ?? 'index.php';
$esc     = static fn($t): string => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$avatar  = rtrim(dirname($entrada), '/') . '/Assets/IMG/ICONS/usuario.png';   // foto por defecto
// Acciones del controlador: interno (index/ver) o página pública (publico/publicoVer)
$accionIndex = $accionIndex ?? 'index';
$accionVer   = $accionVer ?? 'ver';
?>
<?php
// Enlace a otra página conservando los filtros activos
$enlacePagina = static fn (int $pagina): string => $entrada . '?' . http_build_query(
    ['c' => 'directorio', 'a' => $accionIndex] + array_filter($filtros ?? [], static fn ($v) => $v !== '') + ['page' => $pagina]
);
?>
<div class="directorio-contenedor">
    <h1 class="directorio-titulo">Directorio médico</h1>

    <?php if (!empty($errorGeneral)): ?>
        <div class="alerta alerta--error"><?= htmlspecialchars($errorGeneral, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <section class="directorio-filtros">
        <h2 class="directorio-filtros__titulo">Filtros de Búsqueda</h2>
        
        <form class="directorio-filtros__form" action="" method="GET">
            <!-- Parámetros para el enrutador -->
            <input type="hidden" name="c" value="directorio">
            <input type="hidden" name="a" value="<?= $esc($accionIndex) ?>">

            <?php
            $f = $filtros ?? [];
            $cat = $catalogos ?? [];
            // <select> con ids de la API; conserva la opción elegida
            $opciones = static function (array $items, string $campoId, string $actual) use ($esc): string {
                $html = '';
                foreach ($items as $it) {
                    $id = (string) ($it[$campoId] ?? '');
                    $html .= '<option value="' . $esc($id) . '"' . ($id === $actual ? ' selected' : '') . '>' . $esc($it['nombre'] ?? '') . '</option>';
                }
                return $html;
            };
            ?>
            <div class="directorio-filtros__grid">
                <div class="campo">
                    <label class="campo__label" for="filtro-apellido">Apellido</label>
                    <input class="campo__input" type="text" id="filtro-apellido" name="apellido" maxlength="100" value="<?= $esc($f['apellido'] ?? '') ?>">
                </div>

                <div class="campo">
                    <label class="campo__label" for="filtro-especialidad">Especialidad</label>
                    <select class="campo__input" id="filtro-especialidad" name="id_especialidad">
                        <option value="">Seleccionar especialidad</option>
                        <?= $opciones($cat['especialidades'] ?? [], 'id_especialidad', (string) ($f['id_especialidad'] ?? '')) ?>
                    </select>
                </div>

                <div class="campo">
                    <label class="campo__label" for="filtro-subespecialidad">Subespecialidad</label>
                    <select class="campo__input" id="filtro-subespecialidad" name="id_subespecialidad">
                        <option value="">Seleccionar subespecialidad</option>
                        <?= $opciones($cat['subespecialidades'] ?? [], 'id_subespecialidad', (string) ($f['id_subespecialidad'] ?? '')) ?>
                    </select>
                </div>

                <div class="campo">
                    <label class="campo__label" for="filtro-asociacion">Asociación</label>
                    <select class="campo__input" id="filtro-asociacion" name="id_asociacion">
                        <option value="">Seleccionar asociación</option>
                        <?= $opciones($cat['asociaciones'] ?? [], 'id_asociacion', (string) ($f['id_asociacion'] ?? '')) ?>
                    </select>
                </div>

                <div class="campo campo--radio">
                    <span class="campo__label">Certificación</span>
                    <div class="campo__opciones-radio">
                        <label><input type="radio" name="certificacion" value="vigente" <?= ($f['certificacion'] ?? '') === 'vigente' ? 'checked' : '' ?>> Vigente</label>
                        <label><input type="radio" name="certificacion" value="no-vigente" <?= ($f['certificacion'] ?? '') === 'no-vigente' ? 'checked' : '' ?>> No vigente</label>
                    </div>
                </div>

                <div class="campo">
                    <label class="campo__label" for="filtro-provincia">Provincia</label>
                    <select class="campo__input" id="filtro-provincia" name="id_provincia">
                        <option value="">Seleccionar provincia</option>
                        <?= $opciones($cat['provincias'] ?? [], 'id_provincia', (string) ($f['id_provincia'] ?? '')) ?>
                    </select>
                </div>

                <div class="campo">
                    <label class="campo__label" for="filtro-lugar">Lugar de trabajo</label>
                    <input class="campo__input" type="text" id="filtro-lugar" name="lugar_trabajo" maxlength="150" value="<?= $esc($f['lugar_trabajo'] ?? '') ?>">
                </div>

                <div class="directorio-filtros__acciones">
                    <button type="submit" class="btn btn--primario">Aplicar filtros</button>
                    <!-- Eliminamos parámetros para resetear -->
                    <a href="<?= $esc($entrada . '?c=directorio&a=' . $accionIndex) ?>" class="btn btn--secundario">Limpiar filtros</a>
                </div>
            </div>
        </form>
    </section>

    <div class="tabla-responsiva">
        <table class="tabla-directorio">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Especialidad</th>
                    <th>Provincia / País</th>
                    <th>Certificación</th>
                    <th>Consulta</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($medicos)): ?>
                    <?php foreach ($medicos as $medico): ?>
                        <tr>
                            <td class="medico-hover-container">
                                <span class="medico-nombre"><?= $esc($medico['nombre_completo'] ?? '') ?></span>

                                <div class="tarjeta-flotante">
                                    <div class="tarjeta-flotante__header">
                                        <img src="<?= $esc($avatar) ?>" alt="Foto" class="tarjeta-flotante__foto">
                                        <div class="tarjeta-flotante__info-principal">
                                            <span class="tarjeta-flotante__nombre"><?= $esc($medico['nombre_completo'] ?? '') ?></span>
                                            <span class="tarjeta-flotante__especialidad"><?= $esc($medico['especialidades'] ?? '') ?></span>
                                        </div>
                                    </div>
                                    <div class="tarjeta-flotante__body">
                                        <span class="tarjeta-flotante__correo">Idoneidad: <?= $esc($medico['numero_idoneidad'] ?? '—') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= $esc($medico['especialidades'] ?? '') ?></td>
                            <td><?= $esc($medico['provincia'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($medico['certificacion_vigente'])): ?>
                                    <span class="etiqueta etiqueta--vigente">Vigente</span>
                                <?php else: ?>
                                    <span class="etiqueta etiqueta--no-vigente">No vigente</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="<?= $esc($entrada . '?' . http_build_query(['c' => 'directorio', 'a' => $accionVer, 'id' => $medico['id_medico'] ?? 0])) ?>" class="enlace-perfil">Ver Perfil</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px;">No se encontraron médicos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación (Solo se muestra si hay más de 1 página) -->
    <?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
    <div class="paginacion">
        
        <!-- Números de página -->
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="<?= $esc($enlacePagina($i)) ?>" class="paginacion__link <?= $i === $paginaActual ? 'paginacion__link--activo' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <!-- Flecha de Siguiente (>) -->
        <?php if ($paginaActual < $totalPaginas): ?>
            <a href="<?= $esc($enlacePagina($paginaActual + 1)) ?>" class="paginacion__link paginacion__link--flecha">
                &gt;
            </a>
        <?php endif; ?>
        
    </div>
    <?php endif; ?>
</div>