<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $action): void
    {
        $this->add('GET', $path, $action);
    }

    public function post(string $path, array $action): void
    {
        $this->add('POST', $path, $action);
    }

    private function add(string $method, string $path, array $action): void
    {
        $this->routes[$path][$method] = $action;
    }

    public function dispatch(string $method, string $path): void
    {
        // HEAD se atiende como GET (PHP descarta el cuerpo); evita 405 en comprobaciones de salud.
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $path = $path !== '/' ? rtrim($path, '/') : '/';
        if (!isset($this->routes[$path])) {
            http_response_code(404);
            echo 'Página no encontrada.';
            return;
        }
        if (!isset($this->routes[$path][$method])) {
            header('Allow: ' . implode(', ', array_keys($this->routes[$path])));
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }
        $handler = $this->routes[$path][$method];
        [$controller, $action] = $handler;
        (new $controller())->$action();
    }
}
