<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ApiClient;

require_once __DIR__ . '/../Helpers/ui.php';   // e() y dinero()

final class ReportesController
{
    private const VISTAS = __DIR__ . '/../Views/Admin/Contabilidad/Reportes/';

    private ApiClient $api;

    public function __construct()
    {
        $this->api = new ApiClient();
    }

    public function balance(): void
    {
        $inicio = trim((string) ($_GET['inicio'] ?? ''));
        $fin = trim((string) ($_GET['fin'] ?? ''));
        $consultado = isset($_GET['inicio']) || isset($_GET['fin']);

        $datos = [
            'fechaInicio' => $inicio,
            'fechaFin' => $fin,
            'consultado' => false,
            'errorFiltro' => '',
            'errorGeneral' => '',
            'filas' => [],
            'totales' => null,
            'tituloRango' => '',
        ];

        // Primera visita: solo se muestra el filtro.
        if (!$consultado) {
            $this->render($datos);
            return;
        }

        // Validación (la validación real también la hace el backend).
        $datos['errorFiltro'] = $this->validarFechas($inicio, $fin);
        if ($datos['errorFiltro'] !== '') {
            $this->render($datos);
            return;
        }

        $resultado = $this->consultarBalance($inicio, $fin);
        $datos['consultado'] = true;
        $datos['tituloRango'] = 'Balance General del ' . $this->fechaPantalla($inicio)
            . ' al ' . $this->fechaPantalla($fin);

        if (!$resultado['ok']) {
            $datos['errorGeneral'] = $resultado['error'];
            $this->render($datos);
            return;
        }

        [$datos['filas'], $datos['totales']] = $this->prepararFilas($resultado['datos']);
        $this->render($datos);
    }

    private function consultarBalance(string $inicio, string $fin): array
    {
        $resp = $this->api->getJson(
            '/reportes/balance-general?' . http_build_query(['fecha_inicio' => $inicio, 'fecha_fin' => $fin])
        );

        if ($resp['body'] === null || $resp['status'] === 0 || $resp['status'] >= 500) {
            return ['ok' => false, 'datos' => [], 'error' => 'El reporte no está disponible en este momento. Intenta más tarde.'];
        }

        $json = json_decode((string) $resp['body'], true);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $detalle = is_array($json) ? ($json['detail'] ?? null) : null;
            if (is_string($detalle) && $detalle !== '') {
                $mensaje = $detalle; // 400: mensaje que manda la BD
            } elseif ($resp['status'] === 401) {
                $mensaje = 'Tu sesión expiró. Inicia sesión de nuevo.';
            } elseif ($resp['status'] === 403) {
                $mensaje = 'No tienes permiso para ver este reporte.';
            } else {
                $mensaje = 'No se pudo generar el reporte. Verifica las fechas e inténtalo de nuevo.';
            }
            return ['ok' => false, 'datos' => [], 'error' => $mensaje];
        }

        if (!is_array($json) || !is_array($json['filas'] ?? null) || !is_array($json['totales'] ?? null)) {
            return ['ok' => false, 'datos' => [], 'error' => 'La API devolvió una respuesta inesperada.'];
        }

        return ['ok' => true, 'datos' => $json, 'error' => ''];
    }

    /** Devuelve el mensaje de error, o '' si las fechas están bien. */
    private function validarFechas(string $inicio, string $fin): string
    {
        if ($inicio === '' || $fin === '') {
            return 'La fecha de inicio y la fecha fin son obligatorias.';
        }
        if (!$this->fechaValida($inicio) || !$this->fechaValida($fin)) {
            return 'Alguna de las fechas no es válida.';
        }
        if ($inicio > $fin) {
            return 'La fecha de inicio no puede ser mayor que la fecha fin.';
        }
        return '';
    }

    private function fechaValida(string $valor): bool
    {
        $f = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        return $f !== false && $f->format('Y-m-d') === $valor;
    }

    // AAAA-MM-DD -> DD/MM/AAAA
    private function fechaPantalla(string $valor): string
    {
        return \DateTimeImmutable::createFromFormat('!Y-m-d', $valor)->format('d/m/Y');
    }

    /**
     * Da formato a las filas y a los totales que calcula la API.
     * Los saldos no se suman entre sí: una cuenta DEBITO y una CREDITO tienen saldos de
     * naturaleza distinta, así que la API devuelve un total deudor y uno acreedor.
     */
    private function prepararFilas(array $balance): array
    {
        $salida = [];
        foreach ($balance['filas'] as $fila) {
            $saldo = $this->aCentavos($fila['saldo'] ?? 0);
            $salida[] = [
                'cuenta' => (string) ($fila['nombre_cuenta'] ?? ''),
                'tipo' => ($fila['tipo'] ?? '') === 'CREDITO' ? 'Acreedora' : 'Deudora',
                'debito' => dinero($this->aCentavos($fila['total_debito'] ?? 0) / 100),
                'credito' => dinero($this->aCentavos($fila['total_credito'] ?? 0) / 100),
                'saldo' => dinero($saldo / 100),
                'negativo' => $saldo < 0,
            ];
        }

        $t = $balance['totales'];
        $deudor = $this->aCentavos($t['saldo_deudor'] ?? 0);
        $acreedor = $this->aCentavos($t['saldo_acreedor'] ?? 0);
        $totales = [
            'debito' => dinero($this->aCentavos($t['total_debito'] ?? 0) / 100),
            'credito' => dinero($this->aCentavos($t['total_credito'] ?? 0) / 100),
            'saldo_deudor' => dinero($deudor / 100),
            'saldo_acreedor' => dinero($acreedor / 100),
            'deudor_negativo' => $deudor < 0,
            'acreedor_negativo' => $acreedor < 0,
            'cuadra' => (bool) ($t['cuadra'] ?? false),
        ];

        return [$salida, $totales];
    }

    // 1250.75 -> 125075
    private function aCentavos(mixed $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }

    private function render(array $datos): void
    {
        extract($datos, EXTR_SKIP);
        $currentPage = 'reportes';
        $extraCss = 'reportes.css';

        // Ruta base de Public/ calculada, para que funcione en WAMP (/SiColMed-FrontEnd/Public) y en Docker (raíz).
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
        require self::VISTAS . 'balance_general.php';
        echo '<script src="' . e($base) . '/Assets/JavaScript/Dedicated/reportes.js" defer></script>';
        require __DIR__ . '/../Views/Partials/footer.php';   // el footer cierra </main> y </div>
    }
}