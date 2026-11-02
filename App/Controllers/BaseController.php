<?php

declare(strict_types=1);

namespace App\Controllers;

use DateTimeImmutable;

require_once __DIR__ . '/../Helpers/ui.php';

/**
 * BaseController · Provee render unificado, manejo de flash, redirecciones y helpers comunes.
 */
abstract class BaseController
{
    /**
     * Renderiza una vista con layout estándar (header, sidebar, main, footer).
     *
     * @param string      $vista        Ruta de la vista relativa a App/Views/ (sin extensión .php)
     * @param array       $datos        Variables pasadas a la vista
     * @param string      $currentPage  Identificador de la página para activar la opción en el sidebar
     * @param string|null $extraCss     Archivo CSS adicional en Public/Assets/CSS/Dedicated/
     * @param bool        $esPublico    Si es true, no carga el sidebar (vista para visitantes públicos)
     * @param string|null $script       Ruta o nombre de script JS a cargar con defer
     */
    protected function render(
        string $vista,
        array $datos = [],
        string $currentPage = '',
        ?string $extraCss = null,
        bool $esPublico = false,
        ?string $script = null
    ): void {
        extract($datos, EXTR_SKIP);

        $entrada = $this->entrada();
        $base = $this->base();
        $rutaVista = __DIR__ . '/../Views/' . ltrim($vista, '/') . '.php';

        require __DIR__ . '/../Views/Partials/header.php';

        if ($esPublico) {
            require $rutaVista;
        } else {
            echo '<div class="app-layout">';
            require __DIR__ . '/../Views/Partials/sidebar.php';
            echo '<main class="main-content">';
            require $rutaVista;
            if ($script !== null && $script !== '') {
                $scriptSrc = str_starts_with($script, '/') ? $script : '/Assets/JavaScript/Dedicated/' . $script;
                echo '<script src="' . e($base . $scriptSrc) . '" defer></script>';
            }
            require __DIR__ . '/../Views/Partials/footer.php';
        }
    }

    /**
     * Redirige a una URL y finaliza la ejecución.
     */
    protected function redirigir(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Valida y extrae un entero positivo (ID) o null si es inválido.
     */
    protected function leerId(mixed $valor): ?int
    {
        if (is_int($valor) && $valor > 0) {
            return $valor;
        }
        return is_string($valor) && ctype_digit($valor) && (int) $valor > 0 ? (int) $valor : null;
    }

    /**
     * Valida que una fecha tenga formato Y-m-d válido. Devuelve la fecha o null.
     */
    protected function fechaValida(mixed $valor): ?string
    {
        if (!is_string($valor) || $valor === '') {
            return null;
        }
        $f = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        return ($f !== false && $f->format('Y-m-d') === $valor) ? $valor : null;
    }

    /**
     * Formatea Y-m-d a d/m/Y para mostrar en pantalla.
     */
    protected function fechaPantalla(string $valor): string
    {
        $f = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        return $f !== false ? $f->format('d/m/Y') : $valor;
    }

    /**
     * Guarda un mensaje flash en la sesión.
     */
    protected function guardarFlash(string $clave, string $tipo, string $texto): void
    {
        $_SESSION['flash_' . $clave] = ['tipo' => $tipo, 'texto' => $texto];
    }

    /**
     * Lee y consume el mensaje flash guardado.
     */
    protected function tomarFlash(string $clave): ?array
    {
        $flash = $_SESSION['flash_' . $clave] ?? null;
        unset($_SESSION['flash_' . $clave]);
        return is_array($flash) ? $flash : null;
    }

    /**
     * Ruta relativa a index.php (funciona tanto en Docker como en subdirectorios de WAMP/XAMPP).
     */
    protected function entrada(): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/index.php';
    }

    /**
     * Ruta base de la aplicación web ('' en Docker, subcarpeta en WAMP).
     */
    protected function base(): string
    {
        return substr($this->entrada(), 0, -strlen('/index.php'));
    }
}
