<?php

declare(strict_types=1);

namespace App\Services;

final class ApiClient
{
    /** @return array{status:int, body:string|null} */
    public function getJson(string $path): array
    {
        return $this->enviar('GET', $path);
    }

    /** @return array{status:int, body:string|null} */
    public function postJson(string $path, array $payload): array
    {
        return $this->enviar('POST', $path, $payload);
    }

    /** @return array{status:int, body:string|null} */
    public function putJson(string $path, array $payload): array
    {
        return $this->enviar('PUT', $path, $payload);
    }

    /** @return array{status:int, body:string|null} */
    public function patchJson(string $path, array $payload): array
    {
        return $this->enviar('PATCH', $path, $payload);
    }

    /** @return array{status:int, body:string|null} */
    public function deleteJson(string $path): array
    {
        return $this->enviar('DELETE', $path);
    }

    /**
     * Sube un archivo como multipart/form-data (un solo campo).
     * @return array{status:int, body:string|null}
     */
    public function postArchivo(string $path, string $campo, string $rutaArchivo, string $nombre, string $tipo): array
    {
        $limite = '----SiColMed' . bin2hex(random_bytes(12));
        $nombre = str_replace(['"', "\r", "\n"], '', $nombre);
        $cuerpo = "--{$limite}\r\n"
            . "Content-Disposition: form-data; name=\"{$campo}\"; filename=\"{$nombre}\"\r\n"
            . "Content-Type: {$tipo}\r\n\r\n"
            . (string) file_get_contents($rutaArchivo) . "\r\n"
            . "--{$limite}--\r\n";
        return $this->enviar('POST', $path, null, $cuerpo, "multipart/form-data; boundary={$limite}");
    }

    /** @return array{status:int, body:string|null} */
    private function enviar(string $metodo, string $path, ?array $payload = null, ?string $cuerpo = null, ?string $tipoCuerpo = null): array
    {
        $base = rtrim((string) (getenv('API_URL') ?: 'http://api:8000'), '/');

        $cabeceras = "Accept: application/json\r\nConnection: close\r\n";
        $opciones = [
            'method'        => $metodo,
            'timeout'       => 8,
            'ignore_errors' => true,
        ];
        if ($payload !== null) {
            $cabeceras .= "Content-Type: application/json\r\n";
            $opciones['content'] = json_encode($payload, JSON_THROW_ON_ERROR);
        } elseif ($cuerpo !== null) {
            $cabeceras .= "Content-Type: {$tipoCuerpo}\r\n";
            $opciones['content'] = $cuerpo;
        }
        $token = $this->tokenDeSesion();
        if ($token !== null) {
            $cabeceras .= "Authorization: Bearer {$token}\r\n";
        }
        $opciones['header'] = $cabeceras;

        $context  = stream_context_create(['http' => $opciones]);
        $response = @file_get_contents($base . '/' . ltrim($path, '/'), false, $context);

        $statusLine = $http_response_header[0] ?? '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        return [
            'status' => isset($matches[1]) ? (int) $matches[1] : 0,
            'body'   => $response === false ? null : $response,
        ];
    }

    /** JWT guardado por SesionController después del login (si lo hay). */
    private function tokenDeSesion(): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $token = $_SESSION['token'] ?? null;
        return is_string($token) && $token !== '' ? $token : null;
    }
}