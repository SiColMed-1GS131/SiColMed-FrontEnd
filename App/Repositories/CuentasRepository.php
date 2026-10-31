<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

// Pide y guarda los datos de las cuentas.
// Con USE_MOCK=1 usa datos de prueba en vez de la API.
final class CuentasRepository
{
    private const SESION_MOCK = 'mock_cuentas';

    // Datos de prueba 
    private const MOCK_INICIAL = [
        ['id_cuenta' => 1, 'nombre' => 'Caja general',          'tipo' => 'DEBITO',  'activa' => true,  'tiene_movimientos' => true],
        ['id_cuenta' => 2, 'nombre' => 'Banco Nacional',        'tipo' => 'DEBITO',  'activa' => true,  'tiene_movimientos' => true],
        ['id_cuenta' => 3, 'nombre' => 'Cuotas de asociados',   'tipo' => 'CREDITO', 'activa' => true,  'tiene_movimientos' => true],
        ['id_cuenta' => 4, 'nombre' => 'Donaciones',            'tipo' => 'CREDITO', 'activa' => true,  'tiene_movimientos' => false],
        ['id_cuenta' => 5, 'nombre' => 'Gastos de papelería',   'tipo' => 'DEBITO',  'activa' => true,  'tiene_movimientos' => false],
        ['id_cuenta' => 6, 'nombre' => 'Fondo de congresos',    'tipo' => 'CREDITO', 'activa' => false, 'tiene_movimientos' => true],
    ];

    private ApiClient $api;

    public function __construct(?ApiClient $api = null)
    {
        $this->api = $api ?? new ApiClient();
    }

    // Lista de cuentas (null = todas, true = activas, false = inactivas)
    public function listar(?bool $activa = null): array
    {
        if ($this->usaMock()) {
            $cuentas = $this->mockLeer();
            if ($activa !== null) {
                $cuentas = array_values(array_filter($cuentas, fn(array $c) => $c['activa'] === $activa));
            }
            return $this->resultado(true, $cuentas);
        }

        // GET /cuentas
        $query = $activa === null ? '' : '?' . http_build_query(['activa' => $activa ? '1' : '0']);
        $respuesta = $this->interpretar($this->llamar('getJson', '/cuentas' . $query));
        if ($respuesta['ok']) {
            $respuesta['datos'] = array_map([$this, 'normalizar'], is_array($respuesta['datos']) ? $respuesta['datos'] : []);
        }
        return $respuesta;
    }

    // Busca una cuenta por su id
    public function obtener(int $id): array
    {
        $lista = $this->listar();
        if (!$lista['ok']) {
            return $lista;
        }
        foreach ($lista['datos'] as $cuenta) {
            if ($cuenta['id_cuenta'] === $id) {
                return $this->resultado(true, $cuenta);
            }
        }
        return $this->resultado(false, [], 'La cuenta no existe.');
    }

    // Crea una cuenta nueva
    public function crear(array $datos): array
    {
        if ($this->usaMock()) {
            $cuentas = $this->mockLeer();
            if ($this->nombreRepetido($cuentas, $datos['nombre'], null)) {
                return $this->resultado(false, [], '', ['nombre' => 'Ya existe una cuenta con ese nombre.']);
            }
            $nueva = [
                'id_cuenta' => $cuentas === [] ? 1 : max(array_column($cuentas, 'id_cuenta')) + 1,
                'nombre' => $datos['nombre'],
                'tipo' => $datos['tipo'],
                'activa' => true,
                'tiene_movimientos' => false,
            ];
            $cuentas[] = $nueva;
            $this->mockGuardar($cuentas);
            return $this->resultado(true, $nueva);
        }

        // POST /cuentas
        return $this->interpretar($this->llamar('postJson', '/cuentas', $datos));
    }

    // Guarda los cambios de una cuenta
    public function editar(int $id, array $datos): array
    {
        if ($this->usaMock()) {
            $cuentas = $this->mockLeer();
            if ($this->nombreRepetido($cuentas, $datos['nombre'], $id)) {
                return $this->resultado(false, [], '', ['nombre' => 'Ya existe una cuenta con ese nombre.']);
            }
            foreach ($cuentas as &$c) {
                if ($c['id_cuenta'] === $id) {
                    // Con movimientos no se puede cambiar el tipo
                    if ($c['tiene_movimientos'] && $c['tipo'] !== $datos['tipo']) {
                        return $this->resultado(false, [], '', ['tipo' => 'No se puede cambiar el tipo porque la cuenta ya tiene movimientos.']);
                    }
                    $c['nombre'] = $datos['nombre'];
                    $c['tipo'] = $datos['tipo'];
                    $c['activa'] = $datos['activa'];
                    $this->mockGuardar($cuentas);
                    return $this->resultado(true, $c);
                }
            }
            unset($c);
            return $this->resultado(false, [], 'La cuenta no existe.');
        }

        // PUT /cuentas/{id}
        return $this->interpretar($this->llamar('putJson', '/cuentas/' . $id, $datos));
    }

    // Elimina la cuenta. Responde 'ELIMINADA' o 'DESACTIVADA'
    public function eliminar(int $id): array
    {
        if ($this->usaMock()) {
            $cuentas = $this->mockLeer();
            foreach ($cuentas as $i => $c) {
                if ($c['id_cuenta'] === $id) {
                    // Con movimientos solo se desactiva
                    if ($c['tiene_movimientos']) {
                        $cuentas[$i]['activa'] = false;
                        $this->mockGuardar($cuentas);
                        return $this->resultado(true, 'DESACTIVADA');
                    }
                    unset($cuentas[$i]);
                    $this->mockGuardar(array_values($cuentas));
                    return $this->resultado(true, 'ELIMINADA');
                }
            }
            return $this->resultado(false, [], 'La cuenta no existe.');
        }

        // DELETE /cuentas/{id}
        $respuesta = $this->interpretar($this->llamar('deleteJson', '/cuentas/' . $id));
        if ($respuesta['ok']) {
            $resultado = strtoupper((string) ($respuesta['datos']['resultado'] ?? ''));
            $respuesta['datos'] = in_array($resultado, ['ELIMINADA', 'DESACTIVADA'], true) ? $resultado : 'ELIMINADA';
        }
        return $respuesta;
    }

    private function usaMock(): bool
    {
        return getenv('USE_MOCK') === '1';
    }

    // Llama a ApiClient (si el método todavía no existe, devuelve error)
    private function llamar(string $metodo, string $ruta, ?array $payload = null): array
    {
        if (!method_exists($this->api, $metodo)) {
            return ['status' => 0, 'body' => null];
        }
        return $payload === null ? $this->api->$metodo($ruta) : $this->api->$metodo($ruta, $payload);
    }

    // Deja cada cuenta siempre con el mismo formato
    private function normalizar(array $c): array
    {
        $tipo = strtoupper(strtr((string) ($c['tipo'] ?? ''), ['É' => 'E', 'é' => 'E', 'Í' => 'I', 'í' => 'I']));
        return [
            'id_cuenta' => (int) ($c['id_cuenta'] ?? 0),
            'nombre' => (string) ($c['nombre'] ?? ''),
            'tipo' => $tipo,
            'activa' => filter_var($c['activa'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'tiene_movimientos' => filter_var($c['tiene_movimientos'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

        // Pasa el texto a minúsculas (funciona aunque PHP no tenga mbstring)
    private function minusculas(string $texto): string
    {
        $texto = trim($texto);
        return function_exists('mb_strtolower') ? mb_strtolower($texto, 'UTF-8') : strtolower($texto);
    }
    
    // Revisa si ya hay otra cuenta con el mismo nombre
    private function nombreRepetido(array $cuentas, string $nombre, ?int $excepto): bool
    {
        $buscado = $this->minusculas($nombre);
        foreach ($cuentas as $c) {
            if ($c['id_cuenta'] !== $excepto && $this->minusculas($c['nombre']) === $buscado) {
                return true;
            }
        }
        return false;
    }

    private function mockLeer(): array
    {
        if (!isset($_SESSION[self::SESION_MOCK]) || !is_array($_SESSION[self::SESION_MOCK])) {
            $iniciales = self::MOCK_INICIAL;
            $archivo = dirname(__DIR__) . '/Mock/cuentas.json';
            if (is_file($archivo)) {
                $json = json_decode((string) file_get_contents($archivo), true);
                if (is_array($json)) {
                    $iniciales = $json;
                }
            }
            $_SESSION[self::SESION_MOCK] = array_map([$this, 'normalizar'], $iniciales);
        }
        return $_SESSION[self::SESION_MOCK];
    }

    // Guarda los datos de prueba en la sesión
    private function mockGuardar(array $cuentas): void
    {
        $_SESSION[self::SESION_MOCK] = $cuentas;
    }

    private function interpretar(array $respuesta): array
    {
        $status = (int) ($respuesta['status'] ?? 0);
        $cuerpo = json_decode((string) ($respuesta['body'] ?? ''), true);

        // Todo salió bien
        if ($status >= 200 && $status < 300) {
            return $this->resultado(true, is_array($cuerpo) ? $cuerpo : []);
        }
        // La API no respondió
        if ($status === 0) {
            return $this->resultado(false, [], 'No se pudo contactar con la API.');
        }

        // Errores por campo
        $errores = [];
        if (is_array($cuerpo['detail'] ?? null)) {
            foreach ($cuerpo['detail'] as $item) {
                $campo = is_array($item['loc'] ?? null) ? (string) end($item['loc']) : '';
                if ($campo !== '') {
                    $errores[$campo] = (string) ($item['msg'] ?? 'Dato no válido.');
                }
            }
        } elseif (is_array($cuerpo) && !isset($cuerpo['detail'])) {
            foreach ($cuerpo as $campo => $mensaje) {
                if (is_string($campo) && is_string($mensaje)) {
                    $errores[$campo] = $mensaje;
                }
            }
        }
        if ($errores !== []) {
            return $this->resultado(false, [], '', $errores);
        }

        // Otro error
        $detalle = $cuerpo['detail'] ?? null;
        return $this->resultado(false, [], is_string($detalle) ? $detalle : 'No se pudo completar la operación.');
    }

    private function resultado(bool $ok, mixed $datos = [], string $error = '', array $errores = []): array
    {
        return ['ok' => $ok, 'datos' => $datos, 'error' => $error, 'errores' => $errores];
    }
}