<?php

declare(strict_types=1);
    
/* ==================================================
   CONFIGURACIÓN INICIAL Y SEGURIDAD
   ================================================== */
define('SICOLMED_FRONT_CONTROLLER', true);
$raiz = dirname(__DIR__);

// Configuración local de cada computadora (Git la ignora). Aquí se activa USE_MOCK en WAMP.
if (is_file($raiz . '/App/Configs/local.php')) {
    require_once $raiz . '/App/Configs/local.php';
}

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
$vendorAutoload = $raiz . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    // Permite ejecutar el frontend sin instalar dependencias en desarrollo.
    spl_autoload_register(static function (string $class) use ($raiz): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $file = $raiz . '/App/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
}

// Roles y permisos (clase global, sin namespace)
require_once $raiz . '/App/Middleware/Auth.php';

/* ==================================================
   MANEJO GENERAL DE ERRORES
   En modo de prueba (USE_MOCK) se muestran los detalles; en producción, no.
   ================================================== */
$modoDev = Auth::modoPrueba();
ini_set('display_errors', $modoDev ? '1' : '0');
ini_set('log_errors', '1');
set_exception_handler(static function (Throwable $error) use ($modoDev): void {
    error_log((string) $error);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo $modoDev ? (string) $error : 'Error interno del servidor.';
});

/* ==================================================
   IMPORTACIÓN DE CLASES
   ================================================== */
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\Auth\AuthController;
use App\Controllers\Auth\RegisterController;

/* ==================================================
   PÁGINA 404 CON EL DISEÑO DEL SISTEMA
   ================================================== */
function paginaNoEncontrada(string $raiz): never
{
    http_response_code(404);
    $vista = $raiz . '/App/Views/Errors/404.php';
    if (!is_file($vista)) {
        echo 'Página no encontrada.';
        exit;
    }
    require $raiz . '/App/Views/Partials/header.php';
    echo '<div class="app-layout">';
    require $raiz . '/App/Views/Partials/sidebar.php';
    echo '<main class="main-content">';
    require $vista;
    require $raiz . '/App/Views/Partials/footer.php';   // el footer cierra </main> y </div>
    exit;
}

/* ==================================================
   1) MÓDULOS POR PARÁMETROS  (?c=controlador&a=acción)
   ================================================== */
// Lista blanca: ?c=  →  clase del controlador
$controladores = [
    'home'        => HomeController::class,
    'panel'       => \App\Controllers\PanelController::class,
    'login'       => AuthController::class,
    'register'    => RegisterController::class,
    'directorio'  => \App\Controllers\DirectorioController::class,
    'registro'    => \App\Controllers\RegistroController::class,
    'perfil'      => \App\Controllers\PerfilController::class,
    'cuentas'     => \App\Controllers\CuentasController::class,
    'movimientos' => \App\Controllers\MovimientosController::class,
    'reportes'    => \App\Controllers\ReportesController::class,
];
// Acción por defecto de cada controlador (si no es "index")
$accionPorDefecto = ['login' => 'login', 'reportes' => 'balance'];

$c = strtolower((string) ($_GET['c'] ?? ''));

// Compatibilidad: algunos enlaces del Directorio traen "?a=ver&id=1" sin "c".
// Corregirlos a "?c=directorio&a=ver&id=1" y luego quitar este bloque.
if ($c === '' && isset($_GET['a']) && !isset($_GET['page'])) {
    $c = 'directorio';
}

if ($c === 'sistema') {
    // Verificación de la API (lo que antes mostraba Public/index.php)
    $api       = getenv('API_URL') ?: 'http://api:8000';
    $respuesta = @file_get_contents("$api/health/db");
    header('Content-Type: text/plain; charset=UTF-8');
    echo $respuesta ?: 'No se pudo conectar con la API';
    exit;
}

if ($c !== '') {
    if (!isset($controladores[$c]) || !class_exists($controladores[$c])) {
        paginaNoEncontrada($raiz);
    }

    $a = (string) ($_GET['a'] ?? ($accionPorDefecto[$c] ?? 'index'));

    // Permisos por rol (ver PERMISOS en App/Middleware/Auth.php)
    Auth::exigir($c);

    $controlador = new $controladores[$c]();

    // Solo acciones públicas con nombre válido (las privadas no se pueden llamar por URL)
    if (!preg_match('/^[a-z][a-zA-Z0-9_]*$/', $a) || !is_callable([$controlador, $a])) {
        paginaNoEncontrada($raiz);
    }

    $controlador->$a();
    exit;
}

/* ==================================================
   2) RUTAS LIMPIAS: PORTADA Y AUTENTICACIÓN
   ================================================== */
$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/login', [AuthController::class, 'login']);
// El inicio de sesión se procesa mediante JS y la API, no mediante POST PHP.
$router->get('/register', [RegisterController::class, 'index']);
$router->post('/register', [RegisterController::class, 'index']);

/* ==================================================
   EJECUCIÓN DEL ROUTER
   ================================================== */
// Compatibilidad temporal con enlaces antiguos (?page=login/register).
$legacyPage = $_GET['page'] ?? null;
if ($legacyPage !== null) {
    if (!is_string($legacyPage) || !in_array($legacyPage, ['login', 'register'], true)) {
        paginaNoEncontrada($raiz);
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