<?php
declare(strict_types=1);

namespace App\Controllers;

// Importamos las herramientas del sistema que construyó tu equipo
use App\Services\ApiClient;
use Exception as ApiException; // Asumiendo que ApiException hereda de Exception, o ajústalo si tu equipo tiene una clase específica
use View; // Ajusta esto si tu equipo usa un namespace específico para View (ej: use App\Core\View;)
use Auth; // Ajusta esto si tu equipo usa un namespace para Auth (ej: use App\Middleware\Auth;)
use DateTime;

/**
 * MovimientosController · Módulo 5 · P2 Pablo · Requisito 5.2
 *
 * Acciones:  index (movimientos del día) · crear (formulario + guardado) · porCuenta (historial)
 * Rol:       tesorero
 */
class MovimientosController
{
    public const ACCIONES = ['index', 'crear', 'porCuenta'];

    private const VISTAS = 'Contabilidad/Movimientos/';

    public function __construct()
    {
        // Si Auth requiere un namespace estricto, asegúrate de importarlo arriba
        Auth::requiere(['tesorero']);
    }

    // ------------------------------------------------------------------
    // GET /movimientos?fecha=AAAA-MM-DD
    // ------------------------------------------------------------------
    public function index(): void
    {
        $hoy = date('Y-m-d');
        $errorFecha = null;
        $errorApi = null;
        $grupos = [];
        $totales = ['debito' => '0.00', 'credito' => '0.00'];

        $fecha = $hoy;
        if (array_key_exists('fecha', $_GET)) {
            $pedida = $this->fechaValida((string) $_GET['fecha']);
            if ($pedida === null) {
                $errorFecha = 'La fecha es obligatoria. Elige una fecha para consultar.';
            } else {
                $fecha = $pedida;
            }
        }

        if ($errorFecha === null) {
            try {
                $lineas = ApiClient::get('/movimientos', ['fecha' => $fecha]);
                $grupos = $this->agrupar($lineas);
                $totales = $this->totales($lineas);
            } catch (ApiException $ex) {
                $errorApi = $ex->getMessage();
            }
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        View::render(self::VISTAS . 'index', [
            'titulo'     => 'Movimientos diarios',
            'css'        => ['movimientos'],
            'js'         => ['movimientos'],
            'fecha'      => $fecha,
            'fecha_ui'   => $this->fechaUi($fecha),
            'grupos'     => $grupos,
            'totales'    => $totales,
            'errorFecha' => $errorFecha,
            'errorApi'   => $errorApi,
            'flash'      => $flash,
        ]);
    }

    // ------------------------------------------------------------------
    // GET/POST /movimientos/crear 
    // ------------------------------------------------------------------
    public function crear(): void
    {
        $errorApi = null;
        $errores = [];
        $cuentas = [];

        $old = [
            'fecha'       => $this->fechaValida((string) ($_GET['fecha'] ?? '')) ?? date('Y-m-d'),
            'descripcion' => '',
            'id_cuenta'   => '',
            'monto'       => '',
            'tipo'        => '',
        ];

        try {
            $cuentas = ApiClient::get('/cuentas', ['activa' => '1']);
        } catch (ApiException $ex) {
            $errorApi = $ex->getMessage();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorApi === null) {
            $old = [
                'fecha'       => trim((string) ($_POST['fecha'] ?? '')),
                'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
                'id_cuenta'   => (string) ($_POST['id_cuenta'] ?? ''),
                'monto'       => trim((string) ($_POST['monto'] ?? '')),
                'tipo'        => (string) ($_POST['tipo'] ?? ''),
            ];

            try {
                $respuesta = ApiClient::post('/movimientos', $old);

                if (!empty($respuesta['errores'])) {
                    $errores = $respuesta['errores'];
                } else {
                    $_SESSION['flash'] = ['tipo' => 'success', 'texto' => 'Movimiento registrado'];
                    // ADAPTACIÓN: Redirección con URL limpia
                    header('Location: /movimientos?' . http_build_query([
                        'fecha' => $old['fecha'],
                    ]));
                    exit;
                }
            } catch (ApiException $ex) {
                $errorApi = $ex->getMessage();
            }
        }

        View::render(self::VISTAS . 'form', [
            'titulo'   => 'Registrar movimiento',
            'css'      => ['movimientos'],
            'js'       => ['movimientos'],
            'old'      => $old,
            'errores'  => $errores,
            'cuentas'  => $cuentas,
            'errorApi' => $errorApi,
        ]);
    }

    // ------------------------------------------------------------------
    // GET /movimientos/cuenta
    // ------------------------------------------------------------------
    public function porCuenta(): void
    {
        $errorApi = null;
        $errorRango = null;
        $errorCuenta = null;
        $cuentas = [];
        $cuenta = null;
        $lineas = null;

        $inicio = date('Y-m-01');
        $fin = date('Y-m-d');
        $idCuenta = (int) ($_GET['id'] ?? 0);
        $consulto = isset($_GET['id']) || isset($_GET['inicio']) || isset($_GET['fin']);

        if ($consulto) {
            $inicioOk = $this->fechaValida((string) ($_GET['inicio'] ?? ''));
            $finOk = $this->fechaValida((string) ($_GET['fin'] ?? ''));

            if ($inicioOk === null || $finOk === null) {
                $errorRango = 'Las dos fechas son obligatorias: fecha inicio y fecha fin.';
            } else {
                $inicio = $inicioOk;
                $fin = $finOk;
                if ($inicio > $fin) {
                    $errorRango = 'La fecha de inicio no puede ser mayor que la fecha fin.';
                }
            }
        }

        try {
            $cuentas = ApiClient::get('/cuentas');

            if ($consulto && $idCuenta <= 0) {
                $errorCuenta = 'Elige una cuenta.';
            }

            if ($idCuenta > 0) {
                foreach ($cuentas as $c) {
                    if ((int) $c['id_cuenta'] === $idCuenta) {
                        $cuenta = $c;
                    }
                }
                if ($cuenta === null) {
                    $errorCuenta = 'La cuenta elegida no existe.';
                } elseif ($errorRango === null) {
                    $lineas = ApiClient::get("/cuentas/{$idCuenta}/movimientos", [
                        'inicio' => $inicio,
                        'fin'    => $fin,
                    ]);
                    foreach ($lineas as &$l) {
                        $l['fecha_ui'] = $this->fechaUi($l['fecha']);
                    }
                    unset($l);
                }
            }
        } catch (ApiException $ex) {
            $errorApi = $ex->getMessage();
            $lineas = null;
        }

        View::render(self::VISTAS . 'por_cuenta', [
            'titulo'      => 'Movimientos por cuenta',
            'css'         => ['movimientos'],
            'js'          => ['movimientos'],
            'cuentas'     => $cuentas,
            'cuenta'      => $cuenta,
            'idCuenta'    => $idCuenta,
            'inicio'      => $inicio,
            'fin'         => $fin,
            'inicio_ui'   => $this->fechaUi($inicio),
            'fin_ui'      => $this->fechaUi($fin),
            'lineas'      => $lineas,
            'errorApi'    => $errorApi,
            'errorRango'  => $errorRango,
            'errorCuenta' => $errorCuenta,
        ]);
    }

    // ==================================================================
    // Auxiliares privados
    // ==================================================================

    private function fechaValida(string $texto): ?string
    {
        $d = DateTime::createFromFormat('Y-m-d', $texto);
        return ($d && $d->format('Y-m-d') === $texto) ? $texto : null;
    }

    private function fechaUi(string $fecha): string
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d ? $d->format('d/m/Y') : $fecha;
    }

    private function agrupar(array $lineas): array
    {
        $grupos = [];
        foreach ($lineas as $l) {
            $id = $l['id_movimiento'];
            if (!isset($grupos[$id])) {
                $grupos[$id] = [
                    'id_movimiento' => $id,
                    'descripcion'   => $l['descripcion'],
                    'lineas'        => [],
                ];
            }
            $grupos[$id]['lineas'][] = $l;
        }
        return array_values($grupos);
    }

    private function totales(array $lineas): array
    {
        $centavos = ['DEBITO' => 0, 'CREDITO' => 0];
        foreach ($lineas as $l) {
            if (isset($centavos[$l['tipo']])) {
                $centavos[$l['tipo']] += (int) round(((float) $l['monto']) * 100);
            }
        }
        $fmt = fn(int $c) => intdiv($c, 100) . '.' . str_pad((string) ($c % 100), 2, '0', STR_PAD_LEFT);

        return [
            'debito'  => $fmt($centavos['DEBITO']),
            'credito' => $fmt($centavos['CREDITO']),
        ];
    }
}