<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ReportesRepository;

/**
 * ReportesController · Reporte de Balance General (Tesorero y Administradores).
 */
final class ReportesController extends BaseController
{
    private ReportesRepository $reportes;

    public function __construct()
    {
        $this->reportes = new ReportesRepository();
    }

    public function balance(): void
    {
        $inicio = trim((string) ($_GET['inicio'] ?? ''));
        $fin = trim((string) ($_GET['fin'] ?? ''));
        $consultado = isset($_GET['inicio']) || isset($_GET['fin']);

        $datos = [
            'fechaInicio'  => $inicio,
            'fechaFin'     => $fin,
            'consultado'   => false,
            'errorFiltro'  => '',
            'errorGeneral' => '',
            'filas'        => [],
            'totales'      => null,
            'tituloRango'  => '',
        ];

        // Primera visita: solo se muestra el formulario de fechas
        if (!$consultado) {
            $this->mostrar($datos);
            return;
        }

        // Validación de fechas
        $datos['errorFiltro'] = $this->validarRangoFechas($inicio, $fin);
        if ($datos['errorFiltro'] !== '') {
            $this->mostrar($datos);
            return;
        }

        $resultado = $this->reportes->obtenerBalanceGeneral($inicio, $fin);
        $datos['consultado'] = true;
        $datos['tituloRango'] = 'Balance General del ' . $this->fechaPantalla($inicio)
            . ' al ' . $this->fechaPantalla($fin);

        if (!$resultado['ok']) {
            $datos['errorGeneral'] = $resultado['error'];
            $this->mostrar($datos);
            return;
        }

        [$datos['filas'], $datos['totales']] = $this->prepararFilas($resultado['datos']);
        $this->mostrar($datos);
    }

    private function validarRangoFechas(string $inicio, string $fin): string
    {
        if ($inicio === '' || $fin === '') {
            return 'La fecha de inicio y la fecha fin son obligatorias.';
        }
        if ($this->fechaValida($inicio) === null || $this->fechaValida($fin) === null) {
            return 'Alguna de las fechas no es válida.';
        }
        if ($inicio > $fin) {
            return 'La fecha de inicio no puede ser mayor que la fecha fin.';
        }
        return '';
    }

    /**
     * Da formato a los montos y calcula la fila de totales.
     */
    private function prepararFilas(array $filas): array
    {
        $sumaDebito = 0;
        $sumaCredito = 0;
        $sumaSaldo = 0;
        $salida = [];

        foreach ($filas as $fila) {
            $debito = (int) round(((float) ($fila['total_debito'] ?? 0)) * 100);
            $credito = (int) round(((float) ($fila['total_credito'] ?? 0)) * 100);
            $saldo = (int) round(((float) ($fila['saldo'] ?? 0)) * 100);

            $sumaDebito += $debito;
            $sumaCredito += $credito;
            $sumaSaldo += $saldo;

            $salida[] = [
                'cuenta'   => (string) ($fila['nombre_cuenta'] ?? ''),
                'debito'   => dinero($debito / 100),
                'credito'  => dinero($credito / 100),
                'saldo'    => dinero($saldo / 100),
                'negativo' => $saldo < 0,
            ];
        }

        $totales = [
            'debito'   => dinero($sumaDebito / 100),
            'credito'  => dinero($sumaCredito / 100),
            'saldo'    => dinero($sumaSaldo / 100),
            'negativo' => $sumaSaldo < 0,
        ];

        return [$salida, $totales];
    }

    private function mostrar(array $datos): void
    {
        $this->render(
            'Admin/Contabilidad/Reportes/balance_general',
            $datos,
            'reportes',
            'reportes.css',
            false,
            'reportes.js'
        );
    }
}
