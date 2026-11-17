<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

/**
 * Solicitudes de cambio (contrato por secciones de la API).
 *
 * El backend espera secciones: lugar_trabajo, estudio_medico, certificaciones
 * (arreglos de registros con su id) y especialidades/subespecialidades/
 * asociaciones ({agregar, quitar}). Este repositorio solo transporta ese JSON.
 * Con USE_MOCK=1 guarda las solicitudes en la sesión y no llama a la API.
 */
final class SolicitudesCambioRepository
{
    private const SESION_MOCK = 'mock_solicitudes_cambio';

    private ApiClient $api;

    public function __construct(?ApiClient $api = null)
    {
        $this->api = $api ?? new ApiClient();
    }

    /** Perfil agrupado del médico (GET /medicos/{id}); trae los ids de cada registro. */
    public function perfilPropio(int $idMedico): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, self::perfilMock($idMedico));
        }
        return $this->interpretar($this->api->getJson('/medicos/' . $idMedico));
    }

    /** Bandeja del administrador (GET /solicitudes-cambio[?estado=]). */
    public function listar(?string $estado = null): array
    {
        if ($this->usaMock()) {
            $lista = $this->mockLeer();
            if ($estado !== null) {
                $lista = array_values(array_filter($lista, fn(array $s) => $s['estado'] === $estado));
            }
            return $this->resultado(true, $lista);
        }
        $query = $estado === null ? '' : '?' . http_build_query(['estado' => $estado]);
        return $this->interpretar($this->api->getJson('/solicitudes-cambio' . $query));
    }

    /** Crea una solicitud (POST /solicitudes-cambio). 'datos' trae id_solicitud_cambio. */
    public function crear(array $payload): array
    {
        if ($this->usaMock()) {
            $lista = $this->mockLeer();
            $id = $lista === [] ? 1 : max(array_column($lista, 'id_solicitud_cambio')) + 1;
            $lista[] = [
                'id_solicitud_cambio' => $id,
                'id_medico' => 0,
                'nombre_completo' => 'Médico de prueba',
                'estado' => 'PENDIENTE',
                'fecha_solicitud' => date('Y-m-d\TH:i:s'),
                'cambios' => $this->normalizarCambiosMock($payload),
            ];
            $this->mockGuardar($lista);
            return $this->resultado(true, ['id_solicitud_cambio' => $id]);
        }
        return $this->interpretar($this->api->postJson('/solicitudes-cambio', $payload));
    }

    /** Procesa una solicitud (POST /solicitudes-cambio/{id}/procesar). */
    public function procesar(int $id, array $decisiones, ?string $comentario): array
    {
        $payload = $decisiones;
        if ($comentario !== null && $comentario !== '') {
            $payload['comentario'] = $comentario;
        }
        if ($this->usaMock()) {
            $lista = $this->mockLeer();
            foreach ($lista as &$solicitud) {
                if ($solicitud['id_solicitud_cambio'] === $id) {
                    $solicitud['estado'] = 'PROCESADO';
                    $solicitud['comentario'] = $comentario;
                    $this->mockGuardar($lista);
                    return $this->resultado(true, true);
                }
            }
            unset($solicitud);
            return $this->resultado(false, [], 'La solicitud no existe.');
        }
        return $this->interpretar($this->api->postJson('/solicitudes-cambio/' . $id . '/procesar', $payload));
    }

    private function usaMock(): bool
    {
        return getenv('USE_MOCK') === '1';
    }

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

        // 422: errores por campo
        if ($status === 422 && is_array($cuerpo['detail'] ?? null)) {
            $mensajes = [];
            foreach ($cuerpo['detail'] as $item) {
                $mensajes[] = (string) ($item['msg'] ?? 'Dato no válido.');
            }
            return $this->resultado(false, [], implode(' ', array_unique($mensajes)));
        }

        $detalle = $cuerpo['detail'] ?? null;
        return $this->resultado(false, [], is_string($detalle) ? $detalle : 'No se pudo completar la operación.');
    }

    private function resultado(bool $ok, mixed $datos = [], string $error = ''): array
    {
        return ['ok' => $ok, 'datos' => $datos, 'error' => $error];
    }

    private function mockLeer(): array
    {
        $lista = $_SESSION[self::SESION_MOCK] ?? [];
        return is_array($lista) ? $lista : [];
    }

    private function mockGuardar(array $lista): void
    {
        $_SESSION[self::SESION_MOCK] = $lista;
    }

    private static function perfilMock(int $idMedico): array
    {
        return [
            'datos_personales' => ['id_medico' => $idMedico, 'primer_nombre' => 'Médico', 'apellido_paterno' => 'de prueba'],
            'lugares_trabajo' => [[
                'id_lugar_trabajo' => 1,
                'empresa_institucion' => 'Hospital Santo Tomás',
                'cargo' => 'Médico general',
                'telefono_trabajo' => '507-500-0000',
                'email_laboral' => 'medico@hospital.pa',
            ]],
            'estudios' => [[
                'id_estudio' => 1,
                'tipo_estudio' => 'Título básico',
                'titulo' => 'Doctor en Medicina',
                'institucion' => 'Universidad de Panamá',
                'lugar' => 'Panamá',
                'fecha' => '2015-01-01',
            ]],
            'certificaciones' => [[
                'id_certificacion' => 1,
                'vigente' => true,
                'fecha_obtencion' => '2020-01-01',
                'puntaje' => 90,
                'fecha_expiracion' => '2030-01-01',
            ]],
            'especialidades' => [['id_especialidad' => 1, 'nombre' => 'Medicina General']],
            'subespecialidades' => [],
            'asociaciones' => [],
        ];
    }

    /** Convierte el payload por secciones al JSON normalizado que muestra la bandeja. */
    private function normalizarCambiosMock(array $payload): array
    {
        $cambios = [];
        $ids = [
            'lugar_trabajo' => 'id_lugar_trabajo',
            'estudio_medico' => 'id_estudio',
            'certificaciones' => 'id_certificacion',
        ];
        foreach ($ids as $seccion => $idCampo) {
            foreach (($payload[$seccion] ?? []) as $registro) {
                $normalizado = [$idCampo => $registro[$idCampo] ?? 0];
                foreach ($registro as $campo => $valor) {
                    if ($campo !== $idCampo) {
                        $normalizado[$campo] = ['valor_anterior' => null, 'valor_nuevo' => $valor, 'estado' => 'PENDIENTE'];
                    }
                }
                $cambios[$seccion][] = $normalizado;
            }
        }
        foreach (['especialidades', 'subespecialidades', 'asociaciones'] as $relacion) {
            if (isset($payload[$relacion])) {
                $cambios[$relacion] = [
                    'agregar' => $payload[$relacion]['agregar'] ?? [],
                    'quitar' => $payload[$relacion]['quitar'] ?? [],
                    'estado' => 'PENDIENTE',
                ];
            }
        }
        return $cambios;
    }
}
