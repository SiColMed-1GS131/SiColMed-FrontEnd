<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\RegistroMedicoRepository;

final class DirectorioController
{
    private RegistroMedicoRepository $medicos;

    public function __construct()
    {
        $this->medicos = new RegistroMedicoRepository();
    }

    public function index(): void
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
            ]);
            return;
        }

        if (!\is_array($resultado['datos']) || !\array_is_list($resultado['datos'])) {
            $this->render('index', [
                'medicos' => [],
                'filtros' => $filtros,
                'paginaActual' => 1,
                'totalPaginas' => 0,
                'errorGeneral' => 'La API devolvió una lista de médicos con formato inesperado.',
            ]);
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
        ]);
    }

    public function ver(): void
    {
        $id = \filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($id === false) {
            header('Location: ?c=directorio&a=index');
            exit;
        }

        $resultado = $this->medicos->obtener($id);
        $medico = $resultado['ok'] && \is_array($resultado['datos']) ? $resultado['datos'] : [];
        $errorGeneral = $resultado['ok'] ? '' : $resultado['error'];

        $this->render('ver', [
            'medico' => $medico,
            'errorGeneral' => $errorGeneral,
        ]);
    }

    private function render(string $vista, array $datos): void
    {
        extract($datos, EXTR_SKIP);
        $extraCss = 'directorio.css';
        $currentPage = 'directorio';
        // Ruta a index.php que funciona igual en WAMP (/SiColMed-FrontEnd/Public) y en Docker (raíz)
        $entrada = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/index.php';

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
        require __DIR__ . '/../Views/Admin/Directorio/' . $vista . '.php';
        require __DIR__ . '/../Views/Partials/footer.php';
    }
}