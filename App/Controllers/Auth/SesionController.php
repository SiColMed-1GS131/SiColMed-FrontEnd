<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\ApiClient;

/**
 * Inicio y cierre de sesión. El JWT nunca llega al navegador (DEF-008): PHP lo
 * pide a la API y lo guarda solo en la sesión del servidor, cuya cookie es
 * HttpOnly y SameSite (ver Public/index.php).
 *
 *   POST /index.php?c=sesion&a=iniciar   body JSON {"username": "<correo>", "password": "..."}
 *   POST /index.php?c=sesion&a=cerrar
 *
 * El rol se le pregunta a la API (GET /auth/me), que valida la firma del token y
 * confirma que el usuario sigue activo.
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

        $datos   = json_decode((string) file_get_contents('php://input'), true);
        $usuario = is_array($datos) && is_string($datos['username'] ?? null) ? trim($datos['username']) : '';
        $clave   = is_array($datos) && is_string($datos['password'] ?? null) ? $datos['password'] : '';
        if ($usuario === '' || $clave === '' || strlen($usuario) > 320 || strlen($clave) > 1024) {
            $this->responder(422, ['ok' => false, 'mensaje' => 'Revisa el usuario y la contraseña.']);
        }

        // Login contra la API desde el servidor: el token no pasa por JavaScript.
        $login = (new ApiClient())->postJson('/auth/login', ['username' => $usuario, 'password' => $clave]);
        if ($login['status'] === 0 || $login['body'] === null) {
            $this->responder(502, ['ok' => false, 'mensaje' => 'No se pudo contactar con la API.']);
        }
        if ($login['status'] !== 200) {
            // 401, 403, 422 y 429 se reenvían: login.js muestra el mensaje de cada código.
            $estadoLogin = in_array($login['status'], [401, 403, 422, 429], true) ? $login['status'] : 502;
            $this->responder($estadoLogin, ['ok' => false, 'mensaje' => 'No fue posible iniciar sesión.']);
        }
        $respuestaLogin = json_decode($login['body'], true);
        $token = is_array($respuestaLogin) ? (string) ($respuestaLogin['access_token'] ?? '') : '';

        // Forma de un JWT (tres partes base64url) y tamaño razonable
        if (strlen($token) > 4096 || !preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]*$/', $token)) {
            $this->responder(502, ['ok' => false, 'mensaje' => 'La API respondió de forma inesperada.']);
        }

        $api = rtrim((string) (getenv('API_URL') ?: 'http://api:8000'), '/');
        $contexto = stream_context_create(['http' => [
            'method'        => 'GET',
            'header'        => "Accept: application/json\r\nAuthorization: Bearer {$token}\r\nConnection: close\r\n",
            'timeout'       => 8,
            'ignore_errors' => true,
        ]]);
        $cuerpo = @file_get_contents($api . '/auth/me', false, $contexto);
        $estado = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $estado = (int) $m[1];
        }

        if ($cuerpo === false || $estado === 0) {
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

        $usuario = json_decode($cuerpo, true);
        $rol     = is_array($usuario) ? (string) ($usuario['role'] ?? '') : '';
        if (!\Auth::rolValido($rol)) {
            $this->responder(403, ['ok' => false, 'mensaje' => 'Tu rol no tiene acceso a esta aplicación.']);
        }

        $idMedico = is_int($usuario['id_medico'] ?? null) ? $usuario['id_medico'] : null;
        \Auth::establecerSesion($rol, $token, (string) ($usuario['nombre'] ?? $usuario['email'] ?? ''), $idMedico);
        $this->responder(200, [
            'ok'      => true,
            'rol'     => $rol,
            'destino' => self::DESTINOS_POR_ROL[$rol],
        ]);
    }

    public function cerrar(): void
    {
        $this->exigirPeticionSegura();
        // DEF-008: la API invalida el token (POST /auth/logout) antes de borrar la sesión.
        // ApiClient manda el JWT de la sesión. Si la API no responde, igual se cierra aquí.
        if (\Auth::token() !== null) {
            (new ApiClient())->postJson('/auth/logout', []);
        }
        \Auth::cerrarSesion();
        $this->responder(200, ['ok' => true]);
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