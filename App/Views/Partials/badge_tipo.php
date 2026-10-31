<?php
/**
 * Etiqueta Débito / Crédito.
 * Uso: $tipo = 'DEBITO'; require __DIR__ . '/../../Partials/badge_tipo.php';
 * Variable: $tipo = 'DEBITO' | 'CREDITO'
 */
$esDebito = strtoupper((string) ($tipo ?? '')) === 'DEBITO';
?>
<span class="badge <?= $esDebito ? 'badge--debito' : 'badge--credito' ?>"><?= $esDebito ? 'Débito' : 'Crédito' ?></span>
