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

            <div class="directorio-filtros__grid">
                <!-- Especialidad -->
                <div class="campo">
                    <label class="campo__label" for="filtro-especialidad">Especialidad / Subespecialidad</label>
                    <select class="campo__input" id="filtro-especialidad" name="especialidad">
                        <option value="">Seleccionar especialidad</option>
                        <option value="cardiologia">Cardiología</option>
                        <option value="cirugia-general">Cirugía General</option>
                        <option value="dermatologia">Dermatología</option>
                        <option value="ginecologia">Ginecología y Obstetricia</option>
                        <option value="medicina-interna">Medicina Interna</option>
                        <option value="oftalmologia">Oftalmología</option>
                        <option value="ortopedia">Ortopedia</option>
                        <option value="pediatria">Pediatría</option>
                        <option value="psiquiatria">Psiquiatría</option>
                    </select>
                </div>

                <!-- Asociación -->
                <div class="campo">
                    <label class="campo__label" for="filtro-asociacion">Asociación</label>
                    <select class="campo__input" id="filtro-asociacion" name="asociacion">
                        <option value="">Seleccionar asociación</option>
                        <option value="sociedad-panamena-cardiologia">Soc. Panameña de Cardiología</option>
                        <option value="sociedad-panamena-pediatria">Soc. Panameña de Pediatría</option>
                    </select>
                </div>

                <!-- Certificación -->
                <div class="campo campo--radio">
                    <span class="campo__label">Certificación</span>
                    <div class="campo__opciones-radio">
                        <label><input type="radio" name="certificacion" value="vigente"> Vigente</label>
                        <label><input type="radio" name="certificacion" value="no-vigente"> No vigente</label>
                    </div>
                </div>

                <!-- Provincia -->
                <div class="campo">
                    <label class="campo__label" for="filtro-provincia">Provincia</label>
                    <select class="campo__input" id="filtro-provincia" name="provincia">
                        <option value="">Seleccionar provincia</option>
                        <option value="bocas-del-toro">Bocas del Toro</option>
                        <option value="cocle">Coclé</option>
                        <option value="colon">Colón</option>
                        <option value="chiriqui">Chiriquí</option>
                        <option value="darien">Darién</option>
                        <option value="herrera">Herrera</option>
                        <option value="los-santos">Los Santos</option>
                        <option value="panama">Panamá</option>
                        <option value="panama-oeste">Panamá Oeste</option>
                        <option value="veraguas">Veraguas</option>
                    </select>
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
                                <span class="medico-nombre"><?= $esc(($medico['apellido'] ?? '') . ', ' . ($medico['nombre'] ?? '')) ?></span>
                                
                                <div class="tarjeta-flotante">
                                    <div class="tarjeta-flotante__header">
                                        <img src="<?= !empty($medico['foto']) ? $esc($medico['foto']) : $esc($avatar) ?>" alt="Foto" class="tarjeta-flotante__foto">
                                        <div class="tarjeta-flotante__info-principal">
                                            <span class="tarjeta-flotante__nombre"><?= $esc(trim(($medico['nombre'] ?? '') . ' ' . ($medico['apellido'] ?? ''))) ?></span>
                                            <span class="tarjeta-flotante__especialidad"><?= $esc($medico['especialidad'] ?? '') ?></span>
                                        </div>
                                    </div>
                                    <div class="tarjeta-flotante__body">
                                        <span class="tarjeta-flotante__correo"><?= $esc($medico['email_laboral'] ?? 'Correo no disponible') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= $esc($medico['especialidad'] ?? '') ?></td>
                            <td><?= $esc($medico['provincia'] ?? '') ?></td>
                            <td>
                                <?php if (isset($medico['vigente']) && $medico['vigente']): ?>
                                    <span class="etiqueta etiqueta--vigente">Vigente</span>
                                <?php else: ?>
                                    <span class="etiqueta etiqueta--no-vigente">No vigente</span>
                                <?php endif; ?>
                            </td>
                            <!-- El enlace carga la vista 'ver' en el mismo archivo -->
                            <td><a href="<?= $esc($entrada . '?' . http_build_query(['c' => 'directorio', 'a' => $accionVer, 'id' => $medico['id'] ?? 0])) ?>" class="enlace-perfil">Ver Perfil</a></td>
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