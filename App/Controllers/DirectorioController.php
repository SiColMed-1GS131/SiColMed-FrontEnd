<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CatalogosRepository;
use App\Repositories\RegistroMedicoRepository;

require_once __DIR__ . '/../Helpers/ui.php';   // enviarImagen()

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
    // Filtros de la URL con los mismos nombres que GET /medicos (FiltroMedicos del backend)
    private const FILTROS_ID = ['id_especialidad', 'id_asociacion', 'id_provincia'];
    private const FILTROS_TEXTO = ['apellido', 'lugar_trabajo'];
    private const POR_PAGINA = 50;

    private RegistroMedicoRepository $medicos;
    private CatalogosRepository $catalogos;

    public function __construct()
    {
        $this->catalogos = new CatalogosRepository();
        $this->medicos = new RegistroMedicoRepository(null, $this->catalogos);
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

    // Foto de perfil de un médico (pública, como la ficha). La guarda la API, no el webroot.
    public function foto(): never
    {
        $id = \filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        enviarImagen($id === false ? null : $this->medicos->verFoto($id));
    }

    // ==================================================================
    // Lógica compartida
    // ==================================================================
    private function listado(bool $publico): void
    {
        $filtros = [];
        foreach (self::FILTROS_TEXTO as $campo) {
            $valor = $_GET[$campo] ?? '';
            $filtros[$campo] = \is_string($valor) ? trim($valor) : '';
        }
        foreach (self::FILTROS_ID as $campo) {
            $valor = \filter_var($_GET[$campo] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $filtros[$campo] = $valor === false ? '' : (string) $valor;
        }
        $vigente = $_GET['certificacion_vigente'] ?? '';
        $filtros['certificacion_vigente'] = \in_array($vigente, ['true', 'false'], true) ? $vigente : '';

        $paginaActual = \filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $paginaActual = $paginaActual === false ? 1 : $paginaActual;

        // La API pagina y ordena; aquí solo se pide la página actual
        $resultado = $this->medicos->listar($filtros + ['pagina' => $paginaActual, 'tamano_pagina' => self::POR_PAGINA]);
        $this->render('index', [
            'medicos' => $resultado['ok'] ? $resultado['datos'] : [],
            'filtros' => $filtros,
            'catalogos' => [
                'especialidades' => $this->catalogos->especialidades(),
                'asociaciones'   => $this->catalogos->asociaciones(),
                'provincias'     => $this->catalogos->provincias(),
            ],
            'paginaActual' => $resultado['paginacion']['pagina'] ?? 1,
            'totalPaginas' => $resultado['paginacion']['total_paginas'] ?? 0,
            'errorGeneral' => $resultado['ok'] ? '' : $resultado['error'],
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
