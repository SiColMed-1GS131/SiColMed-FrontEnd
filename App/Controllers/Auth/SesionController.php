<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

/**
 * Puente entre el login (JavaScript + JWT) y la sesión de PHP.
 *
 *   POST /index.php?c=sesion&a=iniciar   body JSON {"token": "<JWT>"}
 *   POST /index.php?c=sesion&a=cerrar
 *
 * El rol NO se toma del token que manda el navegador: se le pregunta a la API
 * (GET /auth/me), que valida la firma y confirma que el usuario sigue activo.
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

        $datos = json_decode((string) file_get_contents('php://input'), true);
        $token = is_array($datos) ? (string) ($datos['token'] ?? '') : '';

        // Forma de un JWT (tres partes base64url) y tamaño razonable
        if (strlen($token) > 4096 || !preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]*$/', $token)) {
            $this->responder(400, ['ok' => false, 'mensaje' => 'Token inválido.']);
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

        \Auth::establecerSesion($rol, $token, (string) ($usuario['nombre'] ?? $usuario['email'] ?? ''));
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