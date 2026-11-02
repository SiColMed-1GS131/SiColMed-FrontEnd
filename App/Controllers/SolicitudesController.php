<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\SolicitudesRepository;

/**
 * SolicitudesController · Gestión de Solicitudes de Cambio (Módulo 2).
 * - admin_cmp: Bandeja de entrada para revisar y procesar solicitudes.
 * - medico: Formulario para solicitar cambios en datos sensibles.
 */
final class SolicitudesController extends BaseController
{
    private SolicitudesRepository $solicitudes;

    public function __construct()
    {
        $this->solicitudes = new SolicitudesRepository();
    }

    /**
     * Bandeja de solicitudes.
     */
    public function index(): void
    {
        if (\Auth::es(\Auth::ADMIN)) {
            $this->adminBandeja();
            return;
        }

        if (\Auth::es(\Auth::MEDICO)) {
            $this->medicoBandeja();
            return;
        }

        \Auth::exigirSesion();
    }

    /**
     * Bandeja del administrador (GET /solicitudes-cambio).
     */
    private function adminBandeja(): void
    {
        \Auth::exigir('registro');

        $filtroEstado = trim((string) ($_GET['estado'] ?? ''));
        $estado = $filtroEstado !== '' ? strtoupper($filtroEstado) : null;

        $resultado = $this->solicitudes->listar($estado);
        $solicitudes = $resultado['ok'] && is_array($resultado['datos']) ? $resultado['datos'] : [];

        $this->render('Admin/Solicitudes/index', [
            'solicitudes'  => $solicitudes,
            'filtroEstado' => $filtroEstado,
            'flash'        => $this->tomarFlash('solicitudes'),
            'error'        => $resultado['ok'] ? '' : $resultado['error'],
        ], 'solicitudes', 'registro.css');
    }

    /**
     * Bandeja de solicitudes del médico autenticado.
     */
    private function medicoBandeja(): void
    {
        \Auth::exigir('perfil');

        $resultado = $this->solicitudes->listar();
        $todas = $resultado['ok'] && is_array($resultado['datos']) ? $resultado['datos'] : [];

        $idMedico = $this->obtenerIdMedicoSesion();
        $misSolicitudes = array_values(array_filter($todas, fn(array $s) => ($s['id_medico'] ?? 0) === $idMedico));

        $this->render('Client/Solicitudes/index', [
            'solicitudes' => $misSolicitudes,
            'flash'       => $this->tomarFlash('solicitudes'),
        ], 'perfil', 'perfil.css');
    }

    /**
     * Formulario para que el médico solicite un cambio.
     */
    public function crear(): void
    {
        \Auth::exigir('perfil');

        $this->render('Client/Solicitudes/crear', [
            'csrf'  => Csrf::token('solicitudes'),
            'flash' => $this->tomarFlash('solicitudes'),
        ], 'perfil', 'perfil.css');
    }

    /**
     * Guarda la solicitud de cambio enviada por el médico.
     */
    public function guardar(): void
    {
        \Auth::exigir('perfil');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('/index.php?c=solicitudes&a=crear');
        }

        if (!Csrf::valido('solicitudes', $_POST['csrf'] ?? null)) {
            $this->guardarFlash('solicitudes', 'error', 'La sesión del formulario expiró. Inténtalo nuevamente.');
            $this->redirigir('/index.php?c=solicitudes&a=crear');
        }

        $idMedico = $this->obtenerIdMedicoSesion();
        $nombreMedico = (string) ($_SESSION['usuario'] ?? 'Médico');

        $campo = trim((string) ($_POST['campo'] ?? ''));
        $valorNuevo = trim((string) ($_POST['valor_nuevo'] ?? ''));
        $motivo = trim((string) ($_POST['motivo'] ?? ''));

        if ($campo === '' || $valorNuevo === '') {
            $this->guardarFlash('solicitudes', 'error', 'Por favor selecciona el campo e indica el nuevo valor.');
            $this->redirigir('/index.php?c=solicitudes&a=crear');
        }

        $cambios = [
            $campo => [
                'valor_nuevo' => $valorNuevo,
                'motivo'      => $motivo,
                'estado'      => 'PENDIENTE',
            ],
        ];

        $res = $this->solicitudes->crear($idMedico, $nombreMedico, $cambios);

        if ($res['ok']) {
            $this->guardarFlash('perfil', 'success', 'Solicitud de cambio enviada exitosamente para revisión del CMP.');
            $this->redirigir('/index.php?c=perfil&a=index');
        } else {
            $this->guardarFlash('solicitudes', 'error', $res['error'] ?: 'Error al enviar la solicitud.');
            $this->redirigir('/index.php?c=solicitudes&a=crear');
        }
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
}
