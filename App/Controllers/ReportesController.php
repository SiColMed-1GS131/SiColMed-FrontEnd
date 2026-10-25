<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BalanceGeneralRepository;

/** Reporte Balance General (5.3.a). Solo para el Administrador de la Asociación. */
final class ReportesController
{
    private BalanceGeneralRepository $balance;

    public function __construct()
    {
        $this->balance = new BalanceGeneralRepository();
    }

    // GET /reportes/balance?inicio=AAAA-MM-DD&fin=AAAA-MM-DD
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

        // Primera visita: solo se muestra el filtro.
        if (!$consultado) {
            $this->render($datos);
            return;
        }

        // Validación (la validación real también la hace el backend).
        $errores = $this->validarFechas($inicio, $fin);
        if ($errores !== []) {
            $datos['errores'] = $errores;
            $this->render($datos);
            return;
        }

        $resultado = $this->balance->obtener($inicio, $fin);
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

    // Pendiente: validar rol admin_asociacion cuando exista App/Middleware/Auth.php (Lione).
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

    /**
     * Da formato a los montos para mostrar y calcula la fila de totales.
     * Si la API algún día envía los totales, se usan esos y se borra esta suma.
     * Se suma en centavos (enteros) para evitar errores de decimales.
     *
     * @return array{0: array<int,array<string,mixed>>, 1: array<string,mixed>}
     */
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

    // "1250.75" -> 125075
    private function aCentavos(mixed $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }

    // 125075 -> "1,250.75" ; -5000 -> "-50.00"
    // Pendiente: cambiar por App/Helpers/dinero.php cuando Miguel lo suba a develop.
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
        // footer.php cierra </main> y el contenedor .app-layout.
        echo '<script src="/Assets/JavaScript/Dedicated/reportes.js"></script>';
        require __DIR__ . '/../Views/Partials/footer.php';
    }
}