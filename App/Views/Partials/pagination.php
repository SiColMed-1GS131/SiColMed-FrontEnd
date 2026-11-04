<?php
/**
 * Componente de paginación reutilizable.
 * Requiere: $totalPaginas (int), $paginaActual (int), $enlacePagina (callable)
 */
$esc = $esc ?? (static fn($t): string => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'));
?>
<?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
    <nav class="paginacion" aria-label="Páginas">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="<?= $esc($enlacePagina($i)) ?>" 
               class="paginacion__link <?= $i === $paginaActual ? 'paginacion__link--activo' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($paginaActual < $totalPaginas): ?>
            <a href="<?= $esc($enlacePagina($paginaActual + 1)) ?>" class="paginacion__link paginacion__link--flecha">
                &gt;
            </a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
