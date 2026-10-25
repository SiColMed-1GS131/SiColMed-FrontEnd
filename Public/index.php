<?php
<<<<<<< Updated upstream
$api = getenv('API_URL') ?: 'http://api:8000';
$respuesta = @file_get_contents("$api/health/db");
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Certificaciones Médicas</title></head>
<body>
  <h1>Certificaciones Médicas - Panamá</h1>
  <pre><?= htmlspecialchars($respuesta ?: 'No se pudo conectar con la API') ?></pre>
</body>
</html>
=======

declare(strict_types=1);

/* ==================================================
   CONFIGURACIÓN INICIAL Y SEGURIDAD
   ================================================== */
define('SICOLMED_FRONT_CONTROLLER', true);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
header('Content-Type: text/html; charset=UTF-8');

/* ==================================================
   AUTOLOAD PSR-4 (Composer o respaldo local)
   ================================================== */
$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    // Permite ejecutar el frontend sin instalar dependencias en desarrollo.
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $file = dirname(__DIR__) . '/App/'
            . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
}

/* ==================================================
   MANEJO GENERAL DE ERRORES
   ================================================== */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo 'Error interno del servidor.';
});

/* ==================================================
   IMPORTACIÓN DE CLASES
   ================================================== */
use App\Core\Router;
use App\Controllers\Auth\AuthController;
use App\Controllers\Auth\RegisterController;

/* ==================================================
   INICIALIZACIÓN DEL ROUTER
   ================================================== */
$router = new Router();

/* ==================================================
   PÁGINA INICIAL Y AUTENTICACIÓN
   ================================================== */
$router->get('/', [AuthController::class, 'login']);
$router->get('/login', [AuthController::class, 'login']);
// El inicio de sesión se procesa mediante JS y la API, no mediante POST PHP.

/* ==================================================
   REGISTRO DE MÉDICOS
   ================================================== */
$router->get('/register', [RegisterController::class, 'index']);
$router->post('/register', [RegisterController::class, 'index']);

/* ==================================================
   EJECUCIÓN DEL ROUTER
   ================================================== */
// Compatibilidad temporal con enlaces antiguos (?page=login/register).
$legacyPage = $_GET['page'] ?? null;
if ($legacyPage !== null) {
    if (!is_string($legacyPage) || !in_array($legacyPage, ['login', 'register'], true)) {
        http_response_code(404);
        exit('Página no encontrada.');
    }
    $path = '/' . $legacyPage;
} else {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($path) ? $path : '/';
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = $base === '/' ? '' : rtrim($base, '/');
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    if ($path === '/index.php') {
        $path = '/';
    }
}
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
>>>>>>> Stashed changes
