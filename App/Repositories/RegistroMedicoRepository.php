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
        ['id' => 1, 'nombre' => 'Ana',   'apellido' => 'Pérez',  'apellido_paterno' => 'Pérez',  'cedula' => '8-123-456', 'registro_medico' => 'MD1001', 'especialidad' => 'Cardiología',  'vigente' => true,  'cert_vigente' => 'vigente',    'empresa' => 'Hospital Santo Tomás', 'cargo' => 'Médico staff', 'provincia' => 'Panamá', 'email_laboral' => 'aperez@hst.gob.pa'],
        ['id' => 2, 'nombre' => 'Luis',  'apellido' => 'Gómez',  'apellido_paterno' => 'Gómez',  'cedula' => '4-222-333', 'registro_medico' => 'MD1002', 'especialidad' => 'Pediatría',    'vigente' => false, 'cert_vigente' => 'no_vigente', 'empresa' => 'Clínica Hospital',     'cargo' => 'Pediatra', 'provincia' => 'Chiriquí', 'email_laboral' => 'lgomez@clinica.pa'],
        ['id' => 3, 'nombre' => 'María', 'apellido' => 'Rivera', 'apellido_paterno' => 'Rivera', 'cedula' => '9-555-666', 'registro_medico' => 'MD1003', 'especialidad' => 'Dermatología', 'vigente' => true,  'cert_vigente' => 'vigente',    'empresa' => 'Policlínica',          'cargo' => 'Dermatóloga', 'provincia' => 'Colón', 'email_laboral' => 'mrivera@policlinica.pa'],
    ];

    private ApiClient $api;

    public function __construct(?ApiClient $api = null)
    {
        $this->api = $api ?? new ApiClient();
    }

    /**
     * Directorio paginado (GET /medicos).
     * Filtros aceptados: apellido, id_especialidad, id_subespecialidad, id_asociacion,
     * id_provincia, certificacion ('vigente' | 'no-vigente'), lugar_trabajo, pagina, tamano_pagina.
     * datos = ['medicos' => [...], 'total' => int, 'pagina' => int, 'tamano_pagina' => int, 'total_paginas' => int]
     */
    public function directorio(array $filtros = []): array
    {
        if ($this->usaMock()) {
            $medicos = \array_map(static fn (array $m): array => [
                'id_medico' => $m['id'],
                'nombre_completo' => $m['nombre'] . ' ' . $m['apellido'],
                'numero_idoneidad' => $m['registro_medico'],
                'especialidades' => $m['especialidad'],
                'asociaciones' => null,
                'certificacion_vigente' => $m['vigente'],
                'provincia' => $m['provincia'],
                'lugar_trabajo' => $m['empresa'],
            ], self::MOCK);
            return $this->resultado(true, [
                'medicos' => $medicos, 'total' => \count($medicos), 'pagina' => 1,
                'tamano_pagina' => \count($medicos), 'total_paginas' => 1,
            ]);
        }

        $query = [];
        foreach (['apellido', 'lugar_trabajo'] as $campo) {
            $valor = \trim((string) ($filtros[$campo] ?? ''));
            if ($valor !== '') {
                $query[$campo] = $valor;
            }
        }
        foreach (['id_especialidad', 'id_subespecialidad', 'id_asociacion', 'id_provincia', 'pagina', 'tamano_pagina'] as $campo) {
            $valor = \filter_var($filtros[$campo] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($valor !== false) {
                $query[$campo] = $valor;
            }
        }
        $cert = (string) ($filtros['certificacion'] ?? '');
        if ($cert === 'vigente' || $cert === 'no-vigente') {
            $query['certificacion_vigente'] = $cert === 'vigente' ? 'true' : 'false';
        }

        $ruta = '/medicos' . ($query !== [] ? '?' . \http_build_query($query) : '');
        $res = $this->interpretar($this->api->getJson($ruta));
        if ($res['ok'] && (!\is_array($res['datos']['medicos'] ?? null))) {
            return $this->resultado(false, [], 'La API devolvió el directorio con formato inesperado.');
        }
        return $res;
    }

    /** Solo la lista de médicos de la primera página (para pantallas que no paginan). */
    public function listar(): array
    {
        $res = $this->directorio();
        if (!$res['ok']) {
            return $res;
        }
        return $this->resultado(true, $res['datos']['medicos']);
    }

    /** Total de médicos registrados según la API (para el Panel). */
    public function total(): ?int
    {
        $res = $this->directorio(['tamano_pagina' => 1]);
        return $res['ok'] ? (int) ($res['datos']['total'] ?? 0) : null;
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

    // Un médico por id, ya convertido a los campos planos del formulario de Registro
    public function obtenerParaFormulario(int $id): array
    {
        $res = $this->obtener($id);
        if (!$res['ok']) {
            return $res;
        }
        $datos = \is_array($res['datos']) ? $res['datos'] : [];
        // El mock ya viene con los nombres del formulario
        return $this->resultado(true, $this->usaMock() ? $datos : MedicoPayload::haciaFormulario($datos));
    }

    // Registrar médico nuevo (POST /medicos). $form son los campos planos del formulario.
    public function crear(array $form): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, $form);
        }
        return $this->interpretar($this->api->postJson('/medicos', MedicoPayload::desdeFormulario($form)), true);
    }

    // Guardar cambios de un médico (PATCH /medicos/{id})
    public function actualizar(int $id, array $form): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, $form);
        }
        return $this->interpretar($this->api->patchJson('/medicos/' . $id, MedicoPayload::desdeFormulario($form)), true);
    }

    // Guardar cambios del propio perfil médico (PATCH /medicos/{id}/perfil)
    public function actualizarPerfil(int $idMedico, array $payload): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, ['actualizado' => true]);
        }
        return $this->interpretar($this->api->patchJson('/medicos/' . $idMedico . '/perfil', $payload));
    }

    private function usaMock(): bool
    {
        return getenv('USE_MOCK') === '1';
    }

    // Convierte la respuesta de ApiClient al formato de arriba
    // Puede cambiar: formato de los errores según lo que devuelva el backend
    private function interpretar(array $respuesta, bool $deFormularioMedico = false): array
    {
        $status = (int) ($respuesta['status'] ?? 0);
        $cuerpo = json_decode((string) ($respuesta['body'] ?? ''), true);

        if ($status >= 200 && $status < 300) {
            return $this->resultado(true, \is_array($cuerpo) ? $cuerpo : []);
        }
        if ($status === 0) {
            return $this->resultado(false, [], 'No se pudo contactar con la API.');
        }

        // 422: errores por campo, formato [{"loc": [..., "campo"], "msg": "..."}]
        if ($status === 422 && \is_array($cuerpo['detail'] ?? null)) {
            $errores = [];
            $sinCampo = '';
            foreach ($cuerpo['detail'] as $item) {
                $loc = \is_array($item['loc'] ?? null) ? $item['loc'] : [];
                // En el formulario de médico, la ruta de la API se traduce al campo del formulario
                $campo = $deFormularioMedico
                    ? MedicoPayload::campoDeError($loc)
                    : ($loc === [] ? '' : (string) \end($loc));
                if ($campo !== '') {
                    $errores[$campo] = (string) ($item['msg'] ?? 'Dato no válido.');
                } elseif ($sinCampo === '' && \is_array($item)) {
                    $sinCampo = \implode('.', \array_map('strval', \array_slice($loc, 1))) . ': ' . (string) ($item['msg'] ?? 'Dato no válido.');
                }
            }
            if ($errores !== [] || $sinCampo !== '') {
                return $this->resultado(false, [], $errores === [] ? $sinCampo : '', $errores);
            }
        }

        // Los errores de PostgreSQL llegan aquí
        $detalle = $cuerpo['detail'] ?? null;
        return $this->resultado(false, [], \is_string($detalle) ? $detalle : 'No se pudo completar la operación.');
    }

    private function resultado(bool $ok, mixed $datos = [], string $error = '', array $errores = []): array
    {
        return ['ok' => $ok, 'datos' => $datos, 'error' => $error, 'errores' => $errores];
    }
}