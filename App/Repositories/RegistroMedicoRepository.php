<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

/**
 * Acceso a datos de médicos.
 * Con USE_MOCK=1 usa datos de prueba y no llama a la API.
 * Todos los métodos devuelven: ['ok' => bool, 'datos' => mixed, 'error' => string, 'errores' => array]
 */
final class RegistroMedicoRepository
{
    // Datos de prueba (modo mock)
    private const MOCK = [
        ['id' => 1, 'nombre' => 'Ana',   'apellido' => 'Pérez',  'apellido_paterno' => 'Pérez',  'cedula' => '8-123-456', 'registro_medico' => 'MD1001', 'especialidad' => 'Cardiología',  'vigente' => true,  'cert_vigente' => 'vigente',    'empresa' => 'Hospital Santo Tomás', 'cargo' => 'Médico staff'],
        ['id' => 2, 'nombre' => 'Luis',  'apellido' => 'Gómez',  'apellido_paterno' => 'Gómez',  'cedula' => '4-222-333', 'registro_medico' => 'MD1002', 'especialidad' => 'Pediatría',    'vigente' => false, 'cert_vigente' => 'no_vigente', 'empresa' => 'Clínica Hospital',     'cargo' => 'Pediatra'],
        ['id' => 3, 'nombre' => 'María', 'apellido' => 'Rivera', 'apellido_paterno' => 'Rivera', 'cedula' => '9-555-666', 'registro_medico' => 'MD1003', 'especialidad' => 'Dermatología', 'vigente' => true,  'cert_vigente' => 'vigente',    'empresa' => 'Policlínica',          'cargo' => 'Dermatóloga'],
    ];

    private ApiClient $api;

    public function __construct(?ApiClient $api = null)
    {
        $this->api = $api ?? new ApiClient();
    }

    // Lista de médicos
    // Pendiente: GET /medicos exige exactamente un filtro; confirmar con backend cómo listar todos
    public function listar(): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, self::MOCK);
        }
        return $this->interpretar($this->api->getJson('/medicos'));
    }

    // Un médico por id
    public function obtener(int $id): array
    {
        if ($this->usaMock()) {
            foreach (self::MOCK as $medico) {
                if ($medico['id'] === $id) {
                    return $this->resultado(true, $medico);
                }
            }
            return $this->resultado(false, [], 'Médico no encontrado.');
        }
        return $this->interpretar($this->api->getJson('/medicos/' . $id));
    }

    // Registrar médico nuevo (POST /medicos)
    public function crear(array $datos): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, $datos);
        }
        return $this->interpretar($this->api->postJson('/medicos', $datos));
    }

    // Guardar cambios de un médico (PATCH /medicos/{id})
    public function actualizar(int $id, array $datos): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, $datos);
        }
        return $this->interpretar($this->api->patchJson('/medicos/' . $id, $datos));
    }

    private function usaMock(): bool
    {
        return getenv('USE_MOCK') === '1';
    }

    // Convierte la respuesta de ApiClient al formato de arriba
    // Puede cambiar: formato de los errores según lo que devuelva el backend
    private function interpretar(array $respuesta): array
    {
        $status = (int) ($respuesta['status'] ?? 0);
        $cuerpo = json_decode((string) ($respuesta['body'] ?? ''), true);

        if ($status >= 200 && $status < 300) {
            return $this->resultado(true, is_array($cuerpo) ? $cuerpo : []);
        }
        if ($status === 0) {
            return $this->resultado(false, [], 'No se pudo contactar con la API.');
        }

        // 422: errores por campo, formato [{"loc": [..., "campo"], "msg": "..."}]
        if ($status === 422 && is_array($cuerpo['detail'] ?? null)) {
            $errores = [];
            foreach ($cuerpo['detail'] as $item) {
                $campo = is_array($item['loc'] ?? null) ? (string) end($item['loc']) : '';
                if ($campo !== '') {
                    $errores[$campo] = (string) ($item['msg'] ?? 'Dato no válido.');
                }
            }
            if ($errores !== []) {
                return $this->resultado(false, [], '', $errores);
            }
        }

        // Los errores de PostgreSQL llegan aquí
        $detalle = $cuerpo['detail'] ?? null;
        return $this->resultado(false, [], is_string($detalle) ? $detalle : 'No se pudo completar la operación.');
    }

    private function resultado(bool $ok, mixed $datos = [], string $error = '', array $errores = []): array
    {
        return ['ok' => $ok, 'datos' => $datos, 'error' => $error, 'errores' => $errores];
    }
}