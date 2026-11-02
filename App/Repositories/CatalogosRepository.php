<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\ApiClient;

/**
 * Catálogos de la API para llenar los filtros (provincias, especialidades,
 * subespecialidades, asociaciones). Si la API falla devuelve listas vacías.
 */
final class CatalogosRepository
{
    private const MOCK = [
        'provincias' => [['id_provincia' => 1, 'nombre' => 'Panamá'], ['id_provincia' => 2, 'nombre' => 'Chiriquí'], ['id_provincia' => 3, 'nombre' => 'Colón']],
        'especialidades' => [['id_especialidad' => 1, 'nombre' => 'Cardiología'], ['id_especialidad' => 2, 'nombre' => 'Pediatría']],
        'subespecialidades' => [],
        'asociaciones' => [['id_asociacion' => 1, 'nombre' => 'Soc. Panameña de Cardiología']],
        'distritos' => [['id_distrito' => 1, 'nombre' => 'Panamá'], ['id_distrito' => 2, 'nombre' => 'San Miguelito']],
        'corregimientos' => [['id_corregimiento' => 1, 'nombre' => 'Bella Vista'], ['id_corregimiento' => 2, 'nombre' => 'San Francisco']],
    ];

    private ApiClient $api;

    public function __construct(?ApiClient $api = null)
    {
        $this->api = $api ?? new ApiClient();
    }

    public function provincias(): array { return $this->obtener('provincias'); }
    public function especialidades(): array { return $this->obtener('especialidades'); }
    public function subespecialidades(): array { return $this->obtener('subespecialidades'); }
    public function asociaciones(): array { return $this->obtener('asociaciones'); }

    /** GET /provincias/{id}/distritos */
    public function distritos(int $idProvincia): array
    {
        return $this->obtener('distritos', '/provincias/' . $idProvincia . '/distritos');
    }

    /** GET /distritos/{id}/corregimientos */
    public function corregimientos(int $idDistrito): array
    {
        return $this->obtener('corregimientos', '/distritos/' . $idDistrito . '/corregimientos');
    }

    private function obtener(string $recurso, ?string $ruta = null): array
    {
        if (\getenv('USE_MOCK') === '1') {
            return self::MOCK[$recurso];
        }
        $res = $this->api->getJson($ruta ?? '/' . $recurso);
        $cuerpo = \json_decode((string) ($res['body'] ?? ''), true);
        return $res['status'] >= 200 && $res['status'] < 300 && \is_array($cuerpo) && \array_is_list($cuerpo)
            ? $cuerpo
            : [];
    }
}
