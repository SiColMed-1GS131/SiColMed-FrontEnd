<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CatalogosRepository;
use App\Repositories\RegistroMedicoRepository;

require_once __DIR__ . '/../Helpers/ui.php';   // enviarPdf()

/** Registro médico, lista, formulario (nuevo o editar) y guardado. */
final class RegistroController
{
    private const POR_PAGINA = 100;   // máximo que acepta GET /medicos

    private RegistroMedicoRepository $medicos;
    private CatalogosRepository $catalogos;

    public function __construct()
    {
        $this->catalogos = new CatalogosRepository();
        $this->medicos = new RegistroMedicoRepository(null, $this->catalogos);
    }

    // Lista de médicos
    public function index(): void
    {
        $this->verificarAcceso();

        $pagina = \filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $resultado = $this->medicos->listar(['pagina' => $pagina, 'tamano_pagina' => self::POR_PAGINA]);
        $mensaje = $resultado['ok'] ? $this->tomarMensaje() : $resultado['error'];

        $this->render('index', [
            'medicos' => $resultado['ok'] ? $resultado['datos'] : [],
            'total' => $resultado['paginacion']['total'] ?? 0,
            'paginaActual' => $resultado['paginacion']['pagina'] ?? 1,
            'totalPaginas' => $resultado['paginacion']['total_paginas'] ?? 0,
            'mensaje' => $mensaje,
        ]);
    }

    // Formulario vacío
    public function nuevo(): void
    {
        $this->verificarAcceso();

        $this->render('form', ['medico' => [], 'errores' => [], 'errorGeneral' => '']);
    }

    // Distritos o corregimientos en JSON para los selects en cascada del formulario.
    // ?c=registro&a=catalogo&recurso=distritos&id=<id_provincia> | recurso=corregimientos&id=<id_distrito>
    public function catalogo(): never
    {
        $this->verificarAcceso();

        $id = \filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $lista = match (true) {
            $id === false => null,
            ($_GET['recurso'] ?? '') === 'distritos' => $this->catalogos->distritos($id),
            ($_GET['recurso'] ?? '') === 'corregimientos' => $this->catalogos->corregimientos($id),
            default => null,
        };
        if ($lista === null) {
            $this->responderJson(400, ['ok' => false, 'error' => 'Catálogo no válido.']);
        }
        $this->responderJson(200, \array_map(static fn (array $i) => ['id' => $i['id'], 'nombre' => $i['nombre']], $lista));
    }

    // Formulario con los datos del médico
    public function editar(): void
    {
        $this->verificarAcceso();

        $id = $this->leerId($_GET['id'] ?? null);
        $resultado = $id === null ? null : $this->medicos->obtener($id);

        // Con la sesión vencida la API solo devuelve la ficha pública: no sirve para editar
        if ($resultado !== null && $resultado['ok'] && !empty($resultado['datos']['publico'])) {
            $resultado = ['ok' => false, 'error' => 'Tu sesión expiró. Inicia sesión de nuevo para editar este médico.'];
        }
        if ($resultado === null || !$resultado['ok']) {
            $this->guardarMensaje(($resultado['error'] ?? '') ?: 'Médico no encontrado.');
            $this->redirigir($this->entrada() . '?c=registro&a=index');
        }

        $this->render('form', ['medico' => $resultado['datos'], 'errores' => [], 'errorGeneral' => '']);
    }

    // Guardar un médico nuevo o los cambios de uno existente 
    public function guardar(): void
    {
        $this->verificarAcceso();

        $datos = $_POST;
        $id = $this->leerId($datos['id'] ?? null);
        unset($datos['id']);

        // Primero los datos (un médico nuevo necesita su id) y después el certificado PDF.
        // La foto todavía no se envía.
        $resultado = $id === null
            ? $this->medicos->crear($datos)
            : $this->medicos->actualizar($id, $datos);

        if ($resultado['ok']) {
            $mensaje = 'Médico guardado correctamente.';
            $idMedico = $id ?? (int) ($resultado['datos']['id_medico'] ?? 0);
            $pdf = $_FILES['certificado_idoneidad'] ?? null;
            if (\is_array($pdf) && ($pdf['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && $idMedico > 0) {
                $subida = $this->medicos->subirCertificado($idMedico, $pdf);
                $mensaje = $subida['ok']
                    ? 'Médico y certificado guardados correctamente.'
                    : 'Médico guardado, pero el certificado no se subió: ' . $subida['error'] . ' Edítalo para intentarlo de nuevo.';
            }
            $this->guardarMensaje($mensaje);
            $this->redirigir($this->entrada() . '?c=registro&a=index');
        }

        // Si hay errores, se vuelve a mostrar el formulario con los datos y errores
        $this->render('form', [
            'medico' => $id === null ? $datos : $datos + ['id' => $id],
            'errores' => $resultado['errores'],
            'errorGeneral' => $resultado['error'],
        ]);
    }

    // Descarga el certificado de idoneidad de un médico (la API exige sesión de administrador)
    public function certificado(): never
    {
        $this->verificarAcceso();

        $id = $this->leerId($_GET['id'] ?? null);
        $resultado = $id === null ? null : $this->medicos->descargarCertificado($id);
        enviarPdf($resultado, 'certificado_idoneidad_' . $id . '.pdf');
    }

    // Registra una fila proveniente de la importación CSV.
    public function importarCsv(): never
    {
        $this->verificarAcceso();

        $datos = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($datos)) {
            $this->responderJson(400, ['ok' => false, 'error' => 'La fila CSV no tiene un formato válido.']);
        }

        $resultado = $this->medicos->crear($datos);
        $this->responderJson($resultado['ok'] ? 201 : 422, [
            'ok' => $resultado['ok'],
            'error' => $resultado['error'],
            'errores' => $resultado['errores'],
        ]);
    }

    // Pendiente
    private function verificarAcceso(): void
    {
    }

    // Listas de los selects del formulario. Distritos y corregimientos solo de lo ya elegido;
    // el resto lo carga registro.js con la acción catalogo.
    private function catalogosFormulario(array $medico): array
    {
        $catalogos = [
            'provincias'        => $this->catalogos->provincias(),
            'especialidades'    => $this->catalogos->especialidades(),
            'subespecialidades' => $this->catalogos->subespecialidades(),
            'asociaciones'      => $this->catalogos->asociaciones(),
        ];
        foreach (['residencia', 'trabajo'] as $prefijo) {
            $provincia = (int) ($medico[$prefijo . '_provincia'] ?? 0);
            $distrito = (int) ($medico[$prefijo . '_distrito'] ?? 0);
            $catalogos['distritos'][$prefijo] = $provincia > 0 ? $this->catalogos->distritos($provincia) : [];
            $catalogos['corregimientos'][$prefijo] = $distrito > 0 ? $this->catalogos->corregimientos($distrito) : [];
        }
        return $catalogos;
    }

    // Arma la página (posibles cambios dependiendo de /Views/Partials/...)
    private function render(string $vista, array $datos = []): void
    {
        if ($vista === 'form') {
            $datos['catalogos'] = $this->catalogosFormulario($datos['medico'] ?? []);
        }
        extract($datos, EXTR_SKIP);
        $currentPage = 'registro';
        $extraCss = 'registro.css';   // el header carga base.css y este CSS
        $entrada = $this->entrada();  // URL base de index.php para los enlaces y el formulario

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
        require __DIR__ . '/../Views/Admin/Registro/' . $vista . '.php';
        require __DIR__ . '/../Views/Partials/footer.php';
    }

    // Ruta a index.php que funciona igual en WAMP (/SiColMed-FrontEnd/Public) y en Docker (raíz)
    private function entrada(): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/index.php';
    }

    // El id debe ser un número entero
    private function leerId(mixed $valor): ?int
    {
        return is_string($valor) && ctype_digit($valor) ? (int) $valor : null;
    }

    // Mensaje que se muestra una sola vez en la lista
    private function guardarMensaje(string $texto): void
    {
        $_SESSION['registro_mensaje'] = $texto;
    }

    private function tomarMensaje(): string
    {
        $texto = (string) ($_SESSION['registro_mensaje'] ?? '');
        unset($_SESSION['registro_mensaje']);
        return $texto;
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . $ruta);
        exit;
    }

    private function responderJson(int $codigo, array $datos): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}