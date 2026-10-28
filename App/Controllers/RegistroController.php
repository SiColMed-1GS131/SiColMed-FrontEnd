<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\RegistroMedicoRepository;

/** Registro médico, lista, formulario (nuevo o editar) y guardado. */
final class RegistroController
{
    private const ENTRADA = '/SiColMed-FrontEnd/Public/index.php';

    private RegistroMedicoRepository $medicos;

    public function __construct()
    {
        $this->medicos = new RegistroMedicoRepository();
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
        $resultado = $id === null ? null : $this->medicos->obtener($id);

        if ($resultado === null || !$resultado['ok']) {
            $this->guardarMensaje(($resultado['error'] ?? '') ?: 'Médico no encontrado.');
            $this->redirigir(self::ENTRADA . '?c=registro&a=index');
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

        // Posible cambio, foto y PDF ahora mismo solo se envían los textos.
        $resultado = $id === null
            ? $this->medicos->crear($datos)
            : $this->medicos->actualizar($id, $datos);

        if ($resultado['ok']) {
            $this->guardarMensaje('Médico guardado correctamente.');
            $this->redirigir(self::ENTRADA . '?c=registro&a=index');
        }

        // Si hay errores, se vuelve a mostrar el formulario con los datos y errores
        $this->render('form', [
            'medico' => $id === null ? $datos : $datos + ['id' => $id],
            'errores' => $resultado['errores'],
            'errorGeneral' => $resultado['error'],
        ]);
    }

    // Pendiente
    private function verificarAcceso(): void
    {
    }

    // Arma la página (posibles cambios dependiendo de /Views/Partials/...)
    private function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        $currentPage = 'registro';
        $extraCss = 'registro.css';   // el header carga base.css y este CSS

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
       require __DIR__ . '/../Views/Admin/Registro/' . $vista . '.php';
        require __DIR__ . '/../Views/Partials/footer.php';
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
}