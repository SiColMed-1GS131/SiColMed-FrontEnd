<?php
declare(strict_types=1);

/**
 * Public/index.php  ·  Punto de entrada (front controller)
 *
 *   index.php?c=directorio&a=index   → DirectorioController::index()
 *   index.php?c=cuentas&a=index      → CuentasController::index()
 *   index.php                        → verificación de salud de la API (como antes)
 */

$raiz = dirname(__DIR__);

// Configuración local opcional (WAMP). Git la ignora. Aquí se puede activar USE_MOCK.
if (is_file($raiz . '/App/Configs/local.php')) {
    require_once $raiz . '/App/Configs/local.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autocarga: App\Controllers\X → App/Controllers/X.php
spl_autoload_register(static function (string $clase) use ($raiz): void {
    if (strncmp($clase, 'App\\', 4) !== 0) {
        return;
    }
    $archivo = $raiz . '/App/' . str_replace('\\', '/', substr($clase, 4)) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

require_once $raiz . '/App/Middleware/Auth.php';   // clase global, sin namespace

// Página 404 con el diseño del sistema (usa App/Views/Errors/404.php si existe)
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
    require $raiz . '/App/Views/Partials/footer.php';
    exit;
}

// Controladores permitidos (lista blanca): ?c=  →  clase
$controladores = [
    'home'        => \App\Controllers\HomeController::class,
    'login'       => \App\Controllers\Auth\AuthController::class,
    'register'    => \App\Controllers\Auth\RegisterController::class,
    'directorio'  => \App\Controllers\DirectorioController::class,
    'registro'    => \App\Controllers\RegistroController::class,
    'perfil'      => \App\Controllers\PerfilController::class,
    // Módulo 5 (se activan solos cuando el archivo del controlador exista)
    'cuentas'     => \App\Controllers\CuentasController::class,
    'movimientos' => \App\Controllers\MovimientosController::class,
    'reportes'    => \App\Controllers\ReportesController::class,
];

// Acción por defecto de cada controlador (si no es "index")
$accionPorDefecto = ['login' => 'login'];

$c = strtolower((string) ($_GET['c'] ?? ''));
$a = (string) ($_GET['a'] ?? ($accionPorDefecto[$c] ?? 'index'));

// Compatibilidad: algunos enlaces del Directorio solo traen "?a=ver&id=1" sin "c".
// Corregirlos a "?c=directorio&a=ver&id=1" y luego quitar esta línea.
if ($c === '' && isset($_GET['a'])) {
    $c = 'directorio';
}

// Sin parámetros: página de inicio
if ($c === '') {
    $c = 'home';
    $a = 'index';
}

// ?c=sistema: verificación de salud de la API (lo que antes mostraba Public/index.php)
if ($c === 'sistema') {
    $api       = getenv('API_URL') ?: 'http://api:8000';
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
    <?php
    exit;
}

if (!isset($controladores[$c]) || !class_exists($controladores[$c])) {
    paginaNoEncontrada($raiz);
}

// Permisos por rol (ver PERMISOS en App/Middleware/Auth.php)
Auth::exigir($c);

$controlador = new $controladores[$c]();

// Solo acciones públicas con nombre válido (las privadas, como render, no se pueden llamar por URL)
if (!preg_match('/^[a-z][a-zA-Z0-9_]*$/', $a) || !is_callable([$controlador, $a])) {
    paginaNoEncontrada($raiz);
}

$controlador->$a();