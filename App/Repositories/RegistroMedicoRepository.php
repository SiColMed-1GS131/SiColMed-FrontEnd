<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

/**
 * Acceso a datos de médicos.
 * Con USE_MOCK=1 usa datos de prueba y no llama a la API.
 * Todos los métodos devuelven: ['ok' => bool, 'datos' => mixed, 'error' => string, 'errores' => array]
 *
 * El formulario de registro usa nombres planos (nombre, registro_medico, residencia_calle...).
 * Este repositorio los traduce al contrato de la API y de vuelta:
 *   POST  /medicos       MedicoRegistro: {medico:{...}, direccion:{...}, lugar_trabajo, direccion_trabajo,
 *                        estudio_medico, certificacion, especialidades:[ids], subespecialidades, asociaciones}
 *   PATCH /medicos/{id}  ActualizarMedicoSolicitud: campos planos, direccion:{...} y
 *                        especialidades/subespecialidades/asociaciones: {agregar:[ids], quitar:[ids]}
 *   GET   /medicos       {medicos:[...], total, pagina, tamano_pagina, total_paginas}
 *   GET   /medicos/{id}  perfil agrupado (datos_personales, direccion_residencial, lugares_trabajo...)
 */
final class RegistroMedicoRepository
{
    // Datos de prueba (modo mock), ya con el formato que usan las vistas
    private const MOCK = [
        ['id' => 1, 'nombre' => 'Ana',   'apellido' => 'Pérez',  'nombre_completo' => 'Ana Pérez',    'apellido_paterno' => 'Pérez',  'cedula' => '8-123-456', 'registro_medico' => 'MD1001', 'numero_idoneidad' => 'ID-1001', 'especialidad' => 'Cardiología',  'vigente' => true,  'cert_vigente' => 'vigente',    'empresa' => 'Hospital Santo Tomás', 'cargo' => 'Médico staff', 'provincia' => 'Panamá', 'email_laboral' => 'aperez@hst.gob.pa'],
        ['id' => 2, 'nombre' => 'Luis',  'apellido' => 'Gómez',  'nombre_completo' => 'Luis Gómez',   'apellido_paterno' => 'Gómez',  'cedula' => '4-222-333', 'registro_medico' => 'MD1002', 'numero_idoneidad' => 'ID-1002', 'especialidad' => 'Pediatría',    'vigente' => false, 'cert_vigente' => 'no_vigente', 'empresa' => 'Clínica Hospital',     'cargo' => 'Pediatra', 'provincia' => 'Chiriquí', 'email_laboral' => 'lgomez@clinica.pa'],
        ['id' => 3, 'nombre' => 'María', 'apellido' => 'Rivera', 'nombre_completo' => 'María Rivera', 'apellido_paterno' => 'Rivera', 'cedula' => '9-555-666', 'registro_medico' => 'MD1003', 'numero_idoneidad' => 'ID-1003', 'especialidad' => 'Dermatología', 'vigente' => true,  'cert_vigente' => 'vigente',    'empresa' => 'Policlínica',          'cargo' => 'Dermatóloga', 'provincia' => 'Colón', 'email_laboral' => 'mrivera@policlinica.pa'],
    ];

    // Campo del formulario => campo de la API (datos personales)
    private const CAMPOS_MEDICO = [
        'numero_idoneidad'     => 'numero_idoneidad',
        'registro_medico'      => 'numero_registro_medico',
        'cedula'               => 'cedula',
        'nombre'               => 'primer_nombre',
        'segundo_nombre'       => 'segundo_nombre',
        'apellido_paterno'     => 'apellido_paterno',
        'apellido_materno'     => 'apellido_materno',
        'fecha_nacimiento'     => 'fecha_nacimiento',
        'telefono_residencial' => 'telefono_residencial',
        'celular'              => 'celular',
        'email_personal'       => 'email_personal',
    ];

    // Sufijo del formulario (residencia_calle, trabajo_calle...) => campo de la API
    private const CAMPOS_DIRECCION = [
        'calle'         => 'calle',
        'edificio'      => 'edificio',
        'numero'        => 'numero_direccion',
        'corregimiento' => 'id_corregimiento',
    ];

    private const CAMPOS_TRABAJO = [
        'empresa'          => 'empresa_institucion',
        'cargo'            => 'cargo',
        'telefono_trabajo' => 'telefono_trabajo',
        'email_laboral'    => 'email_laboral',
    ];

    private const CAMPOS_CERTIFICACION = [
        'cert_fecha_obtencion'  => 'fecha_obtencion',
        'cert_fecha_expiracion' => 'fecha_expiracion',
        'cert_puntaje'          => 'puntaje',
    ];

    private const CAMPOS_ESTUDIO = ['titulo', 'institucion', 'lugar', 'fecha'];

    private const RELACIONES = ['especialidades', 'subespecialidades', 'asociaciones'];

    private ApiClient $api;
    private CatalogosRepository $catalogos;

    public function __construct(?ApiClient $api = null, ?CatalogosRepository $catalogos = null)
    {
        $this->api = $api ?? new ApiClient();
        $this->catalogos = $catalogos ?? new CatalogosRepository($this->api);
    }

    /**
     * Una página de médicos (GET /medicos).
     * $filtros usa los nombres de la API: apellido, id_especialidad, id_asociacion,
     * id_subespecialidad, certificacion_vigente, id_provincia, lugar_trabajo, pagina, tamano_pagina.
     * 'datos' es la lista de médicos (formato de las vistas) y 'paginacion' trae total y páginas.
     */
    public function listar(array $filtros = []): array
    {
        if ($this->usaMock()) {
            $resultado = $this->resultado(true, self::MOCK);
            $resultado['paginacion'] = ['total' => \count(self::MOCK), 'pagina' => 1, 'tamano_pagina' => 20, 'total_paginas' => 1];
            return $resultado;
        }

        $filtros = \array_filter($filtros, static fn ($valor): bool => \is_scalar($valor) && $valor !== '');
        $query = \http_build_query($filtros);
        $resultado = $this->interpretar($this->api->getJson('/medicos' . ($query !== '' ? '?' . $query : '')));
        if (!$resultado['ok']) {
            return $resultado;
        }

        $cuerpo = $resultado['datos'];
        if (!\is_array($cuerpo['medicos'] ?? null)) {
            return $this->resultado(false, [], 'La API devolvió una lista de médicos con formato inesperado.');
        }
        $resultado['datos'] = \array_map([$this, 'desdeResumen'], $cuerpo['medicos']);
        $resultado['paginacion'] = [
            'total'         => (int) ($cuerpo['total'] ?? 0),
            'pagina'        => (int) ($cuerpo['pagina'] ?? 1),
            'tamano_pagina' => (int) ($cuerpo['tamano_pagina'] ?? 0),
            'total_paginas' => (int) ($cuerpo['total_paginas'] ?? 0),
        ];
        return $resultado;
    }

    // Un médico por id, con los nombres que usan el formulario y la vista de perfil
    public function obtener(int $id): array
    {
        if ($this->usaMock()) {
            foreach (self::MOCK as $medico) {
                if ($medico['id'] === $id) {
                    return $this->resultado(true, $medico);
                }
            }
            return $this->resultado(false, [], 'Médico no encontrado.');
        }

        $resultado = $this->interpretar($this->api->getJson('/medicos/' . $id));
        if ($resultado['ok']) {
            $resultado['datos'] = $this->desdePerfil($resultado['datos'], $id);
        }
        return $resultado;
    }

    // Registrar médico nuevo (POST /medicos). $datos: campos del formulario (o una fila del CSV)
    public function crear(array $datos): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, $datos);
        }
        return $this->interpretar($this->api->postJson('/medicos', $this->paraRegistro($datos)));
    }

    // Guardar cambios de un médico (PATCH /medicos/{id}). Solo se envía lo que cambió.
    public function actualizar(int $id, array $datos): array
    {
        if ($this->usaMock()) {
            return $this->resultado(true, $datos);
        }

        $actual = $this->obtener($id);
        if (!$actual['ok']) {
            return $actual;
        }

        $cambios = $this->paraActualizacion($datos, $actual['datos']);
        if ($cambios === []) {
            return $this->resultado(true, []);
        }
        return $this->interpretar($this->api->patchJson('/medicos/' . $id, $cambios));
    }

    // ------------------------------------------------------------------
    // Formulario → API
    // ------------------------------------------------------------------

    private function paraRegistro(array $d): array
    {
        $medico = [];
        foreach (self::CAMPOS_MEDICO as $campo => $api) {
            $medico[$api] = $this->texto($d[$campo] ?? null);
        }

        $payload = [
            'medico'    => $medico,
            'direccion' => $this->direccion($d, 'residencia') + ['apartado_postal' => $this->texto($d['apartado_postal'] ?? null)],
        ];

        $trabajo = $this->bloque($d, self::CAMPOS_TRABAJO);
        if ($trabajo !== null) {
            // La API exige la dirección del trabajo cuando hay lugar de trabajo
            $payload['lugar_trabajo'] = $trabajo;
            $payload['direccion_trabajo'] = $this->direccion($d, 'trabajo');
        }

        $basico = $d['educacion']['basico'][0] ?? [];
        $estudio = \is_array($basico) ? $this->bloque($basico, \array_combine(self::CAMPOS_ESTUDIO, self::CAMPOS_ESTUDIO)) : null;
        if ($estudio !== null) {
            $payload['estudio_medico'] = ['tipo_estudio' => 'Título básico'] + $estudio;
        }

        $certificacion = $this->bloque($d, self::CAMPOS_CERTIFICACION);
        if ($certificacion !== null) {
            $certificacion['puntaje'] = $this->entero($certificacion['puntaje']);
            $certificacion['vigente'] = $this->vigencia($d['cert_vigente'] ?? '');
            $payload['certificacion'] = $certificacion;
        }

        foreach (self::RELACIONES as $relacion) {
            $payload[$relacion] = $this->ids($d[$relacion] ?? []);
        }
        return $payload;
    }

    /**
     * Compara el formulario con el médico actual (ya aplanado por desdePerfil).
     * Un campo vacío no se envía: la API no permite borrar valores (min_length=1).
     * Lugar de trabajo, estudios y certificación no se envían: PATCH /medicos/{id} no los acepta.
     */
    private function paraActualizacion(array $d, array $actual): array
    {
        $cambios = [];
        foreach (self::CAMPOS_MEDICO as $campo => $api) {
            $nuevo = $this->texto($d[$campo] ?? null);
            if ($nuevo !== null && $nuevo !== $this->texto($actual[$campo] ?? null)) {
                $cambios[$api] = $nuevo;
            }
        }

        $direccion = [];
        $nuevaDireccion = $this->direccion($d, 'residencia') + ['apartado_postal' => $this->texto($d['apartado_postal'] ?? null)];
        $direccionActual = $this->direccion($actual, 'residencia') + ['apartado_postal' => $this->texto($actual['apartado_postal'] ?? null)];
        foreach ($nuevaDireccion as $api => $valor) {
            if ($valor !== null && $valor !== $direccionActual[$api]) {
                $direccion[$api] = $valor;
            }
        }
        if ($direccion !== []) {
            $cambios['direccion'] = $direccion;
        }

        foreach (self::RELACIONES as $relacion) {
            $nuevos = $this->ids($d[$relacion] ?? []);
            $anteriores = $this->ids($actual[$relacion] ?? []);
            $agregar = \array_values(\array_diff($nuevos, $anteriores));
            $quitar = \array_values(\array_diff($anteriores, $nuevos));
            if ($agregar !== [] || $quitar !== []) {
                $cambios[$relacion] = ['agregar' => $agregar, 'quitar' => $quitar];
            }
        }
        return $cambios;
    }

    // {calle, edificio, numero_direccion, id_corregimiento} a partir de prefijo_calle, prefijo_edificio...
    private function direccion(array $d, string $prefijo): array
    {
        $direccion = [];
        foreach (self::CAMPOS_DIRECCION as $sufijo => $api) {
            $valor = $d[$prefijo . '_' . $sufijo] ?? null;
            $direccion[$api] = $api === 'id_corregimiento' ? $this->entero($valor) : $this->texto($valor);
        }
        return $direccion;
    }

    // Devuelve los campos traducidos, o null si todos están vacíos (el bloque es opcional)
    private function bloque(array $d, array $campos): ?array
    {
        $bloque = [];
        foreach ($campos as $campo => $api) {
            $bloque[$api] = $this->texto($d[$campo] ?? null);
        }
        return \array_filter($bloque, static fn ($v) => $v !== null) === [] ? null : $bloque;
    }

    private function texto(mixed $valor): ?string
    {
        if (!\is_scalar($valor)) {
            return null;
        }
        $valor = \trim((string) $valor);
        return $valor === '' ? null : $valor;
    }

    private function entero(mixed $valor): ?int
    {
        $valor = $this->texto($valor);
        return $valor !== null && \ctype_digit($valor) ? (int) $valor : null;
    }

    private function vigencia(mixed $valor): ?bool
    {
        return match ($valor) {
            'vigente'    => true,
            'no_vigente' => false,
            default      => null,
        };
    }

    /** @return int[] IDs positivos sin repetir */
    private function ids(mixed $valores): array
    {
        if (!\is_array($valores)) {
            return [];
        }
        $ids = \array_map(fn ($v) => $this->entero($v), $valores);
        return \array_values(\array_unique(\array_filter($ids, static fn ($v) => $v !== null && $v > 0)));
    }

    // ------------------------------------------------------------------
    // API → vistas
    // ------------------------------------------------------------------

    // Fila de GET /medicos con los nombres que usan Directorio y Registro
    private function desdeResumen(mixed $m): array
    {
        $m = \is_array($m) ? $m : [];
        return [
            'id'               => (int) ($m['id_medico'] ?? 0),
            'nombre_completo'  => (string) ($m['nombre_completo'] ?? ''),
            'numero_idoneidad' => (string) ($m['numero_idoneidad'] ?? ''),
            'especialidad'     => (string) ($m['especialidades'] ?? ''),
            'asociaciones'     => (string) ($m['asociaciones'] ?? ''),
            'vigente'          => (bool) ($m['certificacion_vigente'] ?? false),
            'provincia'        => (string) ($m['provincia'] ?? ''),
            'lugar_trabajo'    => (string) ($m['lugar_trabajo'] ?? ''),
        ];
    }

    /**
     * Aplana el perfil de GET /medicos/{id}: claves del formulario de registro
     * más las que usa la vista de perfil (Directorio/ver.php).
     * La API devuelve la dirección con nombres; aquí se buscan sus IDs en los catálogos.
     */
    private function desdePerfil(mixed $perfil, int $id): array
    {
        $perfil = \is_array($perfil) ? $perfil : [];
        $dp = \is_array($perfil['datos_personales'] ?? null) ? $perfil['datos_personales'] : [];
        $medico = ['id' => $id, 'foto_url' => (string) ($dp['foto'] ?? ''), 'foto' => (string) ($dp['foto'] ?? '')];
        foreach (self::CAMPOS_MEDICO as $campo => $api) {
            $medico[$campo] = (string) ($dp[$api] ?? '');
        }

        $residencia = \is_array($perfil['direccion_residencial'] ?? null) ? $perfil['direccion_residencial'] : [];
        $medico['apartado_postal'] = (string) ($residencia['apartado_postal'] ?? '');
        $medico += $this->direccionDesdeApi($residencia, 'residencia');

        $trabajo = \is_array($perfil['lugares_trabajo'][0] ?? null) ? $perfil['lugares_trabajo'][0] : [];
        foreach (self::CAMPOS_TRABAJO as $campo => $api) {
            $medico[$campo] = (string) ($trabajo[$api] ?? '');
        }
        $direccionTrabajo = \is_array($trabajo['direccion'] ?? null) ? $trabajo['direccion'] : [];
        $medico += $this->direccionDesdeApi($direccionTrabajo, 'trabajo');

        $estudio = \is_array($perfil['estudios'][0] ?? null) ? $perfil['estudios'][0] : [];
        $medico['educacion']['basico'][0] = \array_intersect_key($estudio, \array_flip(self::CAMPOS_ESTUDIO));

        $certificaciones = \is_array($perfil['certificaciones'] ?? null) ? $perfil['certificaciones'] : [];
        $cert = \is_array(\end($certificaciones)) ? \end($certificaciones) : [];
        foreach (self::CAMPOS_CERTIFICACION as $campo => $api) {
            $medico[$campo] = (string) ($cert[$api] ?? '');
        }
        $vigente = isset($cert['vigente']) ? (bool) $cert['vigente'] : null;
        $medico['cert_vigente'] = $vigente === null ? '' : ($vigente ? 'vigente' : 'no_vigente');

        $columnas = ['especialidades' => 'id_especialidad', 'subespecialidades' => 'id_subespecialidad', 'asociaciones' => 'id_asociacion'];
        $nombres = [];
        foreach ($columnas as $relacion => $columna) {
            $lista = \is_array($perfil[$relacion] ?? null) ? $perfil[$relacion] : [];
            $medico[$relacion] = \array_map(static fn ($r) => (int) ($r[$columna] ?? 0), $lista);
            $nombres[$relacion] = \implode(', ', \array_map(static fn ($r) => (string) ($r['nombre'] ?? ''), $lista));
        }

        // Claves de la vista de perfil
        $medico['apellido'] = \trim($medico['apellido_paterno'] . ' ' . $medico['apellido_materno']);
        $medico['especialidad'] = $nombres['especialidades'];
        $medico['subespecialidad'] = $nombres['subespecialidades'] !== '' ? $nombres['subespecialidades'] : null;
        $medico['organizaciones'] = $nombres['asociaciones'] !== '' ? $nombres['asociaciones'] : null;
        $medico['provincia'] = (string) ($residencia['provincia'] ?? '');
        $medico['vigente'] = (bool) $vigente;
        $medico['empresa_institucion'] = $medico['empresa'];
        $partes = \array_filter([$direccionTrabajo['calle'] ?? null, $direccionTrabajo['corregimiento'] ?? null,
                                 $direccionTrabajo['distrito'] ?? null, $direccionTrabajo['provincia'] ?? null]);
        $medico['direccion_laboral'] = $partes !== [] ? \implode(', ', $partes) : null;

        return $medico;
    }

    // Dirección de la API (con nombres) → campos del formulario (con IDs)
    private function direccionDesdeApi(array $dir, string $prefijo): array
    {
        $idProvincia = CatalogosRepository::idPorNombre($this->catalogos->provincias(), $dir['provincia'] ?? null);
        $idDistrito = $idProvincia === null ? null
            : CatalogosRepository::idPorNombre($this->catalogos->distritos($idProvincia), $dir['distrito'] ?? null);
        $idCorregimiento = $idDistrito === null ? null
            : CatalogosRepository::idPorNombre($this->catalogos->corregimientos($idDistrito), $dir['corregimiento'] ?? null);

        return [
            $prefijo . '_provincia'     => (string) ($idProvincia ?? ''),
            $prefijo . '_distrito'      => (string) ($idDistrito ?? ''),
            $prefijo . '_corregimiento' => (string) ($idCorregimiento ?? ''),
            $prefijo . '_calle'         => (string) ($dir['calle'] ?? ''),
            $prefijo . '_edificio'      => (string) ($dir['edificio'] ?? ''),
            $prefijo . '_numero'        => (string) ($dir['numero_direccion'] ?? ''),
        ];
    }

    // Ruta del error de la API (sin "body") → campo del formulario. null si no tiene campo propio.
    private function campoDelFormulario(array $ruta): ?string
    {
        $grupo = \count($ruta) > 1 ? (string) $ruta[0] : '';
        $campo = (string) \end($ruta);

        if ($grupo === 'direccion' || $grupo === 'direccion_trabajo') {
            $prefijo = $grupo === 'direccion' ? 'residencia' : 'trabajo';
            if ($campo === 'apartado_postal') {
                return 'apartado_postal';
            }
            $sufijo = \array_search($campo, self::CAMPOS_DIRECCION, true);
            return $sufijo === false ? null : $prefijo . '_' . $sufijo;
        }
        if ($grupo === 'lugar_trabajo') {
            $form = \array_search($campo, self::CAMPOS_TRABAJO, true);
            return $form === false ? null : $form;
        }
        if ($grupo === 'certificacion') {
            $form = \array_search($campo, self::CAMPOS_CERTIFICACION, true);
            return $form === false ? ($campo === 'vigente' ? 'cert_vigente' : null) : $form;
        }
        if ($grupo === 'medico' || $grupo === '') {
            $form = \array_search($campo, self::CAMPOS_MEDICO, true);
            return $form === false ? null : $form;
        }
        return null;
    }

    private function usaMock(): bool
    {
        return getenv('USE_MOCK') === '1';
    }

    // Convierte la respuesta de ApiClient al formato de arriba
    private function interpretar(array $respuesta): array
    {
        $status = (int) ($respuesta['status'] ?? 0);
        $cuerpo = json_decode((string) ($respuesta['body'] ?? ''), true);

        if ($status >= 200 && $status < 300) {
            return $this->resultado(true, \is_array($cuerpo) ? $cuerpo : []);
        }
        if ($status === 0) {
            return $this->resultado(false, [], 'No se pudo contactar con la API.');
        }

        // 422: errores por campo, formato [{"loc": ["body", ..., "campo"], "msg": "..."}]
        if ($status === 422 && \is_array($cuerpo['detail'] ?? null)) {
            $errores = [];
            $sinCampo = [];
            foreach ($cuerpo['detail'] as $item) {
                $ruta = \is_array($item['loc'] ?? null) ? \array_values(\array_diff($item['loc'], ['body'])) : [];
                $mensaje = (string) ($item['msg'] ?? 'Dato no válido.');
                $campo = $ruta === [] ? null : $this->campoDelFormulario($ruta);
                if ($campo !== null) {
                    $errores[$campo] = $mensaje;
                } else {
                    $sinCampo[] = $mensaje;
                }
            }
            return $this->resultado(false, [], \implode(' ', \array_unique($sinCampo)), $errores);
        }

        // Los errores de PostgreSQL llegan aquí
        $detalle = $cuerpo['detail'] ?? null;
        return $this->resultado(false, [], \is_string($detalle) ? $detalle : 'No se pudo completar la operación.');
    }

    private function resultado(bool $ok, mixed $datos = [], string $error = '', array $errores = []): array
    {
        return ['ok' => $ok, 'datos' => $datos, 'error' => $error, 'errores' => $errores];
    }
}
