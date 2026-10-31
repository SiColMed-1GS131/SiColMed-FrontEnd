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
    const ADMIN            = 'admin';             // Módulo 2: Administrador del CMP
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
        'cuentas'     => [self::TESORERO],
        'movimientos' => [self::TESORERO],
        'reportes'    => [self::ADMIN_ASOCIACION],
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