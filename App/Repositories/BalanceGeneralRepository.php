<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Acceso a datos del Balance General (requisito 5.3.a).
 *
 * Con USE_MOCK=1 devuelve datos de prueba y NO llama a la API.
 * Devuelve siempre: ['ok' => bool, 'datos' => array, 'error' => string]
 *
 * Cada fila de 'datos' sigue el contrato propuesto en el README (sección 7):
 *   id_cuenta, nombre_cuenta, total_debito, total_credito, saldo
 * Los montos llegan como texto con 2 decimales ("1250.00").
 */
final class BalanceGeneralRepository
{
    // Datos de prueba: un saldo positivo, uno negativo y uno en cero.
    private const MOCK = [
        ['id_cuenta' => 1, 'nombre_cuenta' => 'Caja general',        'total_debito' => '5200.00',     'total_credito' => '1250.75', 'saldo' => '3949.25'],
        ['id_cuenta' => 2, 'nombre_cuenta' => 'Cuotas por cobrar',   'total_debito' => '800.00',      'total_credito' => '2100.50', 'saldo' => '-1300.50'],
        ['id_cuenta' => 3, 'nombre_cuenta' => 'Fondo de reserva',    'total_debito' => '1500.00',     'total_credito' => '1500.00', 'saldo' => '0.00'],
        ['id_cuenta' => 4, 'nombre_cuenta' => 'Donaciones',          'total_debito' => '0.00',        'total_credito' => '99999999.99', 'saldo' => '-99999999.99'],
    ];

    /**
     * @param string $inicio Fecha AAAA-MM-DD (ya validada por el controlador)
     * @param string $fin    Fecha AAAA-MM-DD (ya validada por el controlador)
     */
    public function obtener(string $inicio, string $fin): array
    {
        if ($this->usaMock()) {
            // Para probar el estado "sin movimientos": usa un rango anterior a 2026.
            if ($fin < '2026-01-01') {
                return $this->resultado(true, []);
            }
            return $this->resultado(true, self::MOCK);
        }

        // ==================================================================
        // PENDIENTE: CONEXIÓN CON LA API (backend)
        // Endpoint propuesto: GET /reportes/balance-general?inicio=&fin=
        // Cuando exista, reemplazar el return de abajo por algo como:
        //
        //   $resp = (new \App\Services\ApiClient())->getJson(
        //       '/reportes/balance-general?inicio=' . urlencode($inicio) . '&fin=' . urlencode($fin)
        //   );
        //   if ($resp['status'] === 0 || $resp['status'] >= 500) {
        //       return $this->resultado(false, [], 'El reporte no está disponible en este momento.');
        //   }
        //   if ($resp['status'] < 200 || $resp['status'] >= 300) {
        //       return $this->resultado(false, [], 'No se pudo generar el reporte.');
        //   }
        //   $filas = json_decode((string) $resp['body'], true);
        //   return $this->resultado(true, is_array($filas) ? $filas : []);
        //
        // Nota: ApiClient hoy solo tiene postJson(); falta getJson().
        // ==================================================================
        return $this->resultado(false, [], 'La API del Balance General aún no está conectada.');
    }

    private function usaMock(): bool
    {
        return getenv('USE_MOCK') === '1';
    }

    private function resultado(bool $ok, array $datos, string $error = ''): array
    {
        return ['ok' => $ok, 'datos' => $datos, 'error' => $error];
    }
}