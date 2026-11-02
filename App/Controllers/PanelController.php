<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CuentasRepository;
use App\Repositories\MovimientosRepository;
use App\Repositories\RegistroMedicoRepository;
use App\Repositories\SolicitudesRepository;

/**
 * PanelController · Pantalla de inicio tras iniciar sesión.
 * - admin_cmp: Indicadores globales, accesos rápidos y solicitudes recientes.
 * - medico: Estado de certificación personal y solicitudes de cambio.
 */
final class PanelController extends BaseController
{
    private const ENTRADA = '/index.php';
    private const PERFIL  = '/index.php?c=perfil&a=';

    public function index(): void
    {
        if (\Auth::es(\Auth::ADMIN)) {
            $this->admin();
            return;
        }
        $this->medico();
    }

    private function admin(): void
    {
        $hoy = date('Y-m-d');
        $solicitudesRepo = new SolicitudesRepository();
        $registroRepo = new RegistroMedicoRepository();
        $cuentasRepo = new CuentasRepository();

        $solicitudesResp = $solicitudesRepo->listar();
        $listaSolicitudes = $solicitudesResp['ok'] && is_array($solicitudesResp['datos']) ? $solicitudesResp['datos'] : [];
        $pendientesCount = count(array_filter($listaSolicitudes, fn(array $s) => strtoupper($s['estado'] ?? '') === 'PENDIENTE'));

        // Certificaciones por vencer / no vigentes
        $directorioResp = $registroRepo->directorio(['certificacion' => 'no_vigente']);
        $noVigentesCount = $directorioResp['ok'] && isset($directorioResp['datos']['total'])
            ? (int) $directorioResp['datos']['total']
            : 0;

        $estadisticas = [
            ['etiqueta' => 'Médicos registrados',        'valor' => $this->contar(fn() => $registroRepo->total())],
            ['etiqueta' => 'Solicitudes pendientes',     'valor' => (string) $pendientesCount],
            ['etiqueta' => 'Certificaciones no vigentes','valor' => (string) $noVigentesCount],
            ['etiqueta' => 'Cuentas activas',            'valor' => $this->contar(fn() => $this->total($cuentasRepo->listar(true)))],
            ['etiqueta' => 'Movimientos de hoy',         'valor' => $this->contar(fn() => $this->movimientosDelDia($hoy))],
            ['etiqueta' => 'Saldo del mes',              'valor' => $this->saldoMesEstimado()],
        ];

        // Mapear solicitudes para la tabla del panel
        $solicitudes = array_map(static function (array $s): array {
            $cambios = $s['cambios'] ?? [];
            $campo = is_array($cambios) && $cambios !== [] ? implode(', ', array_keys($cambios)) : 'Datos generales';
            $estadoStr = ucfirst(strtolower((string) ($s['estado'] ?? 'Pendiente')));
            return [
                'medico' => (string) ($s['nombre_completo'] ?? 'Médico'),
                'campo'  => ucfirst(str_replace('_', ' ', $campo)),
                'fecha'  => isset($s['fecha_solicitud']) ? date('d/m/Y', strtotime((string) $s['fecha_solicitud'])) : '—',
                'estado' => $estadoStr === 'Procesado' ? 'Procesada' : $estadoStr,
            ];
        }, array_slice($listaSolicitudes, 0, 5));

        $this->render('Admin/Panel/index', [
            'estadisticas'  => $estadisticas,
            'solicitudes'   => $solicitudes,
            'esEjemplo'     => \Auth::modoPrueba(),
            'hayPendientes' => in_array('—', array_column($estadisticas, 'valor'), true),
            'urls'          => [
                'nuevo'       => self::ENTRADA . '?c=registro&a=nuevo',
                'solicitudes' => self::ENTRADA . '?c=solicitudes&a=index',
                'directorio'  => self::ENTRADA . '?c=directorio&a=index',
                'cuentas'     => self::ENTRADA . '?c=cuentas&a=index',
                'movimientos' => self::ENTRADA . '?c=movimientos&a=index',
                'balance'     => self::ENTRADA . '?c=reportes&a=balance',
            ],
        ], 'panel', 'panel.css');
    }

    private function medico(): void
    {
        $idMedico = $this->obtenerIdMedicoSesion();
        $registroRepo = new RegistroMedicoRepository();
        $solicitudesRepo = new SolicitudesRepository();

        $medicoResp = $registroRepo->obtener($idMedico);
        $vigente = $medicoResp['ok'] && !empty($medicoResp['datos']['certificacion_vigente']);

        $solicitudesResp = $solicitudesRepo->listar();
        $todas = $solicitudesResp['ok'] && is_array($solicitudesResp['datos']) ? $solicitudesResp['datos'] : [];
        $misPendientes = count(array_filter($todas, fn(array $s) => ($s['id_medico'] ?? 0) === $idMedico && strtoupper($s['estado'] ?? '') === 'PENDIENTE'));

        $this->render('Client/Panel/index', [
            'estadisticas' => [
                ['etiqueta' => 'Estado de mi certificación', 'valor' => $vigente ? 'Vigente' : 'Por renovar'],
                ['etiqueta' => 'Mis solicitudes pendientes', 'valor' => (string) $misPendientes],
            ],
            'urls' => [
                'perfil'     => self::PERFIL . 'index',
                'editar'     => self::PERFIL . 'editar',
                'directorio' => self::ENTRADA . '?c=directorio&a=index',
            ],
        ], 'panel', 'panel.css');
    }

    private function obtenerIdMedicoSesion(): int
    {
        if (isset($_SESSION['usuario_datos']['id_medico']) && is_numeric($_SESSION['usuario_datos']['id_medico'])) {
            return (int) $_SESSION['usuario_datos']['id_medico'];
        }
        if (isset($_SESSION['usuario_datos']['id']) && is_numeric($_SESSION['usuario_datos']['id'])) {
            return (int) $_SESSION['usuario_datos']['id'];
        }
        return 1;
    }

    private function contar(callable $consulta): string
    {
        try {
            $n = $consulta();
            return $n === null ? '—' : (string) $n;
        } catch (\Throwable) {
            return '—';
        }
    }

    private function total(array $resultado): ?int
    {
        return ($resultado['ok'] ?? false) && is_array($resultado['datos']) ? count($resultado['datos']) : null;
    }

    private function movimientosDelDia(string $fecha): ?int
    {
        $res = (new MovimientosRepository())->listarPorFecha($fecha);
        if (!($res['ok'] ?? false) || !is_array($res['datos'])) {
            return 0;
        }
        return count(array_unique(array_column($res['datos'], 'id_movimiento')));
    }

    private function saldoMesEstimado(): string
    {
        try {
            $primerDiaMes = date('Y-m-01');
            $hoy = date('Y-m-d');
            $movRepo = new MovimientosRepository();
            $res = $movRepo->listarPorFecha($hoy);
            if (!($res['ok'] ?? false) || !is_array($res['datos'])) {
                return 'B/. 0.00';
            }
            $suma = 0;
            foreach ($res['datos'] as $linea) {
                $monto = (float) ($linea['monto'] ?? 0);
                if (strtoupper($linea['tipo'] ?? '') === 'DEBITO') {
                    $suma += $monto;
                } else {
                    $suma -= $monto;
                }
            }
            return 'B/. ' . number_format($suma, 2, '.', ',');
        } catch (\Throwable) {
            return '—';
        }
    }
}
