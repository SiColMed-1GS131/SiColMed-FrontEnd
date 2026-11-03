<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CuentasRepository;
use App\Repositories\MovimientosRepository;
use App\Repositories\RegistroMedicoRepository;
use App\Services\ApiClient;

require_once __DIR__ . '/../Helpers/ui.php';   // dinero()

/**
 * PanelController · inicio de quien ya está dentro del sistema.
 *
 *   ?c=panel&a=index
 *     rol admin  → Views/Admin/Panel/index.php   (ve también la gestión financiera)
 *     otro rol   → Views/Client/Panel/index.php  (sin gestión financiera)
 *
 * Mientras no haya login, se prueba con USE_MOCK y  &rol=admin  en la dirección.
 */
final class PanelController
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

    // ------------------------------------------------------------------
    private function admin(): void
    {
        $hoy = date('Y-m-d');

        // GET /solicitudes-cambio (solo admin): sirve para el contador y la tabla
        $todasSolicitudes = (new ApiClient())->getJson('/solicitudes-cambio');
        $listaSolicitudes = $todasSolicitudes['status'] === 200 ? json_decode((string) $todasSolicitudes['body'], true) : null;

        $estadisticas = [
            ['etiqueta' => 'Médicos registrados',        'valor' => $this->contar(fn() => $this->totalMedicos())],
            ['etiqueta' => 'Solicitudes pendientes',     'valor' => $this->contar(fn() => is_array($listaSolicitudes)
                ? count(array_filter($listaSolicitudes, fn($s) => ($s['estado'] ?? '') === 'PENDIENTE')) : null)],
            ['etiqueta' => 'Certificaciones por vencer', 'valor' => '—'],   // pendiente: no hay endpoint
            ['etiqueta' => 'Cuentas activas',            'valor' => $this->contar(fn() => $this->total((new CuentasRepository())->listar(true)))],
            ['etiqueta' => 'Movimientos de hoy',         'valor' => $this->contar(fn() => $this->movimientosDelDia($hoy))],
            ['etiqueta' => 'Saldo del mes',              'valor' => $this->contar(fn() => $this->saldoDelMes($hoy))],
        ];

        // En modo de prueba se muestran filas de ejemplo; con la API real vienen de /solicitudes-cambio
        $esEjemplo = \Auth::modoPrueba();
        $solicitudes = $esEjemplo ? [
            ['medico' => 'Ana Pérez',    'campo' => 'Cargo laboral',      'fecha' => '28/10/2026', 'estado' => 'Pendiente'],
            ['medico' => 'Luis Gómez',   'campo' => 'Teléfono de trabajo', 'fecha' => '27/10/2026', 'estado' => 'Pendiente'],
            ['medico' => 'María Rivera', 'campo' => 'Especialidad',        'fecha' => '25/10/2026', 'estado' => 'Procesada'],
        ] : $this->solicitudesRecientes(is_array($listaSolicitudes) ? $listaSolicitudes : []);

        $this->render('Admin/Panel/index', [
            'estadisticas' => $estadisticas,
            'solicitudes'  => $solicitudes,
            'esEjemplo'    => $esEjemplo,
            'hayPendientes' => in_array('—', array_column($estadisticas, 'valor'), true),
            'urls' => [
                'nuevo'      => self::ENTRADA . '?c=registro&a=nuevo',
                'directorio' => self::ENTRADA . '?c=directorio&a=index',
                'cuentas'    => self::ENTRADA . '?c=cuentas&a=index',
                'movimientos' => self::ENTRADA . '?c=movimientos&a=index',
                'balance'    => self::ENTRADA . '?c=reportes&a=balance',
            ],
        ]);
    }

    // ------------------------------------------------------------------
    private function medico(): void
    {
        $this->render('Client/Panel/index', [
            'estadisticas' => [
                ['etiqueta' => 'Estado de mi certificación', 'valor' => $this->estadoCertificacion()],
                ['etiqueta' => 'Mis solicitudes de cambio',  'valor' => '—'],   // pendiente: GET /solicitudes-cambio es solo del admin
            ],
            'urls' => [
                'perfil'     => self::PERFIL . 'index',
                'editar'     => self::PERFIL . 'editar',
                'directorio' => self::ENTRADA . '?c=directorio&a=index',
            ],
        ]);
    }

    // ==================================================================
    // Auxiliares privados
    // ==================================================================
    private function render(string $vista, array $datos): void
    {
        extract($datos, EXTR_SKIP);
        $currentPage = 'panel';
        $extraCss = 'panel.css';

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
        require __DIR__ . '/../Views/' . $vista . '.php';
        require __DIR__ . '/../Views/Partials/footer.php';   // el footer cierra </main> y </div>
    }

    // Convierte una consulta en texto; si falla devuelve "—" para no romper la página
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

    // GET /medicos devuelve el total aunque se pida una sola fila
    private function totalMedicos(): ?int
    {
        $res = (new RegistroMedicoRepository())->listar(['tamano_pagina' => 1]);
        return ($res['ok'] ?? false) ? (int) ($res['paginacion']['total'] ?? 0) : null;
    }

    // Suma de saldos del Balance General del 1 del mes a hoy (GET /reportes/balance-general)
    private function saldoDelMes(string $hoy): ?string
    {
        $res = (new ApiClient())->getJson('/reportes/balance-general?' . http_build_query([
            'fecha_inicio' => substr($hoy, 0, 8) . '01', 'fecha_fin' => $hoy,
        ]));
        $json = $res['status'] === 200 ? json_decode((string) $res['body'], true) : null;
        if (!is_array($json['filas'] ?? null)) {
            return null;
        }
        $centavos = array_sum(array_map(fn($f) => (int) round(((float) ($f['saldo'] ?? 0)) * 100), $json['filas']));
        return dinero($centavos / 100);
    }

    // Las 5 solicitudes más recientes con el formato de la tabla del panel
    private function solicitudesRecientes(array $lista): array
    {
        usort($lista, fn($a, $b) => strcmp((string) ($b['fecha_solicitud'] ?? ''), (string) ($a['fecha_solicitud'] ?? '')));
        return array_map(fn(array $s) => [
            'medico' => (string) ($s['nombre_completo'] ?? ''),
            'campo'  => implode(', ', array_keys(is_array($s['cambios'] ?? null) ? $s['cambios'] : [])),
            'fecha'  => ($t = strtotime((string) ($s['fecha_solicitud'] ?? ''))) ? date('d/m/Y', $t) : '',
            'estado' => ($s['estado'] ?? '') === 'PENDIENTE' ? 'Pendiente' : 'Procesada',
        ], array_slice($lista, 0, 5));
    }

    // Vigencia de la certificación más reciente del médico con sesión (GET /medicos/{id})
    private function estadoCertificacion(): string
    {
        $idMedico = \Auth::idMedico();
        if ($idMedico === null) {
            return '—';
        }
        $res = (new RegistroMedicoRepository())->obtener($idMedico);
        if (!($res['ok'] ?? false)) {
            return '—';
        }
        return match ($res['datos']['cert_vigente'] ?? '') {
            'vigente'    => 'Vigente',
            'no_vigente' => 'No vigente',
            default      => 'Sin certificación',
        };
    }

    private function movimientosDelDia(string $fecha): ?int
    {
        $res = (new MovimientosRepository())->listarPorFecha($fecha);
        if (!($res['ok'] ?? false) || !is_array($res['datos'])) {
            return null;
        }
        return count(array_unique(array_column($res['datos'], 'id_movimiento')));
    }
}