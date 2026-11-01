<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CuentasRepository;
use App\Repositories\MovimientosRepository;
use DateTime;

require_once __DIR__ . '/../Helpers/ui.php';

/**
 * MovimientosController · Módulo 5 · Requisito 5.2 · rol: tesorero
 *
 * URLs (entran por Public/index.php):
 *   ?c=movimientos&a=index&fecha=AAAA-MM-DD       movimientos del día
 *   ?c=movimientos&a=crear                        formulario y guardado (POST)
 *   ?c=movimientos&a=porCuenta&id=3&inicio=&fin=  historial de una cuenta
 */
final class MovimientosController
{
    private const ENTRADA = '/index.php';
    private const VISTAS = __DIR__ . '/../Views/Admin/Contabilidad/Movimientos/';
    private const TIPOS = ['DEBITO', 'CREDITO'];

    private MovimientosRepository $movimientos;
    private CuentasRepository $cuentas;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->movimientos = new MovimientosRepository();
        $this->cuentas = new CuentasRepository();
    }

    // ------------------------------------------------------------------
    // Movimientos del día
    // ------------------------------------------------------------------
    public function index(): void
    {
        $this->verificarAcceso();

        $errorFecha = null;
        $errorApi = null;
        $grupos = [];
        $totales = ['debito' => '0.00', 'credito' => '0.00'];

        $fecha = date('Y-m-d');
        if (array_key_exists('fecha', $_GET)) {
            $pedida = $this->fechaValida((string) $_GET['fecha']);
            if ($pedida === null) {
                $errorFecha = 'La fecha es obligatoria. Elige una fecha para consultar.';
            } else {
                $fecha = $pedida;
            }
        }

        if ($errorFecha === null) {
            $res = $this->movimientos->listarPorFecha($fecha);
            if ($res['ok']) {
                $grupos = $this->agrupar($res['datos']);
                $totales = $this->totales($res['datos']);
            } else {
                $errorApi = $res['error'] !== '' ? $res['error'] : 'No se pudieron cargar los movimientos.';
            }
        }

        $this->render('index', [
            'fecha'      => $fecha,
            'fecha_ui'   => $this->fechaUi($fecha),
            'grupos'     => $grupos,
            'totales'    => $totales,
            'errorFecha' => $errorFecha,
            'errorApi'   => $errorApi,
            'flash'      => $this->tomarFlash(),
        ]);
    }

    // ------------------------------------------------------------------
    // Registrar movimiento (GET muestra el formulario, POST guarda)
    // ------------------------------------------------------------------
    public function crear(): void
    {
        $this->verificarAcceso();

        $errorApi = null;
        $errores = [];
        $activas = [];

        $old = [
            'fecha'       => $this->fechaValida((string) ($_GET['fecha'] ?? '')) ?? date('Y-m-d'),
            'descripcion' => '',
            'id_cuenta'   => '',
            'monto'       => '',
            'tipo'        => '',
        ];

        // Solo cuentas activas: una cuenta inactiva no recibe movimientos
        $res = $this->cuentas->listar(true);
        if ($res['ok']) {
            $activas = $res['datos'];
        } else {
            $errorApi = $res['error'] !== '' ? $res['error'] : 'No se pudieron cargar las cuentas.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorApi === null) {
            $old = [
                'fecha'       => trim((string) ($_POST['fecha'] ?? '')),
                'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
                'id_cuenta'   => (string) ($_POST['id_cuenta'] ?? ''),
                'monto'       => trim((string) ($_POST['monto'] ?? '')),
                'tipo'        => (string) ($_POST['tipo'] ?? ''),
            ];

            if (!$this->csrfValido()) {
                $errorApi = 'La sesión del formulario venció. Intenta de nuevo.';
            } else {
                $errores = $this->validar($old, $activas);
                if ($errores === []) {
                    $guardado = $this->movimientos->registrar($old);
                    if ($guardado['ok']) {
                        $_SESSION['movimientos_flash'] = ['tipo' => 'success', 'texto' => 'Movimiento registrado'];
                        $this->redirigir(self::ENTRADA . '?' . http_build_query([
                            'c' => 'movimientos', 'a' => 'index', 'fecha' => $old['fecha'],
                        ]));
                    }
                    $errores = $guardado['errores'];
                    if ($errores === []) {
                        $errorApi = $guardado['error'] !== '' ? $guardado['error'] : 'No se pudo registrar el movimiento.';
                    }
                }
            }
        }

        $this->render('form', [
            'old'      => $old,
            'errores'  => $errores,
            'cuentas'  => $activas,
            'errorApi' => $errorApi,
            'csrf'     => $this->tokenCsrf(),
        ]);
    }

    // ------------------------------------------------------------------
    // Historial de una cuenta
    // ------------------------------------------------------------------
    public function porCuenta(): void
    {
        $this->verificarAcceso();

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

        $res = $this->cuentas->listar();
        if (!$res['ok']) {
            $errorApi = $res['error'] !== '' ? $res['error'] : 'No se pudieron cargar las cuentas.';
        } else {
            $cuentas = $res['datos'];

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
                    $mov = $this->movimientos->listarPorCuenta($idCuenta, $inicio, $fin);
                    if ($mov['ok']) {
                        $lineas = $mov['datos'];
                        foreach ($lineas as &$l) {
                            $l['fecha_ui'] = $this->fechaUi($l['fecha']);
                        }
                        unset($l);
                    } else {
                        $errorApi = $mov['error'] !== '' ? $mov['error'] : 'No se pudieron cargar los movimientos.';
                    }
                }
            }
        }

        $this->render('por_cuenta', [
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
    // Auxiliares privados (no se pueden llamar desde la URL)
    // ==================================================================

    // Solo el tesorero. El router ya lo revisa con Auth::exigir; esto es una segunda barrera.
    private function verificarAcceso(): void
    {
        if (class_exists('Auth')) {
            \Auth::exigir('movimientos');
        }
    }

    private function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        $entrada = self::ENTRADA;
        $currentPage = 'movimientos';
        $extraCss = 'movimientos.css';

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
        require self::VISTAS . $vista . '.php';
        echo '<script src="/Assets/JavaScript/Dedicated/movimientos.js" defer></script>';
        require __DIR__ . '/../Views/Partials/footer.php';   // el footer cierra </main> y </div>
    }

    // Validación básica en el servidor. La validación final es del backend.
    private function validar(array $d, array $cuentasActivas): array
    {
        $errores = [];

        if ($this->fechaValida($d['fecha']) === null) {
            $errores['fecha'] = 'La fecha es obligatoria.';
        }

        if ($d['descripcion'] === '') {
            $errores['descripcion'] = 'Escribe una descripción.';
        } elseif (strlen($d['descripcion']) > 200) {
            $errores['descripcion'] = 'La descripción no puede pasar de 200 caracteres.';
        }

        $ids = array_map(fn(array $c) => (string) $c['id_cuenta'], $cuentasActivas);
        if ($d['id_cuenta'] === '') {
            $errores['id_cuenta'] = 'Elige una cuenta.';
        } elseif (!in_array($d['id_cuenta'], $ids, true)) {
            $errores['id_cuenta'] = 'La cuenta elegida no está disponible.';
        }

        if ($d['monto'] === '' || !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $d['monto'])) {
            $errores['monto'] = 'Escribe un monto válido (máximo 2 decimales).';
        } elseif ((float) $d['monto'] <= 0) {
            $errores['monto'] = 'El monto debe ser mayor que cero.';
        }

        if (!in_array($d['tipo'], self::TIPOS, true)) {
            $errores['tipo'] = 'Elige Débito o Crédito.';
        }

        return $errores;
    }

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

    // Junta las líneas que pertenecen al mismo movimiento
    private function agrupar(array $lineas): array
    {
        $grupos = [];
        foreach ($lineas as $l) {
            $id = $l['id_movimiento'];
            if (!isset($grupos[$id])) {
                $grupos[$id] = ['id_movimiento' => $id, 'descripcion' => $l['descripcion'], 'lineas' => []];
            }
            $grupos[$id]['lineas'][] = $l;
        }
        return array_values($grupos);
    }

    // Totales del día calculados en centavos para no perder decimales
    private function totales(array $lineas): array
    {
        $centavos = ['DEBITO' => 0, 'CREDITO' => 0];
        foreach ($lineas as $l) {
            if (isset($centavos[$l['tipo']])) {
                $centavos[$l['tipo']] += (int) round(((float) $l['monto']) * 100);
            }
        }
        $fmt = fn(int $c) => intdiv($c, 100) . '.' . str_pad((string) ($c % 100), 2, '0', STR_PAD_LEFT);

        return ['debito' => $fmt($centavos['DEBITO']), 'credito' => $fmt($centavos['CREDITO'])];
    }

    private function tokenCsrf(): string
    {
        if (empty($_SESSION['movimientos_csrf'])) {
            $_SESSION['movimientos_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['movimientos_csrf'];
    }

    private function csrfValido(): bool
    {
        $enviado = (string) ($_POST['csrf'] ?? '');
        $guardado = (string) ($_SESSION['movimientos_csrf'] ?? '');
        return $guardado !== '' && hash_equals($guardado, $enviado);
    }

    private function tomarFlash(): ?array
    {
        $flash = $_SESSION['movimientos_flash'] ?? null;
        unset($_SESSION['movimientos_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function redirigir(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}