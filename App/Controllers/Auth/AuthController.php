<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

/** Muestra el formulario de acceso; el envío se procesa actualmente en JavaScript. */
final class AuthController
{
    public function login(): void
    {
        // URL de la API alcanzable desde el NAVEGADOR (distinta de API_URL, que usa PHP dentro de Docker).
        $apiBaseUrl = rtrim((string) (getenv('API_PUBLIC_URL') ?: 'http://localhost:8000'), '/');
        // Se activa si el formulario llegó a enviarse sin que JavaScript lo interceptara.
        $scriptFailed = ($_GET['js'] ?? '') === '0';
        require __DIR__ . '/../../Views/Auth/login.php';
    }

    /**
     * Respaldo si el formulario se envía sin JavaScript (por ejemplo, si el script no cargó).
     * Descarta los datos enviados y vuelve al formulario con un aviso, en lugar de mostrar un 405.
     */
    public function loginFallback(): void
    {
        header('Location: /login?js=0', true, 303);
    }
}
