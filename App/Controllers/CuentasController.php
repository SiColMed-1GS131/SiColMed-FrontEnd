<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CuentasRepository;

// Controlador de la pantalla de Cuentas (solo para el tesorero)
final class CuentasController
{
    private const VISTAS = __DIR__ . '/../Views/Contabilidad/Cuentas/';
    private const POR_PAGINA = 10;                          // cuentas por página
    private const TIPOS = ['DEBITO', 'CREDITO'];            // tipos permitidos
    private const ESTADOS = ['todas', 'activas', 'inactivas']; // opciones del filtro

    private CuentasRepository $cuentas;

    public function __construct()
    {
        $this->cuentas = new CuentasRepository();
    }

    public function index(): void
    {
        $this->verificarAcceso();
        $this->pantalla(['cuenta' => [], 'errores' => [], 'errorGeneral' => '']);
    }

    public function nueva(): void
    {
        $this->verificarAcceso();
        $this->pantalla(['cuenta' => [], 'errores' => [], 'errorGeneral' => '', 'enfocarFormulario' => true]);
    }

    public function editar(): void
    {
        $this->verificarAcceso();

        $id = $this->leerId($_GET['id'] ?? null);
        $resultado = $id === null ? null : $this->cuentas->obtener($id);

        // Si no existe, vuelve a la lista con un mensaje
        if ($resultado === null || !$resultado['ok']) {
            $this->guardarFlash('error', ($resultado['error'] ?? '') ?: 'La cuenta no existe.');
            $this->redirigir('/cuentas');
        }

        $this->pantalla([
            'cuenta' => $resultado['datos'],
            'errores' => [],
            'errorGeneral' => '',
            'enfocarFormulario' => true,
        ]);
    }

    // Guarda una cuenta nueva o los cambios de una existente
    public function guardar(): void
    {
        $this->verificarAcceso();
        $this->verificarCsrf();

        $id = $this->leerId($_POST['id_cuenta'] ?? null);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $tipo = strtoupper((string) ($_POST['tipo'] ?? ''));
        $errores = [];
        $actual = null;

        // Si trae id, es una edición
        if ($id !== null) {
            $buscada = $this->cuentas->obtener($id);
            if (!$buscada['ok']) {
                $this->guardarFlash('error', $buscada['error'] ?: 'La cuenta no existe.');
                $this->redirigir('/cuentas');
            }
            $actual = $buscada['datos'];
            // Si tiene movimientos, el tipo no cambia
            if ($actual['tiene_movimientos']) {
                $tipo = $actual['tipo'];
            }
        }

        // Revisar los campos
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre de la cuenta es obligatorio.';
        } elseif (preg_match_all('/./us', $nombre) > 100) {
            $errores['nombre'] = 'El nombre no puede superar 100 caracteres.';
        }
        if (!in_array($tipo, self::TIPOS, true)) {
            $errores['tipo'] = 'Elige el tipo de cuenta: Débito o Crédito.';
        }

        $datos = ['nombre' => $nombre, 'tipo' => $tipo];
        if ($actual !== null) {
            $datos['activa'] = isset($_POST['activa']);
        }

        if ($errores === []) {
            $resultado = $actual === null
                ? $this->cuentas->crear($datos)
                : $this->cuentas->editar($id, $datos);

            if ($resultado['ok']) {
                $this->guardarFlash('success', $actual === null ? 'La cuenta fue creada exitosamente.' : 'Los cambios fueron guardados exitosamente.');
                $this->redirigir('/cuentas' . $this->queryLista());
            }
            $errores = $resultado['errores'];
            $errorGeneral = $resultado['error'];
        }

        // Si algo falló, se muestra otra vez el formulario con los errores
        $this->pantalla([
            'cuenta' => $actual === null ? $datos : array_merge($actual, $datos),
            'errores' => $errores,
            'errorGeneral' => $errorGeneral ?? '',
            'enfocarFormulario' => true,
        ]);
    }

    // Elimina una cuenta (o la desactiva si tiene movimientos)
    public function eliminar(): void
    {
        $this->verificarAcceso();
        $this->verificarCsrf();

        $id = $this->leerId($_POST['id_cuenta'] ?? null);
        $resultado = $id === null ? null : $this->cuentas->eliminar($id);

        if ($resultado === null || !$resultado['ok']) {
            $this->guardarFlash('error', ($resultado['error'] ?? '') ?: 'No se pudo eliminar la cuenta.');
        } elseif ($resultado['datos'] === 'DESACTIVADA') {
            $this->guardarFlash('warning', 'La cuenta tiene movimientos, por eso se desactivó en lugar de eliminarse.');
        } else {
            $this->guardarFlash('success', 'La cuenta fue eliminada exitosamente.');
        }

        $this->redirigir('/cuentas' . $this->queryLista());
    }

    private function pantalla(array $datosFormulario): void
    {
        $estado = $this->leerEstado();
        $filtroActiva = match ($estado) {
            'activas' => true,
            'inactivas' => false,
            default => null,
        };

        $lista = $this->cuentas->listar($filtroActiva);
        $cuentas = $lista['ok'] ? $lista['datos'] : [];

        usort($cuentas, fn(array $a, array $b) => strcasecmp($a['nombre'], $b['nombre']));

        $totalCuentas = count($cuentas);
        $totalPaginas = max(1, (int) ceil($totalCuentas / self::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);
        $cuentasPagina = array_slice($cuentas, ($pagina - 1) * self::POR_PAGINA, self::POR_PAGINA);

        $this->render('index', array_merge([
            'enfocarFormulario' => false,
        ], $datosFormulario, [
            'cuentas' => $cuentasPagina,
            'totalCuentas' => $totalCuentas,
            'estado' => $estado,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'errorApi' => $lista['ok'] ? '' : $lista['error'],
            'flash' => $this->tomarFlash(),
            'csrf' => $this->tokenCsrf(),
            'queryLista' => $this->queryLista(),
        ]));
    }

    // Solo deja entrar al tesorero (puede cambiar cuando se suba Auth.php)
    private function verificarAcceso(): void
    {
        $auth = 'App\\Middleware\\Auth';
        if (class_exists($auth) && method_exists($auth, 'requiere')) {
            $auth::requiere(['tesorero']);
            return;
        }

        $rol = $_SESSION['rol'] ?? null;
        $permitido = $rol === 'tesorero' || ($rol === null && getenv('USE_MOCK') === '1');
        if (!$permitido) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            exit('Acceso denegado: esta sección es solo para el Tesorero.');
        }
    }

    private function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        $currentPage = 'cuentas';

        require __DIR__ . '/../Views/Partials/header.php';
        echo '<link rel="stylesheet" href="/Assets/CSS/base.css">';
        echo '<link rel="stylesheet" href="/Assets/CSS/Dedicated/cuentas.css">';
        echo '<div class="app-layout">';
        require __DIR__ . '/../Views/Partials/sidebar.php';
        echo '<main class="main-content">';
        require self::VISTAS . $vista . '.php';
        echo '<script src="/Assets/JavaScript/Dedicated/cuentas.js" defer></script>';
        require __DIR__ . '/../Views/Partials/footer.php';
    }

    // Lee el filtro de la URL (todas, activas o inactivas)
    private function leerEstado(): string
    {
        $estado = $_GET['estado'] ?? 'todas';
        return is_string($estado) && in_array($estado, self::ESTADOS, true) ? $estado : 'todas';
    }

    // Guarda el filtro y la página para volver al mismo lugar de la lista
    private function queryLista(): string
    {
        $origen = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        $params = [];
        $estado = $origen['estado'] ?? '';
        if (is_string($estado) && in_array($estado, self::ESTADOS, true) && $estado !== 'todas') {
            $params['estado'] = $estado;
        }
        $pagina = $origen['pagina'] ?? '';
        if (is_string($pagina) && ctype_digit($pagina) && (int) $pagina > 1) {
            $params['pagina'] = $pagina;
        }
        return $params === [] ? '' : '?' . http_build_query($params);
    }

    // El id debe ser un número entero mayor que 0
    private function leerId(mixed $valor): ?int
    {
        return is_string($valor) && ctype_digit($valor) && (int) $valor > 0 ? (int) $valor : null;
    }

    // Código de seguridad para los formularios
    private function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf_cuentas'])) {
            $_SESSION['csrf_cuentas'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_cuentas'];
    }

    // Revisa que el código de seguridad sea el correcto
    private function verificarCsrf(): void
    {
        $enviado = $_POST['csrf'] ?? '';
        if (!is_string($enviado) || !hash_equals($this->tokenCsrf(), $enviado)) {
            $this->guardarFlash('error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirigir('/cuentas');
        }
    }

    private function guardarFlash(string $tipo, string $texto): void
    {
        $_SESSION['cuentas_flash'] = ['tipo' => $tipo, 'texto' => $texto];
    }

    private function tomarFlash(): ?array
    {
        $flash = $_SESSION['cuentas_flash'] ?? null;
        unset($_SESSION['cuentas_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . $ruta);
        exit;
    }
}