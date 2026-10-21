<?php

declare(strict_types=1);

namespace App\Services;

/** Transporte HTTP hacia FastAPI; no contiene reglas de negocio. */
final class ApiClient
{
    /** @return array{status:int, body:string|null} */
    public function postJson(string $path, array $payload): array
    {
        $base = rtrim((string) (getenv('API_URL') ?: 'http://api:8000'), '/');
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\nConnection: close\r\n",
            'content' => $json,
            'timeout' => 8,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($base . '/' . ltrim($path, '/'), false, $context);
        $statusLine = $http_response_header[0] ?? '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        return [
            'status' => isset($matches[1]) ? (int) $matches[1] : 0,
            'body' => $response === false ? null : $response,
        ];
    }
}
