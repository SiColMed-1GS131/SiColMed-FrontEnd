<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\CatalogosRepository;
use App\Repositories\RegistroMedicoRepository;

/** Registro médico, lista, formulario (nuevo o editar) y guardado. */
final class RegistroController
{
    private RegistroMedicoRepository $medicos;
    private CatalogosRepository $catalogos;

    public function __construct()
    {
        $this->medicos = new RegistroMedicoRepository();
        $this->catalogos = new CatalogosRepository();
    }

    // Lista de médicos
    public function index(): void
    {
        $this->verificarAcceso();

        $resultado = $this->medicos->listar();
        $mensaje = $resultado['ok'] ? $this->tomarMensaje() : $resultado['error'];

        $this->render('index', [
            'medicos' => $resultado['ok'] ? $resultado['datos'] : [],
            'mensaje' => $mensaje,
        ]);
    }

    // Formulario vacío
    public function nuevo(): void
    {
        $this->verificarAcceso();

        $this->render('form', ['medico' => [], 'errores' => [], 'errorGeneral' => '']);
    }

    // Formulario con los datos del médico
    public function editar(): void
    {
        $this->verificarAcceso();

        $id = $this->leerId($_GET['id'] ?? null);
        $resultado = $id === null ? null : $this->medicos->obtenerParaFormulario($id);

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

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            exit('Método no permitido.');
        }

        $datos = $_POST;
        $id = $this->leerId($datos['id'] ?? null);
        unset($datos['id'], $datos['csrf']);

        // CSRF: si el código no coincide, no se envía nada a la API y se vuelve a mostrar el formulario.
        if (!Csrf::valido('registro', $_POST['csrf'] ?? '')) {
            http_response_code(403);
            $this->render('form', [
                'medico' => $id === null ? $datos : $datos + ['id' => $id],
                'errores' => [],
                'errorGeneral' => 'La sesión expiró o el formulario no es válido. Vuelve a intentarlo.',
            ]);
            return;
        }

        // Se envía JSON a la API: la foto y el PDF de idoneidad todavía no se suben (la API no tiene endpoint de archivos).
        $resultado = $id === null
            ? $this->medicos->crear($datos)
            : $this->medicos->actualizar($id, $datos);

        if ($resultado['ok']) {
            $this->guardarMensaje('Médico guardado correctamente.');
            $this->redirigir($this->entrada() . '?c=registro&a=index');
        }

        // Si hay errores, se vuelve a mostrar el formulario con los datos y errores
        $this->render('form', [
            'medico' => $id === null ? $datos : $datos + ['id' => $id],
            'errores' => $resultado['errores'],
            'errorGeneral' => $resultado['error'],
        ]);
    }

    // Distritos de una provincia o corregimientos de un distrito, para los selects en cascada.
    // GET ?c=registro&a=catalogo&tipo=distritos|corregimientos&id=N
    public function catalogo(): never
    {
        $this->verificarAcceso(true);

        $id = $this->leerId($_GET['id'] ?? null);
        $tipo = (string) ($_GET['tipo'] ?? '');
        if ($id === null || !\in_array($tipo, ['distritos', 'corregimientos'], true)) {
            $this->responderJson(400, ['ok' => false, 'error' => 'Parámetros no válidos.']);
        }

        $lista = $tipo === 'distritos'
            ? $this->catalogos->distritos($id)
            : $this->catalogos->corregimientos($id);
        $this->responderJson(200, ['ok' => true, 'datos' => $lista]);
    }

    // Registra una fila proveniente de la importación CSV.
    public function importarCsv(): never
    {
        $this->verificarAcceso(true);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->responderJson(405, ['ok' => false, 'error' => 'Método no permitido.']);
        }
        // El JS manda el código en la cabecera X-CSRF-Token (el cuerpo es el JSON de la fila).
        if (!Csrf::valido('registro', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            $this->responderJson(403, ['ok' => false, 'error' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.']);
        }

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

    // Solo el administrador del CMP (ver PERMISOS en App/Middleware/Auth.php).
    // $json = true en las acciones que responden JSON: sin permiso devuelven 401/403 en JSON, no HTML.
    private function verificarAcceso(bool $json = false): void
    {
        \Auth::exigir('registro', $json);
    }

    // Arma la página (posibles cambios dependiendo de /Views/Partials/...)
    private function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        $csrf = Csrf::token('registro');   // lo usan el formulario y la importación CSV
        // Catálogos reales de la API para los selects del formulario (provincias, especialidades, subespecialidades)
        if ($vista === 'form') {
            $catalogos = [
                'provincias' => $this->catalogos->provincias(),
                'especialidades' => $this->catalogos->especialidades(),
                'subespecialidades' => $this->catalogos->subespecialidades(),
                'asociaciones' => $this->catalogos->asociaciones(),
            ];
            $urlCatalogo = $this->entrada() . '?c=registro&a=catalogo';
        }
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