<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

// Pide y guarda los movimientos contables (Módulo 5).
// Con USE_MOCK=1 usa datos de prueba guardados en la sesión, en vez de la API.
// Cada línea del movimiento: id_movimiento, fecha, descripcion, id_cuenta, nombre_cuenta, tipo, monto
final class MovimientosRepository
{
    private const SESION_MOCK = 'mock_movimientos';

    private ApiClient $api;
    private CuentasRepository $cuentas;

    public function __construct(?ApiClient $api = null, ?CuentasRepository $cuentas = null)
    {
        $this->api = $api ?? new ApiClient();
        $this->cuentas = $cuentas ?? new CuentasRepository();
    }

    // Movimientos de un día. GET /movimientos?fecha=AAAA-MM-DD
    public function listarPorFecha(string $fecha): array
    {
        if ($this->usaMock()) {
            $lineas = array_values(array_filter($this->mockLeer(), fn(array $l) => $l['fecha'] === $fecha));
            return $this->resultado(true, $this->conNombres($lineas));
        }

        $respuesta = $this->interpretar($this->llamar('getJson', '/movimientos?' . http_build_query(['fecha' => $fecha])));
        return $this->normalizarLista($respuesta);
    }

    // Movimientos de una cuenta en un rango. GET /cuentas/{id}/movimientos?inicio=...&fin=...
    public function listarPorCuenta(int $idCuenta, string $inicio, string $fin): array
    {
        if ($this->usaMock()) {
            $lineas = array_values(array_filter(
                $this->mockLeer(),
                fn(array $l) => $l['id_cuenta'] === $idCuenta && $l['fecha'] >= $inicio && $l['fecha'] <= $fin
            ));
            usort($lineas, fn(array $a, array $b) => [$a['fecha'], $a['id_movimiento']] <=> [$b['fecha'], $b['id_movimiento']]);
            return $this->resultado(true, $this->conNombres($lineas));
        }

        $ruta = '/cuentas/' . $idCuenta . '/movimientos?' . http_build_query(['inicio' => $inicio, 'fin' => $fin]);
        return $this->normalizarLista($this->interpretar($this->llamar('getJson', $ruta)));
    }

    // Registra un movimiento. POST /movimientos
    // $datos: fecha (AAAA-MM-DD), descripcion, id_cuenta (int), monto ("1250.00"), tipo (DEBITO|CREDITO)
    public function registrar(array $datos): array
    {
        if ($this->usaMock()) {
            $lineas = $this->mockLeer();
            $id = $lineas === [] ? 1 : max(array_column($lineas, 'id_movimiento')) + 1;
            $lineas[] = [
                'id_movimiento' => $id,
                'fecha' => $datos['fecha'],
                'descripcion' => $datos['descripcion'],
                'id_cuenta' => (int) $datos['id_cuenta'],
                'nombre_cuenta' => '',
                'tipo' => $datos['tipo'],
                'monto' => $this->dosDecimales($datos['monto']),
            ];
            $this->mockGuardar($lineas);
            $this->marcarCuentaConMovimientos((int) $datos['id_cuenta']);
            return $this->resultado(true, ['id_movimiento' => $id]);
        }

        // El monto viaja como texto con 2 decimales para no perder centavos
        $payload = [
            'fecha' => $datos['fecha'],
            'descripcion' => $datos['descripcion'],
            'id_cuenta' => (int) $datos['id_cuenta'],
            'monto' => $this->dosDecimales($datos['monto']),
            'tipo' => $datos['tipo'],
        ];
        return $this->interpretar($this->llamar('postJson', '/movimientos', $payload));
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

    private function dosDecimales(mixed $monto): string
    {
        return number_format((float) $monto, 2, '.', '');
    }

    // Deja cada línea siempre con el mismo formato
    private function normalizar(array $l): array
    {
        $tipo = strtoupper(strtr((string) ($l['tipo'] ?? ''), ['É' => 'E', 'é' => 'E']));
        return [
            'id_movimiento' => (int) ($l['id_movimiento'] ?? 0),
            'fecha' => substr((string) ($l['fecha'] ?? ''), 0, 10),
            'descripcion' => (string) ($l['descripcion'] ?? ''),
            'id_cuenta' => (int) ($l['id_cuenta'] ?? 0),
            'nombre_cuenta' => (string) ($l['nombre_cuenta'] ?? ''),
            'tipo' => $tipo,
            'monto' => $this->dosDecimales($l['monto'] ?? 0),
        ];
    }

    private function normalizarLista(array $respuesta): array
    {
        if ($respuesta['ok']) {
            $datos = is_array($respuesta['datos']) ? $respuesta['datos'] : [];
            $respuesta['datos'] = array_map([$this, 'normalizar'], array_values($datos));
        }
        return $respuesta;
    }

    // Agrega el nombre de la cuenta a cada línea (en modo mock)
    private function conNombres(array $lineas): array
    {
        $nombres = [];
        $todas = $this->cuentas->listar();
        if ($todas['ok']) {
            foreach ($todas['datos'] as $c) {
                $nombres[$c['id_cuenta']] = $c['nombre'];
            }
        }
        foreach ($lineas as &$l) {
            $l['nombre_cuenta'] = $nombres[(int) $l['id_cuenta']] ?? ('Cuenta ' . $l['id_cuenta']);
        }
        unset($l);
        return array_map([$this, 'normalizar'], $lineas);
    }

    // En modo mock, una cuenta con movimientos ya no puede cambiar de tipo
    private function marcarCuentaConMovimientos(int $idCuenta): void
    {
        $clave = 'mock_cuentas';
        if (!isset($_SESSION[$clave]) || !is_array($_SESSION[$clave])) {
            return;
        }
        foreach ($_SESSION[$clave] as &$c) {
            if ((int) $c['id_cuenta'] === $idCuenta) {
                $c['tiene_movimientos'] = true;
            }
        }
        unset($c);
    }

    private function mockLeer(): array
    {
        if (!isset($_SESSION[self::SESION_MOCK]) || !is_array($_SESSION[self::SESION_MOCK])) {
            $iniciales = $this->mockInicial();
            $archivo = dirname(__DIR__) . '/Mock/movimientos.json';
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

    private function mockGuardar(array $lineas): void
    {
        $_SESSION[self::SESION_MOCK] = $lineas;
    }

    // Datos de prueba (ids de cuenta iguales a los de CuentasRepository)
    private function mockInicial(): array
    {
        $hoy = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));
        return [
            ['id_movimiento' => 1, 'fecha' => $hoy,  'descripcion' => 'Cobro de cuotas del mes', 'id_cuenta' => 1, 'tipo' => 'DEBITO',  'monto' => '1250.75'],
            ['id_movimiento' => 1, 'fecha' => $hoy,  'descripcion' => 'Cobro de cuotas del mes', 'id_cuenta' => 3, 'tipo' => 'CREDITO', 'monto' => '1250.75'],
            ['id_movimiento' => 2, 'fecha' => $ayer, 'descripcion' => 'Compra de papelería',     'id_cuenta' => 5, 'tipo' => 'DEBITO',  'monto' => '45.50'],
            ['id_movimiento' => 2, 'fecha' => $ayer, 'descripcion' => 'Compra de papelería',     'id_cuenta' => 1, 'tipo' => 'CREDITO', 'monto' => '45.50'],
        ];
    }

    private function interpretar(array $respuesta): array
    {
        $status = (int) ($respuesta['status'] ?? 0);
        $cuerpo = json_decode((string) ($respuesta['body'] ?? ''), true);

        if ($status >= 200 && $status < 300) {
            return $this->resultado(true, is_array($cuerpo) ? $cuerpo : []);
        }
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

        $detalle = $cuerpo['detail'] ?? null;
        return $this->resultado(false, [], is_string($detalle) ? $detalle : 'No se pudo completar la operación.');
    }

    private function resultado(bool $ok, mixed $datos = [], string $error = '', array $errores = []): array
    {
        return ['ok' => $ok, 'datos' => $datos, 'error' => $error, 'errores' => $errores];
    }
}
