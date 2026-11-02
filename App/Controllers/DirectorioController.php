<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\RegistroMedicoRepository;

/**
 * DirectorioController · directorio médico.
 *
 * Hay DOS formas de abrirlo (misma información, distinto marco):
 *
 *   PÚBLICA  (página aparte, sin sidebar, para visitantes)
 *     ?c=directorio&a=publico                 listado
 *     ?c=directorio&a=publicoVer&id=1         perfil público de un médico
 *
 *   INTERNA  (dentro del sistema, con sidebar y menú)
 *     ?c=directorio&a=index                   listado
 *     ?c=directorio&a=ver&id=1                perfil público de un médico
 */
final class DirectorioController
{
    private RegistroMedicoRepository $medicos;

    public function __construct()
    {
        $this->medicos = new RegistroMedicoRepository();
    }

    // ---------- Dentro del sistema (con sidebar) ----------
    public function index(): void
    {
        $this->listado(false);
    }

    public function ver(): void
    {
        $this->perfil(false);
    }

    // ---------- Página pública (sin sidebar) ----------
    public function publico(): void
    {
        $this->listado(true);
    }

    public function publicoVer(): void
    {
        $this->perfil(true);
    }

    // ==================================================================
    // Lógica compartida
    // ==================================================================
    private function listado(bool $publico): void
    {
        $filtros = [];
        foreach (['q', 'especialidad', 'asociacion', 'certificacion', 'provincia', 'lugar_trabajo'] as $campo) {
            $valor = $_GET[$campo] ?? '';
            $filtros[$campo] = \is_string($valor) ? trim($valor) : '';
        }

        $resultado = $this->medicos->listar($filtros);
        if (!$resultado['ok']) {
            $this->render('index', [
                'medicos' => [],
                'filtros' => $filtros,
                'paginaActual' => 1,
                'totalPaginas' => 0,
                'errorGeneral' => $resultado['error'],
            ], $publico);
            return;
        }

        if (!\is_array($resultado['datos']) || !\array_is_list($resultado['datos'])) {
            $this->render('index', [
                'medicos' => [],
                'filtros' => $filtros,
                'paginaActual' => 1,
                'totalPaginas' => 0,
                'errorGeneral' => 'La API devolvió una lista de médicos con formato inesperado.',
            ], $publico);
            return;
        }

        $medicos = $resultado['datos'];
        usort($medicos, static function (array $a, array $b): int {
            return strcmp((string) ($a['apellido'] ?? ''), (string) ($b['apellido'] ?? ''));
        });

        $porPagina = 50;
        $totalMedicos = \count($medicos);
        $totalPaginas = (int) \ceil($totalMedicos / $porPagina);
        $paginaActual = \filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $paginaActual = $paginaActual === false ? 1 : $paginaActual;
        if ($totalPaginas > 0) {
            $paginaActual = \min($paginaActual, $totalPaginas);
        }

        $offset = ($paginaActual - 1) * $porPagina;
        $this->render('index', [
            'medicos' => \array_slice($medicos, $offset, $porPagina),
            'filtros' => $filtros,
            'paginaActual' => $paginaActual,
            'totalPaginas' => $totalPaginas,
            'errorGeneral' => '',
        ], $publico);
    }

    private function perfil(bool $publico): void
    {
        $id = \filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($id === false) {
            header('Location: ' . $this->entrada() . '?c=directorio&a=' . ($publico ? 'publico' : 'index'));
            exit;
        }

        $resultado = $this->medicos->obtener($id);
        $medico = $resultado['ok'] && \is_array($resultado['datos']) ? $resultado['datos'] : [];
        $errorGeneral = $resultado['ok'] ? '' : $resultado['error'];

        $this->render('ver', [
            'medico' => $medico,
            'errorGeneral' => $errorGeneral,
        ], $publico);
    }

    /** Ruta a index.php; funciona igual en WAMP (/SiColMed-FrontEnd/Public) y en Docker (raíz). */
    private function entrada(): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/index.php';
    }

    private function render(string $vista, array $datos, bool $publico): void
    {
        extract($datos, EXTR_SKIP);
        $extraCss = 'directorio.css';
        $currentPage = 'directorio';
        $entrada = $this->entrada();
        $base = \substr($entrada, 0, -\strlen('/index.php'));   // '' en Docker

        // Acciones a las que apuntan los enlaces y filtros de las vistas
        $accionIndex = $publico ? 'publico' : 'index';
        $accionVer = $publico ? 'publicoVer' : 'ver';
        $rutaVista = __DIR__ . '/../Views/Admin/Directorio/' . $vista . '.php';

        if ($publico) {
            // Página aparte: barra superior y pie de la portada, sin sidebar
            $tituloPagina = 'Directorio médico · SiColMed';
            $urls = [
                'inicio'   => $entrada,
                'login'    => $entrada . '?c=login&a=login',
                'registro' => $entrada . '?c=register&a=index',
            ];
            require __DIR__ . '/../Views/Partials/public_header.php';
            echo '<main id="contenido" class="directorio-publico">';
            require $rutaVista;
            echo '</main>';
            require __DIR__ . '/../Views/Partials/public_footer.php';
            return;
        }

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
        require $rutaVista;
        require __DIR__ . '/../Views/Partials/footer.php';
    }
}
