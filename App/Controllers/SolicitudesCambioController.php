<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CatalogosRepository;
use App\Repositories\SolicitudesCambioRepository;

/**
 * Solicitudes de cambio.
 *
 *   ?c=solicitudes&a=index      médico -> formulario; admin -> bandeja
 *   ?c=solicitudes&a=nueva      formulario del médico
 *   ?c=solicitudes&a=crear      POST del médico
 *   ?c=solicitudes&a=procesar   POST del administrador
 *
 * El rol real lo valida la API; aquí además se separan las acciones para no
 * mostrarle al médico la bandeja ni al admin el formulario.
 */
final class SolicitudesCambioController
{
    private const LISTA = '/index.php?c=solicitudes&a=index';
    private const NUEVA = '/index.php?c=solicitudes&a=nueva';

    // Secciones de registros: perfil de la API -> id y campos editables.
    private const REGISTROS = [
        'lugar_trabajo' => [
            'perfil' => 'lugares_trabajo',
            'id' => 'id_lugar_trabajo',
            'campos' => ['empresa_institucion', 'cargo', 'telefono_trabajo', 'email_laboral'],
        ],
        'estudio_medico' => [
            'perfil' => 'estudios',
            'id' => 'id_estudio',
            'campos' => ['tipo_estudio', 'titulo', 'institucion', 'lugar', 'fecha'],
        ],
        'certificaciones' => [
            'perfil' => 'certificaciones',
            'id' => 'id_certificacion',
            'campos' => ['fecha_obtencion', 'puntaje', 'fecha_expiracion'],
        ],
    ];

    private const RELACIONES = [
        'especialidades' => ['perfil' => 'especialidades', 'id' => 'id_especialidad'],
        'subespecialidades' => ['perfil' => 'subespecialidades', 'id' => 'id_subespecialidad'],
        'asociaciones' => ['perfil' => 'asociaciones', 'id' => 'id_asociacion'],
    ];

    private SolicitudesCambioRepository $repo;
    private CatalogosRepository $catalogos;

    public function __construct()
    {
        $this->repo = new SolicitudesCambioRepository();
        $this->catalogos = new CatalogosRepository();
    }

    public function index(): void
    {
        if (\Auth::es(\Auth::MEDICO)) {
            $this->nueva();
            return;
        }
        $this->exigirAdmin();

        $estado = $_GET['estado'] ?? null;
        $estado = in_array($estado, ['PENDIENTE', 'PROCESADO'], true) ? $estado : null;

        $resultado = $this->repo->listar($estado);
        $this->render('Admin/Solicitudes/index', [
            'solicitudes' => $resultado['ok'] ? $resultado['datos'] : [],
            'errorApi' => $resultado['ok'] ? '' : $resultado['error'],
            'estado' => $estado,
            'csrf' => $this->tokenCsrf(),
            'flash' => $this->tomarFlash(),
        ]);
    }

    public function nueva(): void
    {
        $this->exigirMedico();
        $idMedico = \Auth::idMedico();
        if ($idMedico === null) {
            $this->flash('error', 'Tu usuario no está asociado a un médico.');
            $this->redirigir('/index.php?c=panel&a=index');
        }

        $resultado = $this->repo->perfilPropio($idMedico);
        $this->render('Client/Solicitudes/form', [
            'perfil' => $resultado['ok'] ? $resultado['datos'] : [],
            'errorApi' => $resultado['ok'] ? '' : $resultado['error'],
            'catalogos' => [
                'especialidades' => $this->catalogos->especialidades(),
                'subespecialidades' => $this->catalogos->subespecialidades(),
                'asociaciones' => $this->catalogos->asociaciones(),
            ],
            'csrf' => $this->tokenCsrf(),
            'flash' => $this->tomarFlash(),
        ]);
    }

    public function crear(): void
    {
        $this->exigirMedico();
        $this->verificarCsrf();
        $idMedico = \Auth::idMedico();
        if ($idMedico === null) {
            $this->flash('error', 'Tu usuario no está asociado a un médico.');
            $this->redirigir(self::NUEVA);
        }

        $perfil = $this->repo->perfilPropio($idMedico);
        if (!$perfil['ok']) {
            $this->flash('error', $perfil['error'] ?: 'No se pudo leer tu perfil.');
            $this->redirigir(self::NUEVA);
        }

        $payload = $this->armarCambios($perfil['datos'], $_POST);
        if ($payload === []) {
            $this->flash('error', 'No indicaste ningún cambio.');
            $this->redirigir(self::NUEVA);
        }

        $resultado = $this->repo->crear($payload);
        if ($resultado['ok']) {
            $id = (int) ($resultado['datos']['id_solicitud_cambio'] ?? 0);
            $this->flash('success', 'Solicitud enviada' . ($id > 0 ? ' (N.° ' . $id . ')' : '') . '.');
        } else {
            $this->flash('error', $resultado['error'] ?: 'No se pudo enviar la solicitud.');
        }
        $this->redirigir(self::NUEVA);
    }

    public function procesar(): void
    {
        $this->exigirAdmin();
        $this->verificarCsrf();

        $id = $this->leerId($_POST['id_solicitud_cambio'] ?? null);
        if ($id === null) {
            $this->flash('error', 'La solicitud indicada no es válida.', true);
            $this->redirigir(self::LISTA);
        }

        $decisiones = $this->armarDecisiones($_POST['decisiones'] ?? []);
        if ($decisiones === []) {
            $this->flash('error', 'Debes decidir al menos una sección.', true);
            $this->redirigir(self::LISTA);
        }

        $comentario = trim((string) ($_POST['comentario'] ?? ''));
        $resultado = $this->repo->procesar($id, $decisiones, $comentario === '' ? null : $comentario);

        if ($resultado['ok']) {
            $this->flash('success', 'La solicitud ' . $id . ' fue procesada.', true);
        } else {
            $this->flash('error', $resultado['error'] ?: 'No se pudo procesar la solicitud.', true);
        }
        $this->redirigir(self::LISTA . $this->queryEstado());
    }

    /**
     * Compara lo enviado con el perfil actual y arma el JSON por secciones.
     * Solo viajan campos con valor nuevo distinto y no vacío.
     */
    private function armarCambios(array $perfil, array $enviado): array
    {
        $payload = [];

        foreach (self::REGISTROS as $seccion => $meta) {
            $registros = is_array($perfil[$meta['perfil']] ?? null) ? array_values($perfil[$meta['perfil']]) : [];
            foreach ($registros as $indice => $registro) {
                $enviados = $enviado[$seccion][$indice] ?? null;
                if (!is_array($enviados) || !isset($registro[$meta['id']])) {
                    continue;
                }
                $cambios = [$meta['id'] => (int) $registro[$meta['id']]];
                $hayCambio = false;
                foreach ($meta['campos'] as $campo) {
                    if (!array_key_exists($campo, $enviados)) {
                        continue;
                    }
                    [$nuevo, $cambio] = $this->valorDelCampo($campo, $enviados[$campo], $registro[$campo] ?? null);
                    if ($cambio) {
                        $cambios[$campo] = $nuevo;
                        $hayCambio = true;
                    }
                }
                if ($hayCambio) {
                    $payload[$seccion][] = $cambios;
                }
            }
        }

        foreach (self::RELACIONES as $relacion => $meta) {
            $actuales = $this->ids(array_map(
                static fn($fila) => is_array($fila) ? ($fila[$meta['id']] ?? null) : null,
                is_array($perfil[$meta['perfil']] ?? null) ? $perfil[$meta['perfil']] : []
            ));
            $seleccionados = $this->ids($enviado[$relacion] ?? []);
            $agregar = array_values(array_diff($seleccionados, $actuales));
            $quitar = array_values(array_diff($actuales, $seleccionados));
            if ($agregar !== [] || $quitar !== []) {
                $payload[$relacion] = ['agregar' => $agregar, 'quitar' => $quitar];
            }
        }

        return $payload;
    }

    /** @return array{0: mixed, 1: bool} [valor normalizado, si cambió] */
    private function valorDelCampo(string $campo, mixed $enviado, mixed $actual): array
    {
        if ($campo === 'puntaje') {
            $texto = trim((string) $enviado);
            if ($texto === '' || !ctype_digit($texto)) {
                return [null, false];
            }
            $nuevo = (int) $texto;
            return [$nuevo, $nuevo !== (int) $actual];
        }
        if (in_array($campo, ['fecha', 'fecha_obtencion', 'fecha_expiracion'], true)) {
            $texto = trim((string) $enviado);
            if ($texto === '') {
                return [null, false];
            }
            return [$texto, $texto !== substr((string) $actual, 0, 10)];
        }
        $texto = trim((string) $enviado);
        if ($texto === '') {
            return [null, false];
        }
        return [$texto, $texto !== (string) $actual];
    }

    /** Limpia las decisiones del formulario al contrato de la API. */
    private function armarDecisiones(mixed $entrada): array
    {
        if (!is_array($entrada)) {
            return [];
        }
        $decisiones = [];

        foreach (['lugar_trabajo', 'estudio_medico', 'certificaciones'] as $seccion) {
            if (!is_array($entrada[$seccion] ?? null)) {
                continue;
            }
            $filas = [];
            foreach (array_values($entrada[$seccion]) as $fila) {
                if (!is_array($fila)) {
                    continue;
                }
                $limpia = [];
                foreach ($fila as $campo => $valor) {
                    $estado = strtoupper((string) $valor);
                    if (in_array($estado, ['APROBADO', 'RECHAZADO'], true)) {
                        $limpia[(string) $campo] = $estado;
                    }
                }
                if ($limpia !== []) {
                    $filas[] = $limpia;
                }
            }
            if ($filas !== []) {
                $decisiones[$seccion] = $filas;
            }
        }

        foreach (['especialidades', 'subespecialidades', 'asociaciones'] as $relacion) {
            $estado = strtoupper((string) ($entrada[$relacion]['estado'] ?? ''));
            if (in_array($estado, ['APROBADO', 'RECHAZADO'], true)) {
                $decisiones[$relacion] = ['estado' => $estado];
            }
        }

        return $decisiones;
    }

    /** @return int[] ids positivos, sin repetir */
    private function ids(mixed $valores): array
    {
        if (!is_array($valores)) {
            return [];
        }
        $ids = [];
        foreach ($valores as $valor) {
            $id = $this->leerId($valor);
            if ($id !== null) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    private function leerId(mixed $valor): ?int
    {
        return is_scalar($valor) && ctype_digit((string) $valor) && (int) $valor > 0
            ? (int) $valor
            : null;
    }

    private function queryEstado(): string
    {
        $estado = $_POST['estado'] ?? '';
        return in_array($estado, ['PENDIENTE', 'PROCESADO'], true) ? '&estado=' . $estado : '';
    }

    private function exigirMedico(): void
    {
        if (!\Auth::es(\Auth::MEDICO)) {
            $this->denegar('Esta sección es solo para médicos.');
        }
    }

    private function exigirAdmin(): void
    {
        if (!\Auth::es(\Auth::ADMIN)) {
            $this->denegar('Esta sección es solo para administradores.');
        }
    }

    private function denegar(string $mensaje): void
    {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit($mensaje);
    }

    private function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        $currentPage = 'solicitudes';
        $extraCss = 'solicitudes.css';

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
        require __DIR__ . '/../Views/' . $vista . '.php';
        require __DIR__ . '/../Views/Partials/footer.php';
    }

    private function tokenCsrf(): string
    {
        if (empty($_SESSION['solicitudes_csrf'])) {
            $_SESSION['solicitudes_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['solicitudes_csrf'];
    }

    private function verificarCsrf(): void
    {
        $enviado = $_POST['csrf'] ?? '';
        if (!is_string($enviado) || !hash_equals($this->tokenCsrf(), $enviado)) {
            $this->flash('error', 'La sesión expiró. Vuelve a intentarlo.', \Auth::es(\Auth::ADMIN));
            $this->redirigir(\Auth::es(\Auth::ADMIN) ? self::LISTA : self::NUEVA);
        }
    }

    private function flash(string $tipo, string $texto, bool $admin = false): void
    {
        $_SESSION['solicitudes_flash'] = ['tipo' => $tipo, 'texto' => $texto, 'admin' => $admin];
    }

    private function tomarFlash(): ?array
    {
        $flash = $_SESSION['solicitudes_flash'] ?? null;
        unset($_SESSION['solicitudes_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function redirigir(string $ruta): void
    {
        header('Location: ' . $ruta);
        exit;
    }
}
