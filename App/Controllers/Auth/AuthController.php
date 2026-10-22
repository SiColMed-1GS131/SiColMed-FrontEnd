<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

/** Muestra el formulario de acceso; el envío se procesa actualmente en JavaScript. */
final class AuthController
{
    public function login(): void
    {
        require __DIR__ . '/../../Views/Auth/login.php';
    }
}
