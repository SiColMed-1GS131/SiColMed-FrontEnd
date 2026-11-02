<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

/**
 * BaseRepository · Funcionalidad común para acceso a API y modo mock.
 */
abstract class BaseRepository
{
    protected ApiClient $api;

    public function __construct(?ApiClient $api = null)
    {
        $this->api = $api ?? new ApiClient();
    }

    protected function usaMock(): bool
    {
        return getenv('USE_MOCK') === '1';
    }

    /**
     * Llama al cliente de la API con el método indicado.
     */
    protected function llamar(string $metodo, string $path, ?array $payload = null): array
    {
        return match (strtolower($metodo)) {
            'post'   => $this->api->postJson($path, $payload ?? []),
            'put'    => $this->api->putJson($path, $payload ?? []),
            'patch'  => $this->api->patchJson($path, $payload ?? []),
            'delete' => $this->api->deleteJson($path),
            default  => $this->api->getJson($path),
        };
    }

    /**
     * Convierte la respuesta HTTP de ApiClient en el formato estándar del repositorio.
     * @return array{ok: bool, datos: mixed, error: string, errores: array}
     */
    protected function interpretar(array $respuesta, ?callable $traductorError = null): array
    {
        $status = (int) ($respuesta['status'] ?? 0);
        $cuerpo = json_decode((string) ($respuesta['body'] ?? ''), true);

        if ($status >= 200 && $status < 300) {
            return $this->resultado(true, is_array($cuerpo) ? $cuerpo : []);
        }

        if ($status === 0) {
            return $this->resultado(false, [], 'No se pudo contactar con la API.');
        }

        $errores = [];
        $sinCampo = '';

        if (is_array($cuerpo['detail'] ?? null)) {
            foreach ($cuerpo['detail'] as $item) {
                $loc = is_array($item['loc'] ?? null) ? $item['loc'] : [];
                $campo = $traductorError !== null
                    ? $traductorError($loc)
                    : ($loc === [] ? '' : (string) end($loc));

                if ($campo !== '') {
                    $errores[$campo] = (string) ($item['msg'] ?? 'Dato no válido.');
                } elseif ($sinCampo === '' && is_array($item)) {
                    $sinCampo = implode('.', array_map('strval', array_slice($loc, 1))) . ': ' . (string) ($item['msg'] ?? 'Dato no válido.');
                }
            }
            if ($errores !== [] || $sinCampo !== '') {
                return $this->resultado(false, [], $errores === [] ? $sinCampo : '', $errores);
            }
        } elseif (is_array($cuerpo) && !isset($cuerpo['detail'])) {
            foreach ($cuerpo as $campo => $mensaje) {
                if (is_string($campo) && is_string($mensaje)) {
                    $errores[$campo] = $mensaje;
                }
            }
            if ($errores !== []) {
                return $this->resultado(false, [], '', $errores);
            }
        }

        $detalle = $cuerpo['detail'] ?? null;
        if (is_string($detalle) && $detalle !== '') {
            return $this->resultado(false, [], $detalle);
        }

        return match ($status) {
            401 => $this->resultado(false, [], 'Tu sesión ha expirado o no estás autenticado.'),
            403 => $this->resultado(false, [], 'No tienes permisos para realizar esta acción.'),
            404 => $this->resultado(false, [], 'El recurso solicitado no fue encontrado.'),
            429 => $this->resultado(false, [], 'Demasiadas solicitudes. Por favor espera un momento.'),
            default => $this->resultado(false, [], 'No se pudo completar la operación. Código HTTP: ' . $status),
        };
    }

    /**
     * Estructura estándar de respuesta de repositorio.
     */
    protected function resultado(bool $ok, mixed $datos = [], string $error = '', array $errores = []): array
    {
        return [
            'ok'      => $ok,
            'datos'   => $datos,
            'error'   => $error,
            'errores' => $errores,
        ];
    }
}
