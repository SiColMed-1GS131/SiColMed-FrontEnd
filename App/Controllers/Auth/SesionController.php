<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\ApiClient;

/**
 * Inicio y cierre de sesión. PHP es el único que habla con la API de autenticación:
 * el JWT se guarda en $_SESSION y NUNCA llega al navegador (ni a localStorage ni a sessionStorage).
 *
 *   POST /index.php?c=sesion&a=iniciar   body JSON {"username": "...", "password": "...", "recordar": false}
 *   POST /index.php?c=sesion&a=cerrar
 *
 * El rol NO lo decide el navegador: sale de GET /auth/me, que valida la firma
 * del token y confirma que el usuario sigue activo.
 */
final class SesionController
{
    private const DESTINOS_POR_ROL = [
        'medico'           => '/index.php?c=perfil&a=index',
        'admin_cmp'        => '/index.php?c=panel&a=index',
        'tesorero'         => '/index.php?c=cuentas&a=index',
        'admin_asociacion' => '/index.php?c=reportes&a=balance',
    ];

    public function iniciar(): void
    {
        $this->exigirPeticionSegura();

        $datos    = json_decode((string) file_get_contents('php://input'), true);
        $usuario  = is_array($datos) && is_string($datos['username'] ?? null) ? trim($datos['username']) : '';
        $password = is_array($datos) && is_string($datos['password'] ?? null) ? $datos['password'] : '';
        $recordar = is_array($datos) && ($datos['recordar'] ?? false) === true;

        if ($usuario === '' || $password === '' || strlen($usuario) > 254 || strlen($password) > 128) {
            $this->responder(422, ['ok' => false, 'mensaje' => 'Ingresa tu correo o usuario y tu contraseña.']);
        }

        // 1) Login en la API → JWT
        $api       = new ApiClient();
        $respuesta = $api->postJson('/auth/login', ['username' => $usuario, 'password' => $password]);
        if ($respuesta['status'] === 0 || $respuesta['body'] === null) {
            $this->responder(502, ['ok' => false, 'mensaje' => 'No se pudo contactar con la API.']);
        }
        if ($respuesta['status'] < 200 || $respuesta['status'] >= 300) {
            $this->responder(
                $respuesta['status'] === 429 ? 429 : ($respuesta['status'] >= 500 ? 502 : 401),
                ['ok' => false, 'mensaje' => $this->mensajeDeLogin($respuesta['status'])]
            );
        }
        $cuerpo = json_decode($respuesta['body'], true);
        $token  = is_array($cuerpo) ? (string) ($cuerpo['access_token'] ?? $cuerpo['token'] ?? '') : '';

        // Forma de un JWT (tres partes base64url) y tamaño razonable
        if (strlen($token) > 4096 || !preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]*$/', $token)) {
            $this->responder(502, ['ok' => false, 'mensaje' => 'El servidor respondió de forma inesperada. Inténtalo de nuevo.']);
        }

        // 2) Quién es y qué rol tiene (la API valida la firma)
        $me     = $api->conToken($token)->getJson('/auth/me');
        $estado = $me['status'];
        if ($me['body'] === null || $estado === 0) {
            $this->responder(502, ['ok' => false, 'mensaje' => 'No se pudo contactar con la API.']);
        }
        if ($estado === 401) {
            $this->responder(401, ['ok' => false, 'mensaje' => 'Tu sesión no es válida o expiró.']);
        }
        if ($estado === 403) {
            $this->responder(403, ['ok' => false, 'mensaje' => 'El usuario está inactivo.']);
        }
        if ($estado !== 200) {
            $this->responder(502, ['ok' => false, 'mensaje' => 'La API respondió con un error inesperado.']);
        }

        $perfil = json_decode($me['body'], true);
        $rol    = is_array($perfil) ? (string) ($perfil['role'] ?? '') : '';
        if (!\Auth::rolValido($rol)) {
            $this->responder(403, ['ok' => false, 'mensaje' => 'Tu rol no tiene acceso a esta aplicación.']);
        }

        // 3) Sesión de PHP (rol + JWT). «Recordar sesión» alarga la cookie de esta sesión.
        \Auth::establecerSesion($rol, $token, (string) ($perfil['nombre'] ?? $perfil['email'] ?? ''), $recordar);
        $this->responder(200, [
            'ok'      => true,
            'rol'     => $rol,
            'destino' => self::DESTINOS_POR_ROL[$rol],
        ]);
    }

    public function cerrar(): void
    {
        $this->exigirPeticionSegura();
        \Auth::cerrarSesion();
        $this->responder(200, ['ok' => true]);
    }

    // No se muestra el texto crudo del backend (puede venir en inglés o ser una lista de errores
    // de validación): se traduce el código de estado a un mensaje claro y sin filtrar detalles.
    private function mensajeDeLogin(int $estado): string
    {
        return match (true) {
            $estado === 400, $estado === 401, $estado === 404 => 'Usuario o contraseña incorrectos.',
            $estado === 403 => 'Tu cuenta no tiene permiso para acceder en este momento.',
            $estado === 422 => 'Revisa el usuario y la contraseña e inténtalo de nuevo.',
            $estado === 429 => 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.',
            $estado >= 500  => 'El servicio no está disponible en este momento. Inténtalo más tarde.',
            default         => 'No fue posible iniciar sesión. Inténtalo de nuevo.',
        };
    }

    /** Solo POST con JSON y del mismo sitio (defensa básica contra CSRF). */
    private function exigirPeticionSegura(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->responder(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
        }
        if (stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== 0) {
            $this->responder(415, ['ok' => false, 'mensaje' => 'Se esperaba application/json.']);
        }
        // Si el navegador manda Origin, debe ser este mismo sitio (host:puerto)
        $origen = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origen !== '') {
            $puerto     = parse_url($origen, PHP_URL_PORT);
            $hostOrigen = (string) parse_url($origen, PHP_URL_HOST) . ($puerto ? ':' . $puerto : '');
            if (strcasecmp($hostOrigen, (string) ($_SERVER['HTTP_HOST'] ?? '')) !== 0) {
                $this->responder(403, ['ok' => false, 'mensaje' => 'Origen no permitido.']);
            }
        }
    }

    private function responder(int $codigo, array $datos): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
