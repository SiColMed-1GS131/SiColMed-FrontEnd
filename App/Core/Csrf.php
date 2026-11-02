<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Token CSRF por formulario/módulo, guardado en la sesión de PHP.
 *
 *   $token = Csrf::token('registro');                 // para el <input hidden> o una cabecera
 *   Csrf::valido('registro', $_POST['csrf'] ?? '');   // al recibir el POST
 */
final class Csrf
{
    public static function token(string $clave): string
    {
        $campo = 'csrf_' . $clave;
        if (empty($_SESSION[$campo]) || !is_string($_SESSION[$campo])) {
            $_SESSION[$campo] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$campo];
    }

    public static function valido(string $clave, mixed $enviado): bool
    {
        $guardado = $_SESSION['csrf_' . $clave] ?? '';
        return is_string($enviado) && is_string($guardado) && $guardado !== ''
            && hash_equals($guardado, $enviado);
    }
}
