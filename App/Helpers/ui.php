<?php
/**
 * App/Helpers/ui.php · funciones pequeñas para las vistas.
 * Están protegidas con function_exists: si otro helper ya las define, no se duplican.
 */

// Escapa texto para imprimirlo en HTML
if (!function_exists('e')) {
    function e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}

// Formato de dinero para mostrar: 1250.5 → "1,250.50" (solo presentación, no se usa para sumar)
if (!function_exists('dinero')) {
    function dinero(mixed $monto): string
    {
        return number_format((float) $monto, 2, '.', ',');
    }
}

// Etiqueta Débito / Crédito (lleva texto, no depende solo del color)
if (!function_exists('badgeTipo')) {
    function badgeTipo(string $tipo): string
    {
        $esDebito = strtoupper($tipo) === 'DEBITO';
        return '<span class="badge ' . ($esDebito ? 'badge--debito' : 'badge--credito') . '">'
             . ($esDebito ? 'Débito' : 'Crédito') . '</span>';
    }
}
