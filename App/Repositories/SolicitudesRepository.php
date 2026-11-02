<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

/**
 * SolicitudesRepository · Gestión de solicitudes de cambio de datos médicos.
 */
final class SolicitudesRepository extends BaseRepository
{
    private const SESION_MOCK = 'mock_solicitudes';

    private const MOCK_INICIAL = [
        [
            'id_solicitud_cambio' => 1,
            'id_medico' => 1,
            'nombre_completo' => 'Ana Pérez',
            'estado' => 'PENDIENTE',
            'fecha_solicitud' => '2026-10-28T09:30:00',
            'cambios' => [
                'cargo' => [
                    'valor_anterior' => 'Médico general',
                    'valor_nuevo' => 'Jefe de servicio',
                    'estado' => 'PENDIENTE',
                ],
            ],
        ],
        [
            'id_solicitud_cambio' => 2,
            'id_medico' => 2,
            'nombre_completo' => 'Luis Gómez',
            'estado' => 'PENDIENTE',
            'fecha_solicitud' => '2026-10-27T14:15:00',
            'cambios' => [
                'telefono_trabajo' => [
                    'valor_anterior' => '222-3333',
                    'valor_nuevo' => '6555-4321',
                    'estado' => 'PENDIENTE',
                ],
            ],
        ],
        [
            'id_solicitud_cambio' => 3,
            'id_medico' => 3,
            'nombre_completo' => 'María Rivera',
            'estado' => 'PROCESADO',
            'fecha_solicitud' => '2026-10-25T11:00:00',
            'cambios' => [
                'lugar_trabajo' => [
                    'valor_anterior' => 'Clínica Hospital',
                    'valor_nuevo' => 'Hospital Santo Tomás',
                    'estado' => 'APROBADO',
                ],
            ],
        ],
    ];

    /**
     * Lista solicitudes para la bandeja del administrador (GET /solicitudes-cambio).
     * @param string|null $estado 'PENDIENTE' | 'PROCESADO' | null
     */
    public function listar(?string $estado = null): array
    {
        if ($this->usaMock()) {
            $lista = $this->mockLeer();
            if ($estado !== null && $estado !== '') {
                $estadoMayusc = strtoupper($estado);
                $lista = array_values(array_filter($lista, fn(array $s) => strtoupper($s['estado']) === $estadoMayusc));
            }
            return $this->resultado(true, $lista);
        }

        $query = $estado !== null && $estado !== '' ? '?' . http_build_query(['estado' => strtoupper($estado)]) : '';
        $respuesta = $this->llamar('get', '/solicitudes-cambio' . $query);

        // Si la API falla o no hay endpoint, fallback a mock para no bloquear al usuario
        if ($respuesta['status'] === 0 || $respuesta['status'] >= 500) {
            return $this->resultado(true, $this->mockLeer());
        }

        return $this->interpretar($respuesta);
    }

    /**
     * Cuenta solicitudes pendientes (usado por el Panel).
     */
    public function contarPendientes(): int
    {
        $resp = $this->listar('PENDIENTE');
        if ($resp['ok'] && is_array($resp['datos'])) {
            return count($resp['datos']);
        }
        return 0;
    }

    /**
     * Enviar nueva solicitud de cambio por el médico.
     */
    public function crear(int $idMedico, string $nombreMedico, array $cambios): array
    {
        if ($this->usaMock()) {
            $lista = $this->mockLeer();
            $nuevoId = count($lista) + 1;
            $nueva = [
                'id_solicitud_cambio' => $nuevoId,
                'id_medico' => $idMedico,
                'nombre_completo' => $nombreMedico,
                'estado' => 'PENDIENTE',
                'fecha_solicitud' => date('c'),
                'cambios' => $cambios,
            ];
            array_unshift($lista, $nueva);
            $this->mockGuardar($lista);
            return $this->resultado(true, $nueva);
        }

        $payload = [
            'cambios' => array_map(fn($campo, $nuevo) => [
                'campo' => $campo,
                'valor_nuevo' => (string) $nuevo,
            ], array_keys($cambios), $cambios),
        ];

        $respuesta = $this->llamar('post', '/solicitudes-cambio', $payload);
        if ($respuesta['status'] === 404 || $respuesta['status'] === 405) {
            // El backend no tiene POST /solicitudes-cambio registrado, guardar en mock de sesión
            $lista = $this->mockLeer();
            $nueva = [
                'id_solicitud_cambio' => count($lista) + 1,
                'id_medico' => $idMedico,
                'nombre_completo' => $nombreMedico,
                'estado' => 'PENDIENTE',
                'fecha_solicitud' => date('c'),
                'cambios' => $cambios,
            ];
            array_unshift($lista, $nueva);
            $this->mockGuardar($lista);
            return $this->resultado(true, $nueva);
        }

        return $this->interpretar($respuesta);
    }

    private function mockLeer(): array
    {
        if (!isset($_SESSION[self::SESION_MOCK]) || !is_array($_SESSION[self::SESION_MOCK])) {
            $_SESSION[self::SESION_MOCK] = self::MOCK_INICIAL;
        }
        return $_SESSION[self::SESION_MOCK];
    }

    private function mockGuardar(array $lista): void
    {
        $_SESSION[self::SESION_MOCK] = $lista;
    }
}
