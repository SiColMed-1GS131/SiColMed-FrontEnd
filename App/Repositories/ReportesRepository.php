<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

/**
 * ReportesRepository · Acceso a datos de reportes contables.
 */
final class ReportesRepository extends BaseRepository
{
    private const MOCK_BALANCE = [
        ['nombre_cuenta' => 'Caja general', 'total_debito' => 5000.00, 'total_credito' => 1200.00, 'saldo' => 3800.00],
        ['nombre_cuenta' => 'Banco Nacional', 'total_debito' => 15400.50, 'total_credito' => 4500.00, 'saldo' => 10900.50],
        ['nombre_cuenta' => 'Cuotas de asociados', 'total_debito' => 0.00, 'total_credito' => 12500.00, 'saldo' => -12500.00],
        ['nombre_cuenta' => 'Gastos administrativos', 'total_debito' => 2200.00, 'total_credito' => 0.00, 'saldo' => 2200.00],
    ];

    /**
     * Consulta el Balance General entre dos fechas (GET /reportes/balance-general).
     * @return array{ok: bool, datos: array, error: string, errores: array}
     */
    public function obtenerBalanceGeneral(string $inicio, string $fin): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, self::MOCK_BALANCE);
        }

        $query = http_build_query(['fecha_inicio' => $inicio, 'fecha_fin' => $fin]);
        $respuesta = $this->llamar('get', '/reportes/balance-general?' . $query);

        // Si el backend da 404 porque no registró el router en main.py
        if (($respuesta['status'] ?? 0) === 404) {
            return $this->resultado(
                false,
                [],
                'El servicio de Reportes no se encuentra disponible en la API (Endpoint /reportes/balance-general no registrado en el servidor).'
            );
        }

        $interpretado = $this->interpretar($respuesta);
        if (!$interpretado['ok']) {
            return $interpretado;
        }

        $datos = $interpretado['datos'];
        $filas = is_array($datos) && isset($datos['filas']) && is_array($datos['filas'])
            ? $datos['filas']
            : (is_array($datos) ? $datos : []);

        return $this->resultado(true, $filas);
    }
}
