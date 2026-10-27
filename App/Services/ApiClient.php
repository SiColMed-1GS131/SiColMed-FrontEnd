<?php

declare(strict_types=1);

namespace App\Services;

/** Cliente HTTP único para la API. Devuelve status y cuerpo sin ocultar errores HTTP. */
final class ApiClient
{
    /** @return array{status:int,body:?string} */
    public function getJson(string $path, array $query = []): array
    {
        return $this->request('GET', $path, null, $query);
    }

    /** @return array{status:int,body:?string} */
    public function postJson(string $path, array $payload): array
    {
        return $this->request('POST', $path, $payload);
    }

    /** @return array{status:int,body:?string} */
    public function patchJson(string $path, array $payload): array
    {
        return $this->request('PATCH', $path, $payload);
    }

    /** @return array{status:int,body:?string} */
    private function request(string $method, string $path, ?array $payload = null, array $query = []): array
    {
        $base = rtrim((string) (getenv('API_URL') ?: 'http://api:8000'), '/');
        // Los endpoints los define el servidor: no aceptar URLs externas ni segmentos de navegación.
        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || preg_match('~(?:^|/)\.\.?(/|$)|[?#]~', $path)) {
            throw new \InvalidArgumentException('Ruta de API no válida.');
        }
        $url = $base . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        $headers = "Accept: application/json\r\nConnection: close\r\n";
        $options = ['method' => $method, 'header' => $headers, 'timeout' => 8,
            'ignore_errors' => true, 'follow_location' => 0];
        if ($payload !== null) {
            $options['header'] .= "Content-Type: application/json\r\n";
            $options['content'] = json_encode($payload, JSON_THROW_ON_ERROR);
        }
        $response = @file_get_contents($url, false, stream_context_create(['http' => $options]));
        // La variable se define por cada llamada al wrapper HTTP.
        $statusLine = $http_response_header[0] ?? '';
        preg_match('~\s([0-9]{3})(?:\s|$)~', $statusLine, $match);
        return ['status' => isset($match[1]) ? (int) $match[1] : 0,
            'body' => $response === false ? null : $response];
    }
}
