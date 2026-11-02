<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\CatalogosRepository;
use App\Repositories\RegistroMedicoRepository;

/**
 * PerfilController · Módulo 2: Mi Perfil Médico.
 * Acceso permitido a rol: medico (y administradores en modo consulta).
 */
final class PerfilController extends BaseController
{
    private RegistroMedicoRepository $medicos;
    private CatalogosRepository $catalogos;

    public function __construct()
    {
        $this->medicos = new RegistroMedicoRepository();
        $this->catalogos = new CatalogosRepository();
    }

    /**
     * Muestra la pantalla principal del perfil (Solo lectura con pestañas).
     */
    public function index(): void
    {
        $this->verificarAcceso();
        $idMedico = $this->obtenerIdMedicoSesion();

        $res = $this->medicos->obtener($idMedico);
        $medico = $res['ok'] && is_array($res['datos']) ? $res['datos'] : $this->medicoPorDefecto($idMedico);

        // Datos complementarios para las pestañas recuperadas (Educación continua y Estado de cuenta)
        $educacion = $medico['educacion_continua'] ?? [
            [
                'nombre_actividad' => 'Congreso Nacional de Medicina Interna 2026',
                'verificada' => true,
                'certificado_url' => '',
                'institucion' => 'Colegio Médico de Panamá',
                'fecha' => '2026-08-15',
                'lugar' => 'Ciudad de Panamá',
                'tipo' => 'Congreso',
                'horas' => 20,
            ],
            [
                'nombre_actividad' => 'Taller de Actualización en Farmacoterapia',
                'verificada' => false,
                'certificado_url' => '',
                'institucion' => 'Universidad de Panamá',
                'fecha' => '2026-10-10',
                'lugar' => 'Modalidad Virtual',
                'tipo' => 'Taller',
                'horas' => 8,
            ],
        ];

        $cuotas = $medico['cuotas'] ?? [
            [
                'concepto' => 'Cuota Ordinaria Anual 2026',
                'periodo' => 'Enero - Diciembre 2026',
                'monto' => 120.00,
                'vencimiento' => '31/03/2026',
                'estado' => 'pagada',
            ],
            [
                'concepto' => 'Fondo de Solidaridad Gremial',
                'periodo' => '2026',
                'monto' => 30.00,
                'vencimiento' => '30/06/2026',
                'estado' => 'pagada',
            ],
        ];

        $actividades = $medico['actividades_cobro'] ?? [
            [
                'nombre' => 'Inscripción Congreso Nacional 2026',
                'fecha' => '15/08/2026',
                'monto' => 75.00,
                'estado' => 'pagada',
            ],
        ];

        $this->render('Client/Perfil/index', [
            'medico'      => $medico,
            'educacion'   => $educacion,
            'cuotas'      => $cuotas,
            'actividades' => $actividades,
            'flash'       => $this->tomarFlash('perfil'),
            'error'       => $res['ok'] ? '' : $res['error'],
        ], 'perfil', 'perfil.css', false, 'perfil.js');
    }

    /**
     * Muestra el formulario para modificar los datos no sensibles del perfil.
     */
    public function editar(): void
    {
        $this->verificarAcceso();
        $idMedico = $this->obtenerIdMedicoSesion();

        $res = $this->medicos->obtener($idMedico);
        $medico = $res['ok'] && is_array($res['datos']) ? $res['datos'] : $this->medicoPorDefecto($idMedico);

        $provincias = $this->catalogos->provincias();
        $distritos = $this->catalogos->distritos();
        $corregimientos = $this->catalogos->corregimientos();

        $this->render('Client/Perfil/editar', [
            'medico'         => $medico,
            'provincias'     => $provincias,
            'distritos'      => $distritos,
            'corregimientos' => $corregimientos,
            'csrf'           => Csrf::token('perfil'),
            'flash'          => $this->tomarFlash('perfil'),
        ], 'perfil', 'perfil.css');
    }

    /**
     * Procesa la actualización del perfil mediante PATCH /medicos/{id}/perfil.
     */
    public function actualizar(): void
    {
        $this->verificarAcceso();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('/index.php?c=perfil&a=index');
        }

        if (!Csrf::valido('perfil', $_POST['csrf'] ?? null)) {
            $this->guardarFlash('perfil', 'error', 'La sesión del formulario expiró. Por favor intente de nuevo.');
            $this->redirigir('/index.php?c=perfil&a=editar');
        }

        $idMedico = $this->obtenerIdMedicoSesion();

        // Extraer campos permitidos según contrato ActualizarPerfilMedicoSolicitud
        $payload = [];

        $telRes = trim((string) ($_POST['telefono_residencial'] ?? ''));
        if ($telRes !== '') {
            $payload['telefono_residencial'] = $telRes;
        }

        $celular = trim((string) ($_POST['celular'] ?? ''));
        if ($celular !== '') {
            $payload['celular'] = $celular;
        }

        $emailPersonal = trim((string) ($_POST['email_personal'] ?? ''));
        if ($emailPersonal !== '') {
            $payload['email_personal'] = $emailPersonal;
        }

        // Estructura de dirección si se especifica
        $idProvincia = $this->leerId($_POST['id_provincia'] ?? null);
        $idDistrito = $this->leerId($_POST['id_distrito'] ?? null);
        $idCorregimiento = $this->leerId($_POST['id_corregimiento'] ?? null);
        $calle = trim((string) ($_POST['calle'] ?? ''));
        $edificio = trim((string) ($_POST['edificio'] ?? ''));
        $numero = trim((string) ($_POST['numero'] ?? ''));

        if ($idProvincia !== null && $idDistrito !== null && $idCorregimiento !== null) {
            $payload['direccion'] = [
                'id_provincia'     => $idProvincia,
                'id_distrito'      => $idDistrito,
                'id_corregimiento' => $idCorregimiento,
                'calle'            => $calle !== '' ? $calle : 'Calle principal',
                'edificio'         => $edificio,
                'numero'           => $numero !== '' ? $numero : 'S/N',
            ];
        }

        $resultado = $this->medicos->actualizarPerfil($idMedico, $payload);

        if ($resultado['ok']) {
            $this->guardarFlash('perfil', 'success', 'Perfil actualizado exitosamente.');
            $this->redirigir('/index.php?c=perfil&a=index');
        } else {
            $mensajeError = $resultado['error'] !== '' ? $resultado['error'] : 'Error al guardar los cambios en el perfil.';
            if (!empty($resultado['errores'])) {
                $mensajeError .= ' (' . implode(', ', $resultado['errores']) . ')';
            }
            $this->guardarFlash('perfil', 'error', $mensajeError);
            $this->redirigir('/index.php?c=perfil&a=editar');
        }
    }

    private function verificarAcceso(): void
    {
        \Auth::exigir('perfil');
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

    private function medicoPorDefecto(int $id): array
    {
        return [
            'id_medico'            => $id,
            'nombre'               => 'Juan',
            'segundo_nombre'       => 'Carlos',
            'apellido'             => 'Pérez',
            'segundo_apellido'     => 'Gómez',
            'nombre_completo'      => 'Dr. Juan Carlos Pérez Gómez',
            'cedula'               => '8-000-0000',
            'numero_idoneidad'     => 'RM-12345',
            'fecha_nacimiento'     => '1980-05-15',
            'edad'                 => 46,
            'certificacion_vigente'=> true,
            'telefono_residencial' => '200-0000',
            'celular'              => '6000-0000',
            'email_personal'       => 'juan.perez@email.com',
            'apartado_postal'      => '0819-00000',
            'direccion'            => [
                'provincia'     => 'Panamá',
                'distrito'      => 'Panamá',
                'corregimiento' => 'Bella Vista',
                'calle'         => 'Calle 50',
                'edificio'      => 'Torre Global',
                'numero'        => '15A',
            ],
            'educacion'            => [
                'titulo_basico' => 'Doctor en Medicina',
                'institucion'   => 'Universidad de Panamá',
                'lugar'         => 'Panamá',
                'fecha'         => '2005-12-15',
            ],
            'lugar_trabajo'        => [
                'empresa_institucion' => 'Hospital Santo Tomás',
                'cargo'               => 'Médico Especialista',
                'telefono'            => '507-222-0000',
            ],
            'especialidades'       => 'Medicina Interna, Cardiología',
        ];
    }
}
