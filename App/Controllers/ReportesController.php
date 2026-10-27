<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ApiClient;

final class ReportesController
{
    private ApiClient $api;

    public function __construct()
    {
        $this->api = new ApiClient();
    }

    public function balance(): void
    {
        $this->verificarAcceso();

        $inicio = trim((string) ($_GET['inicio'] ?? ''));
        $fin = trim((string) ($_GET['fin'] ?? ''));
        $consultado = isset($_GET['inicio']) || isset($_GET['fin']);

        $datos = [
            'inicio' => $inicio,
            'fin' => $fin,
            'consultado' => false,
            'errores' => [],
            'errorGeneral' => '',
            'filas' => [],
            'totales' => null,
            'tituloRango' => '',
        ];

        if (!$consultado) {
            $this->render($datos);
            return;
        }

        $errores = $this->validarFechas($inicio, $fin);
        if ($errores !== []) {
            $datos['errores'] = $errores;
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

        [$filas, $totales] = $this->prepararFilas($resultado['datos']);
        $datos['filas'] = $filas;
        $datos['totales'] = $totales;
        $this->render($datos);
    }

    
    private function consultarBalance(string $inicio, string $fin): array
    {
        $resp = $this->api->getJson(
            '/reportes/balance-general?fecha_inicio=' . urlencode($inicio) . '&fecha_fin=' . urlencode($fin)
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

        if (!is_array($json) || !isset($json['filas']) || !is_array($json['filas'])) {
            return ['ok' => false, 'datos' => [], 'error' => 'La API devolvió una respuesta inesperada.'];
        }

        return ['ok' => true, 'datos' => $json['filas'], 'error' => ''];
    }

    // Pendiente: validar rol admin_asociacion cuando exista App/Middleware/Auth.php.
    private function verificarAcceso(): void
    {
    }

    /** @return array<string,string> errores por campo */
    private function validarFechas(string $inicio, string $fin): array
    {
        $errores = [];

        if ($inicio === '') {
            $errores['inicio'] = 'La fecha de inicio es obligatoria.';
        } elseif (!$this->fechaValida($inicio)) {
            $errores['inicio'] = 'La fecha de inicio no es válida.';
        }

        if ($fin === '') {
            $errores['fin'] = 'La fecha fin es obligatoria.';
        } elseif (!$this->fechaValida($fin)) {
            $errores['fin'] = 'La fecha fin no es válida.';
        }

        if ($errores === [] && $inicio > $fin) {
            $errores['fin'] = 'La fecha de inicio no puede ser mayor que la fecha fin.';
        }

        return $errores;
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


    private function prepararFilas(array $filas): array
    {
        $sumaDebito = 0;
        $sumaCredito = 0;
        $sumaSaldo = 0;
        $salida = [];

        foreach ($filas as $fila) {
            $debito = $this->aCentavos($fila['total_debito'] ?? '0');
            $credito = $this->aCentavos($fila['total_credito'] ?? '0');
            $saldo = $this->aCentavos($fila['saldo'] ?? '0');

            $sumaDebito += $debito;
            $sumaCredito += $credito;
            $sumaSaldo += $saldo;

            $salida[] = [
                'cuenta' => (string) ($fila['nombre_cuenta'] ?? ''),
                'debito' => $this->dinero($debito),
                'credito' => $this->dinero($credito),
                'saldo' => $this->dinero($saldo),
                'negativo' => $saldo < 0,
            ];
        }

        $totales = [
            'debito' => $this->dinero($sumaDebito),
            'credito' => $this->dinero($sumaCredito),
            'saldo' => $this->dinero($sumaSaldo),
            'negativo' => $sumaSaldo < 0,
        ];

        return [$salida, $totales];
    }

   
    private function aCentavos(mixed $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }
    // Pendiente: cambiar por App/Helpers/dinero.php.
    private function dinero(int $centavos): string
    {
        $signo = $centavos < 0 ? '-' : '';
        $abs = abs($centavos);
        return $signo . number_format(intdiv($abs, 100), 0, '.', ',') . '.' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    private function render(array $datos): void
    {
        extract($datos, EXTR_SKIP);
        $currentPage = 'reportes';

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<link rel="stylesheet" href="/Assets/CSS/base.css">';
        echo '<link rel="stylesheet" href="/Assets/CSS/Dedicated/reportes.css">';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
        require __DIR__ . '/../Views/Contabilidad/Reportes/balance_general.php';
        echo '<script src="/Assets/JavaScript/Dedicated/reportes.js"></script>';
        require __DIR__ . '/../Views/Partials/footer.php';
    }
}