<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CuentasRepository;
use App\Repositories\MovimientosRepository;
use App\Repositories\RegistroMedicoRepository;

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

        $estadisticas = [
            ['etiqueta' => 'Médicos registrados',        'valor' => $this->contar(fn() => $this->total((new RegistroMedicoRepository())->listar()))],
            ['etiqueta' => 'Solicitudes pendientes',     'valor' => '—'],   // pendiente de la API
            ['etiqueta' => 'Certificaciones por vencer', 'valor' => '—'],   // pendiente de la API
            ['etiqueta' => 'Cuentas activas',            'valor' => $this->contar(fn() => $this->total((new CuentasRepository())->listar(true)))],
            ['etiqueta' => 'Movimientos de hoy',         'valor' => $this->contar(fn() => $this->movimientosDelDia($hoy))],
            ['etiqueta' => 'Saldo del mes',              'valor' => '—'],   // pendiente de la API
        ];

        // En modo de prueba se muestran filas de ejemplo; con la API real vendrán de /solicitudes-cambio
        $esEjemplo = \Auth::modoPrueba();
        $solicitudes = $esEjemplo ? [
            ['medico' => 'Ana Pérez',    'campo' => 'Cargo laboral',      'fecha' => '28/10/2026', 'estado' => 'Pendiente'],
            ['medico' => 'Luis Gómez',   'campo' => 'Teléfono de trabajo', 'fecha' => '27/10/2026', 'estado' => 'Pendiente'],
            ['medico' => 'María Rivera', 'campo' => 'Especialidad',        'fecha' => '25/10/2026', 'estado' => 'Procesada'],
        ] : [];

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
                ['etiqueta' => 'Estado de mi certificación', 'valor' => '—'],   // pendiente de la API
                ['etiqueta' => 'Mis solicitudes de cambio',  'valor' => '—'],   // pendiente de la API
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

    private function movimientosDelDia(string $fecha): ?int
    {
        $res = (new MovimientosRepository())->listarPorFecha($fecha);
        if (!($res['ok'] ?? false) || !is_array($res['datos'])) {
            return null;
        }
        return count(array_unique(array_column($res['datos'], 'id_movimiento')));
    }
}