<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Traduce el formulario de Registro médico al cuerpo de POST /medicos (schema MedicoRegistro del backend):
 *
 *   {
 *     medico, direccion,
 *     lugar_trabajo, direccion_trabajo,      // van juntos o ninguno
 *     estudio_medico, certificacion,         // opcionales
 *     especialidades[], subespecialidades[], asociaciones[]   // listas de ids de catálogo
 *   }
 *
 * y hace el camino inverso para rellenar el formulario al editar.
 *
 * El backend usa extra="forbid": cualquier clave que no esté en el schema se rechaza con 422.
 * Referencia: SiColMed-BackEnd/app/schemas/medicos.py.
 *
 * Lo que NO está confirmado:
 *  - TIPO_ESTUDIO_BASICO: el schema pide "tipo_estudio" pero no dice qué valores acepta.
 *  - La forma de GET /medicos/{id} (PerfilMedico es un dict libre): haciaFormulario() es tolerante.
 *  - PATCH /medicos/{id} usa otro schema (campos editables) que todavía no tengo.
 */
final class MedicoPayload
{
    /** Valor de "tipo_estudio" para el título básico del formulario. Confirmar con BD/backend. */
    private const TIPO_ESTUDIO_BASICO = 'BASICO';

    /** campo del formulario => clave de "medico" en la API */
    private const MEDICO = [
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

    /** campo del formulario => clave de "lugar_trabajo" */
    private const LUGAR_TRABAJO = [
        'empresa'          => 'empresa_institucion',
        'cargo'            => 'cargo',
        'telefono_trabajo' => 'telefono_trabajo',
        'email_laboral'    => 'email_laboral',
    ];

    /** sufijo del formulario (tras "residencia_" o "trabajo_") => clave de dirección */
    private const DIRECCION = [
        'id_corregimiento' => 'id_corregimiento',
        'calle'            => 'calle',
        'edificio'         => 'edificio',
        'numero'           => 'numero_direccion',
    ];

    /** campo del formulario (educacion[basico][0][...]) => clave de "estudio_medico" */
    private const ESTUDIO = [
        'titulo'      => 'titulo',
        'institucion' => 'institucion',
        'lugar'       => 'lugar',
        'fecha'       => 'fecha',
    ];

    /** campo del formulario => clave de "certificacion" */
    private const CERTIFICACION = [
        'cert_fecha_obtencion'  => 'fecha_obtencion',
        'cert_puntaje'          => 'puntaje',
        'cert_fecha_expiracion' => 'fecha_expiracion',
    ];

    /** @return array<string,mixed> */
    public static function desdeFormulario(array $f): array
    {
        $payload = [
            'medico'    => self::copiar($f, self::MEDICO),
            'direccion' => self::direccion($f, 'residencia'),
        ];

        // apartado_postal pertenece a la dirección en la API (en el formulario está en "Contacto")
        $payload['direccion']['apartado_postal'] = self::limpiar($f['apartado_postal'] ?? null);

        // Lugar de trabajo y su dirección van juntos: si se llena cualquiera de los dos, se envían los dos
        $trabajo = self::copiar($f, self::LUGAR_TRABAJO);
        $dirTrabajo = self::direccion($f, 'trabajo');
        if (self::hayDatos($trabajo) || self::hayDatos($dirTrabajo)) {
            $payload['lugar_trabajo'] = $trabajo;
            $payload['direccion_trabajo'] = $dirTrabajo;
        }

        $basico = $f['educacion']['basico'][0] ?? [];
        if (\is_array($basico)) {
            $estudio = self::copiar($basico, self::ESTUDIO);
            if (self::hayDatos($estudio)) {
                $payload['estudio_medico'] = ['tipo_estudio' => self::TIPO_ESTUDIO_BASICO] + $estudio;
            }
        }

        $cert = [];
        foreach (self::CERTIFICACION as $campo => $claveApi) {
            $cert[$claveApi] = $claveApi === 'puntaje' ? self::enteroNoNegativo($f[$campo] ?? null) : self::limpiar($f[$campo] ?? null);
        }
        if (self::hayDatos($cert)) {
            $payload['certificacion'] = $cert;   // "vigente" lo calcula la API
        }

        $payload['especialidades'] = self::ids($f['educacion']['especialidades'] ?? [], 'id_especialidad');
        $payload['subespecialidades'] = self::ids($f['educacion']['subespecialidades'] ?? [], 'id_subespecialidad');
        $payload['asociaciones'] = self::ids($f['asociaciones'] ?? [], null);

        return $payload;
    }

    /**
     * Respuesta de GET /medicos/{id} => campos planos del formulario.
     * Acepta las mismas claves que el registro (medico, direccion, lugar_trabajo, direccion_trabajo,
     * estudio_medico, certificacion, especialidades, ...) o un diccionario plano.
     */
    public static function haciaFormulario(array $api): array
    {
        $m = \is_array($api['medico'] ?? null) ? $api['medico'] : $api;
        $form = ['id' => $m['id_medico'] ?? ($api['id_medico'] ?? null)];

        foreach (self::MEDICO as $campo => $claveApi) {
            $form[$campo] = $m[$claveApi] ?? '';
        }

        $dir = \is_array($api['direccion'] ?? null) ? $api['direccion'] : [];
        self::direccionAFormulario($form, 'residencia', $dir);
        $form['apartado_postal'] = $dir['apartado_postal'] ?? '';
        self::direccionAFormulario($form, 'trabajo', \is_array($api['direccion_trabajo'] ?? null) ? $api['direccion_trabajo'] : []);

        $trabajo = \is_array($api['lugar_trabajo'] ?? null) ? $api['lugar_trabajo'] : [];
        foreach (self::LUGAR_TRABAJO as $campo => $claveApi) {
            $form[$campo] = $trabajo[$claveApi] ?? '';
        }

        $estudio = \is_array($api['estudio_medico'] ?? null) ? $api['estudio_medico'] : [];
        $form['educacion']['basico'][0] = [];
        foreach (self::ESTUDIO as $campo => $claveApi) {
            $form['educacion']['basico'][0][$campo] = $estudio[$claveApi] ?? '';
        }

        $form['educacion']['especialidades'] = self::filasDeIds($api['especialidades'] ?? [], 'id_especialidad');
        $form['educacion']['subespecialidades'] = self::filasDeIds($api['subespecialidades'] ?? [], 'id_subespecialidad');
        $form['asociaciones'] = \array_map('strval', self::ids($api['asociaciones'] ?? [], 'id_asociacion'));

        // Certificación: "vigente" solo se muestra; lo calcula la API
        $cert = \is_array($api['certificacion'] ?? null) ? $api['certificacion'] : [];
        foreach (self::CERTIFICACION as $campo => $claveApi) {
            $form[$campo] = $cert[$claveApi] ?? '';
        }
        $vigente = $cert['vigente'] ?? ($m['certificacion_vigente'] ?? null);
        $form['cert_vigente'] = $vigente === null ? '' : ($vigente ? 'vigente' : 'no_vigente');

        return $form;
    }

    /**
     * Ruta de un error 422 de FastAPI (["body","medico","numero_idoneidad"]) => campo del formulario.
     * Devuelve '' si no se reconoce (p. ej. errores del modelo completo).
     *
     * @param list<string|int> $loc
     */
    public static function campoDeError(array $loc): string
    {
        $loc = \array_values(\array_filter($loc, static fn ($p): bool => $p !== 'body'));
        $bloque = (string) ($loc[0] ?? '');
        $clave = (string) ($loc[1] ?? '');

        switch ($bloque) {
            case 'medico':
                $campo = \array_search($clave, self::MEDICO, true);
                return $campo !== false ? (string) $campo : '';
            case 'direccion':
                return $clave === 'apartado_postal' ? 'apartado_postal' : self::campoDireccion('residencia', $clave);
            case 'direccion_trabajo':
                return self::campoDireccion('trabajo', $clave);
            case 'lugar_trabajo':
                $campo = \array_search($clave, self::LUGAR_TRABAJO, true);
                return $campo !== false ? (string) $campo : '';
            case 'estudio_medico':
                return 'educacion_basico_0';
            case 'certificacion':
                $campo = \array_search($clave, self::CERTIFICACION, true);
                return $campo !== false ? (string) $campo : '';
            case 'especialidades':
            case 'subespecialidades':
                return 'educacion_' . $bloque . '_' . ($clave === '' ? '0' : $clave);
            case 'asociaciones':
                return 'asociaciones';
        }
        return '';
    }

    // ---------------------------------------------------------------

    private static function campoDireccion(string $prefijo, string $claveApi): string
    {
        $campo = \array_search($claveApi, self::DIRECCION, true);
        return $campo !== false ? $prefijo . '_' . $campo : '';
    }

    private static function direccion(array $f, string $prefijo): array
    {
        $dir = [];
        foreach (self::DIRECCION as $campo => $claveApi) {
            $valor = $f[$prefijo . '_' . $campo] ?? null;
            $dir[$claveApi] = $campo === 'id_corregimiento' ? self::entero($valor) : self::limpiar($valor);
        }
        // Sin corregimiento no se envía la clave: la API responde "Field required" en ese campo concreto
        if ($dir['id_corregimiento'] === null) {
            unset($dir['id_corregimiento']);
        }
        return $dir;
    }

    private static function direccionAFormulario(array &$form, string $prefijo, array $api): void
    {
        foreach (self::DIRECCION as $campo => $claveApi) {
            $form[$prefijo . '_' . $campo] = $api[$claveApi] ?? '';
        }
        // Para preseleccionar provincia y distrito, si la API los devuelve junto a la dirección
        $form[$prefijo . '_id_provincia'] = $api['id_provincia'] ?? '';
        $form[$prefijo . '_id_distrito'] = $api['id_distrito'] ?? '';
    }

    /** Lista de ids positivos sin repetir. $clave: si el elemento es una fila del formulario, cuál es su id. */
    private static function ids(mixed $lista, ?string $clave): array
    {
        $ids = [];
        if (!\is_array($lista)) {
            return $ids;
        }
        foreach ($lista as $item) {
            $valor = \is_array($item) ? ($item[$clave ?? 'id'] ?? null) : $item;
            $n = self::entero($valor);
            if ($n !== null && !\in_array($n, $ids, true)) {
                $ids[] = $n;
            }
        }
        return $ids;
    }

    /** [3, 5] o [{id_especialidad: 3}] => filas del formulario [['id_especialidad' => 3], ...] */
    private static function filasDeIds(mixed $lista, string $clave): array
    {
        $filas = [];
        foreach (self::ids($lista, $clave) as $id) {
            $filas[] = [$clave => $id];
        }
        return $filas;
    }

    /** @param array<string,string> $mapa */
    private static function copiar(array $f, array $mapa): array
    {
        $salida = [];
        foreach ($mapa as $campo => $claveApi) {
            $salida[$claveApi] = self::limpiar($f[$campo] ?? null);
        }
        return $salida;
    }

    private static function hayDatos(array $bloque): bool
    {
        foreach ($bloque as $valor) {
            if ($valor !== null) {
                return true;
            }
        }
        return false;
    }

    private static function limpiar(mixed $valor): ?string
    {
        if (!\is_scalar($valor)) {
            return null;
        }
        $texto = \trim((string) $valor);
        return $texto === '' ? null : $texto;
    }

    private static function entero(mixed $valor): ?int
    {
        $n = \filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $n === false ? null : $n;
    }

    private static function enteroNoNegativo(mixed $valor): ?int
    {
        $n = \filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        return $n === false ? null : $n;
    }
}
