<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CatalogosRepository;

/**
 * HomeController · página de inicio pública de SiColMed.
 *
 *   Public/index.php                (sin parámetros)
 *   Public/index.php?c=home&a=index
 *
 * La página tiene su propio diseño (no usa sidebar). Especialidades y provincias
 * vienen de la API; los comunicados son un placeholder rotulado como ejemplo.
 */
final class HomeController
{
    public function index(): void
    {
        // Ruta base que funciona igual en WAMP (/SiColMed-FrontEnd/Public) y en Docker (raíz)
        $base    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $entrada = $base . '/index.php';

        $urls = [
            'inicio'     => $entrada,
            'login'      => $entrada . '?c=login&a=login',
            'registro'   => $entrada . '?c=register&a=index',
            // Página pública aparte (sin sidebar). Dentro del sistema se usa a=index.
            'directorio' => $entrada . '?c=directorio&a=publico',
        ];

        // Opciones del buscador rápido: catálogos de la API (GET /especialidades y /provincias),
        // con los IDs que espera el filtro del Directorio. Si la API falla, quedan vacías.
        $catalogos = new CatalogosRepository();
        $especialidades = array_column($catalogos->especialidades(), 'nombre', 'id');
        $provincias = array_column($catalogos->provincias(), 'nombre', 'id');

        // Servicios del sistema (icono = archivo de Assets/IMG/ICONS sin extensión)
        $servicios = [
            ['icono' => 'lupa',        'titulo' => 'Directorio médico',   'texto' => 'Busca médicos por especialidad, asociación, provincia o apellido. Abierto para todos.', 'url' => $urls['directorio']],
            ['icono' => 'editar',      'titulo' => 'Registro médico',     'texto' => 'El Administrador del CMP registra y actualiza la información de cada asociado.',       'url' => $urls['login']],
            ['icono' => 'usuario',     'titulo' => 'Perfil médico',       'texto' => 'Cada médico consulta sus datos, estudios, especialidades y certificación vigente.',    'url' => $urls['login']],
            ['icono' => 'gancho',      'titulo' => 'Certificaciones',     'texto' => 'Consulta si la certificación está vigente, su puntaje y su fecha de expiración.',      'url' => $urls['directorio']],
            ['icono' => 'libro',       'titulo' => 'Cuentas y movimientos', 'texto' => 'El Tesorero gestiona las cuentas contables y registra los movimientos diarios.',     'url' => $urls['login']],
            ['icono' => 'estadisticas', 'titulo' => 'Balance General',    'texto' => 'Reporte contable por rango de fechas para la Administración de la Asociación.',        'url' => $urls['login']],
        ];

        // Quién usa el sistema y qué puede hacer (según los requisitos del proyecto)
        $roles = [
            ['titulo' => 'Médicos',                     'items' => ['Ver y mantener su perfil', 'Consultar su certificación', 'Solicitar cambios de datos']],
            ['titulo' => 'Administrador del CMP',       'items' => ['Registrar médicos', 'Editar su información', 'Gestionar solicitudes de cambio']],
            ['titulo' => 'Tesorero',                    'items' => ['Crear y editar cuentas', 'Registrar movimientos diarios', 'Consultar el historial por cuenta']],
            ['titulo' => 'Administrador de la Asociación', 'items' => ['Generar el Balance General', 'Filtrar por rango de fechas', 'Imprimir el reporte']],
        ];

        // PLACEHOLDER: no hay endpoint ni tabla de noticias (la web pública no es parte del
        // alcance del equipo). La vista lo rotula como contenido de ejemplo y no muestra fecha.
        $noticias = [
            ['fecha' => null, 'categoria' => 'Ejemplo', 'titulo' => 'Espacio para comunicados del Colegio', 'texto' => 'Aquí se publicarán los avisos cuando exista un módulo de noticias.'],
        ];
        $noticiasEjemplo = true;

        require __DIR__ . '/../Views/Client/Home/index.php';
    }
}