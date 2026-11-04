<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

/**
 * Catálogos de la API (provincias, distritos, corregimientos, especialidades,
 * subespecialidades y asociaciones). Los formularios y filtros envían IDs, no nombres.
 * Con USE_MOCK=1 usa datos de prueba y no llama a la API.
 * Cada método devuelve una lista de ['id' => int, 'nombre' => string, ...].
 * Si la API falla devuelve [], para que la pantalla se muestre igual.
 */
final class CatalogosRepository
{
    private const MOCK = [
        'provincias'        => [['id' => 1, 'nombre' => 'Panamá'], ['id' => 2, 'nombre' => 'Chiriquí']],
        'distritos'         => [['id' => 1, 'nombre' => 'Panamá', 'id_provincia' => 1]],
        'corregimientos'    => [['id' => 1, 'nombre' => 'Bella Vista', 'id_distrito' => 1]],
        'especialidades'    => [['id' => 1, 'nombre' => 'Medicina General'], ['id' => 2, 'nombre' => 'Pediatría']],
        'subespecialidades' => [['id' => 1, 'nombre' => 'Neonatología', 'id_especialidad' => 2]],
        'asociaciones'      => [['id' => 1, 'nombre' => 'Soc. Panameña de Pediatría']],
    ];

    /** Columna id de cada catálogo en la respuesta de la API. */
    private const COLUMNA_ID = [
        'provincias'        => 'id_provincia',
        'distritos'         => 'id_distrito',
        'corregimientos'    => 'id_corregimiento',
        'especialidades'    => 'id_especialidad',
        'subespecialidades' => 'id_subespecialidad',
        'asociaciones'      => 'id_asociacion',
    ];

    private ApiClient $api;

    /** Respuestas ya pedidas en esta petición, para no repetir llamadas. */
    private array $cache = [];

    public function __construct(?ApiClient $api = null)
    {
        $this->api = $api ?? new ApiClient();
    }

    public function provincias(): array
    {
        return $this->pedir('provincias', '/provincias');
    }

    public function distritos(int $idProvincia): array
    {
        if ($this->usaMock()) {
            return array_values(array_filter(self::MOCK['distritos'], fn(array $d) => $d['id_provincia'] === $idProvincia));
        }
        return $this->pedir('distritos', '/provincias/' . $idProvincia . '/distritos');
    }

    public function corregimientos(int $idDistrito): array
    {
        if ($this->usaMock()) {
            return array_values(array_filter(self::MOCK['corregimientos'], fn(array $c) => $c['id_distrito'] === $idDistrito));
        }
        return $this->pedir('corregimientos', '/distritos/' . $idDistrito . '/corregimientos');
    }

    public function especialidades(): array
    {
        return $this->pedir('especialidades', '/especialidades');
    }

    public function subespecialidades(): array
    {
        return $this->pedir('subespecialidades', '/subespecialidades');
    }

    /**
     * Subespecialidades de las especialidades indicadas (GET /subespecialidades no filtra).
     * $conservar: IDs que se mantienen aunque su especialidad no esté elegida, para no
     * quitarle a un médico una subespecialidad que ya tiene al guardar el formulario.
     *
     * @param int[] $idsEspecialidad
     * @param int[] $conservar
     */
    public function subespecialidadesDe(array $idsEspecialidad, array $conservar = []): array
    {
        return array_values(array_filter($this->subespecialidades(), static fn(array $s) =>
            in_array((int) ($s['id_especialidad'] ?? 0), $idsEspecialidad, true)
            || in_array($s['id'], $conservar, true)));
    }

    public function asociaciones(): array
    {
        return $this->pedir('asociaciones', '/asociaciones');
    }

    /** Busca el id de un elemento por su nombre (sin importar mayúsculas ni tildes). */
    public static function idPorNombre(array $lista, ?string $nombre): ?int
    {
        if ($nombre === null || trim($nombre) === '') {
            return null;
        }
        $buscado = self::clave($nombre);
        foreach ($lista as $item) {
            if (self::clave((string) $item['nombre']) === $buscado) {
                return (int) $item['id'];
            }
        }
        return null;
    }

    private static function clave(string $texto): string
    {
        $texto = strtr(trim($texto), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
                                      'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n']);
        return strtolower($texto);
    }

    private function pedir(string $catalogo, string $ruta): array
    {
        if ($this->usaMock()) {
            return self::MOCK[$catalogo];
        }
        if (isset($this->cache[$ruta])) {
            return $this->cache[$ruta];
        }

        $respuesta = $this->api->getJson($ruta);
        $cuerpo = json_decode((string) ($respuesta['body'] ?? ''), true);
        if ($respuesta['status'] !== 200 || !is_array($cuerpo)) {
            return [];
        }

        $columna = self::COLUMNA_ID[$catalogo];
        $lista = [];
        foreach ($cuerpo as $fila) {
            if (is_array($fila) && isset($fila[$columna], $fila['nombre'])) {
                $lista[] = ['id' => (int) $fila[$columna], 'nombre' => (string) $fila['nombre']] + $fila;
            }
        }
        return $this->cache[$ruta] = $lista;
    }

    private function usaMock(): bool
    {
        return getenv('USE_MOCK') === '1';
    }
}
