<?php
/**
 * App/Middleware/Auth.php
 *
 * Controla qué rol tiene el usuario y a qué pantallas puede entrar.
 *
 * IMPORTANTE: esto solo mejora la experiencia (oculta menús, redirige).
 * La autorización real SIEMPRE debe verificarla también el backend.
 */
class Auth
{
    // Roles que existen en el sistema.
    const PUBLICO          = 'publico';           // visitante sin sesión
    const MEDICO           = 'medico';            // Módulo 2: perfil propio
    const ADMIN            = 'admin_cmp';         // Módulo 2: Administrador del CMP (mismo nombre que el rol del backend)
    const TESORERO         = 'tesorero';          // Módulo 5: cuentas y movimientos
    const ADMIN_ASOCIACION = 'admin_asociacion';  // Módulo 5: balance general

    private const ROLES = [
        self::PUBLICO,
        self::MEDICO,
        self::ADMIN,
        self::TESORERO,
        self::ADMIN_ASOCIACION,
    ];

    /**
     * Qué roles pueden entrar a cada controlador (parámetro ?c=).
     * Si un controlador no aparece aquí, es de acceso libre.
     */
    private const PERMISOS = [
        // Módulo 2: se activan cuando exista el login (hoy todos entran como 'publico')
        // 'registro' => [self::ADMIN],
        // 'perfil'   => [self::MEDICO],
        // Módulo 5
        'cuentas'     => [self::TESORERO, self::ADMIN],
        'movimientos' => [self::TESORERO, self::ADMIN],
        'reportes'    => [self::ADMIN_ASOCIACION, self::ADMIN],
    ];

    /** Inicia la sesión una sola vez. */
    private static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Devuelve el rol actual. Si no hay sesión o el rol no es válido,
     * devuelve 'publico'.
     *
     * Solo con USE_MOCK=1 (variable de entorno o constante en local.php) se permite simular un rol con ?rol=tesorero
     * para poder probar pantallas sin login ni API.
     */
    public static function rol(): string
    {
        self::iniciarSesion();

        if (self::modoPrueba() && isset($_GET['rol'])) {
            $pedido = (string) $_GET['rol'];
            if (in_array($pedido, self::ROLES, true)) {
                $_SESSION['rol'] = $pedido;
            }
        }

        $rol = $_SESSION['rol'] ?? self::PUBLICO;
        return in_array($rol, self::ROLES, true) ? $rol : self::PUBLICO;
    }

    /**
     * Modo de prueba: se activa con USE_MOCK=1 (variable de entorno o constante en local.php).
     * Mientras no exista el login, deja ver y abrir todas las pantallas sin simular un rol.
     * DEBE estar desactivado en la entrega final.
     */
    public static function modoPrueba(): bool
    {
        // Carga App/Configs/local.php (si existe) para que funcione también en páginas
        // que no pasan por Public/index.php, como la de Perfil.
        $local = __DIR__ . '/../Configs/local.php';
        if (is_file($local)) {
            require_once $local;
        }
        return getenv('USE_MOCK') === '1' || (defined('USE_MOCK') && USE_MOCK);
    }

    /** ¿Es un rol que el sistema conoce? (el login lo usa para rechazar roles raros) */
    public static function rolValido(string $rol): bool
    {
        return in_array($rol, self::ROLES, true) && $rol !== self::PUBLICO;
    }

    /**
     * Guarda en la sesión de PHP el rol y el JWT que la API ya validó (/auth/me).
     * Se llama desde SesionController después del login.
     */
    public static function establecerSesion(string $rol, string $token, string $nombre = ''): void
    {
        self::iniciarSesion();
        session_regenerate_id(true);   // evita fijación de sesión
        $_SESSION['rol']     = $rol;
        $_SESSION['token']   = $token;
        $_SESSION['usuario'] = $nombre;
    }

    /** JWT de la sesión actual (lo usa ApiClient para llamar a la API). */
    public static function token(): ?string
    {
        self::iniciarSesion();
        $t = $_SESSION['token'] ?? null;
        return is_string($t) && $t !== '' ? $t : null;
    }

    /** Cierra la sesión de PHP por completo (cookie incluida). */
    public static function cerrarSesion(): void
    {
        self::iniciarSesion();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }

    /** ¿El usuario actual tiene alguno de estos roles? Útil en las vistas (menú). */
    public static function es(string ...$roles): bool
    {
        return in_array(self::rol(), $roles, true);
    }

    /** ¿Puede el rol actual entrar a este controlador? */
    public static function puedeEntrar(string $controlador): bool
    {
        $controlador = strtolower($controlador);
        if (self::modoPrueba() || !isset(self::PERMISOS[$controlador])) {
            return true;
        }
        return in_array(self::rol(), self::PERMISOS[$controlador], true);
    }

    /**
     * Llamar al inicio de un controlador o del router.
     * Si no tiene permiso, responde 403 y detiene la ejecución.
     */
    public static function exigir(string $controlador): void
    {
        if (self::puedeEntrar($controlador)) {
            return;
        }

        http_response_code(403);

        $vista = __DIR__ . '/../Views/Access/denegado.php';
        if (is_file($vista)) {
            require $vista;
        } else {
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
               . '<title>Acceso denegado</title></head><body>'
               . '<h1>Acceso denegado</h1>'
               . '<p>No tienes permiso para ver esta página.</p>'
               . '<p><a href="index.php">Volver al inicio</a></p>'
               . '</body></html>';
        }
        exit;
    }
}