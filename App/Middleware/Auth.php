<?php

class Auth
{

    const PUBLICO          = 'publico';          
    const MEDICO           = 'medico';            
    const ADMIN            = 'admin';            
    const TESORERO         = 'tesorero';          
    const ADMIN_ASOCIACION = 'admin_asociacion'; 

    private const ROLES = [
        self::PUBLICO,
        self::MEDICO,
        self::ADMIN,
        self::TESORERO,
        self::ADMIN_ASOCIACION,
    ];


    private const PERMISOS = [

        'registro' => [self::ADMIN],
        'perfil'   => [self::MEDICO],
        'cuentas'     => [self::TESORERO],
        'movimientos' => [self::TESORERO],
        'reportes'    => [self::ADMIN_ASOCIACION],
    ];


    private static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }


    public static function rol(): string
    {
        self::iniciarSesion();

        if ((getenv('USE_MOCK') === '1' || (defined('USE_MOCK') && USE_MOCK)) && isset($_GET['rol'])) {
            $pedido = (string) $_GET['rol'];
            if (in_array($pedido, self::ROLES, true)) {
                $_SESSION['rol'] = $pedido;
            }
        }

        $rol = $_SESSION['rol'] ?? self::PUBLICO;
        return in_array($rol, self::ROLES, true) ? $rol : self::PUBLICO;
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
        if (!isset(self::PERMISOS[$controlador])) {
            return true;
        }
        return in_array(self::rol(), self::PERMISOS[$controlador], true);
    }


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