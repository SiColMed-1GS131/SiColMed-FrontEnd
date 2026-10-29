<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * HomeController · página de inicio pública de SiColMed.
 *
 *   Public/index.php                (sin parámetros)
 *   Public/index.php?c=home&a=index
 *
 * La página tiene su propio diseño (no usa sidebar). Los textos de ejemplo
 * (estadísticas y comunicados) están aquí arriba para cambiarlos sin tocar la vista.
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
            'directorio' => $entrada . '?c=directorio&a=index',
        ];

        // Opciones del buscador rápido (mismos valores que usa el filtro del Directorio)
        $especialidades = [
            'cardiologia' => 'Cardiología', 'cirugia-general' => 'Cirugía General',
            'dermatologia' => 'Dermatología', 'ginecologia' => 'Ginecología y Obstetricia',
            'medicina-interna' => 'Medicina Interna', 'oftalmologia' => 'Oftalmología',
            'ortopedia' => 'Ortopedia', 'pediatria' => 'Pediatría', 'psiquiatria' => 'Psiquiatría',
        ];
        $provincias = [
            'bocas-del-toro' => 'Bocas del Toro', 'cocle' => 'Coclé', 'colon' => 'Colón',
            'chiriqui' => 'Chiriquí', 'darien' => 'Darién', 'herrera' => 'Herrera',
            'los-santos' => 'Los Santos', 'panama' => 'Panamá', 'panama-oeste' => 'Panamá Oeste',
            'veraguas' => 'Veraguas',
        ];

        // Servicios del sistema (icono = archivo de Assets/IMG/ICONS sin extensión)
        $servicios = [
            ['icono' => 'lupa',        'titulo' => 'Directorio médico',   'texto' => 'Busca médicos por especialidad, asociación, provincia o apellido. Abierto para todos.', 'url' => $urls['directorio']],
            ['icono' => 'editar',      'titulo' => 'Registro médico',     'texto' => 'El Administrador del CMP registra y actualiza la información de cada asociado.',       'url' => $urls['login']],
            ['icono' => 'usuario',     'titulo' => 'Perfil médico',       'texto' => 'Cada médico consulta sus datos, estudios, especialidades y certificación vigente.',    'url' => $urls['login']],
            ['icono' => 'gancho',      'titulo' => 'Certificaciones',     'texto' => 'Consulta si la certificación está vigente, su puntaje y su fecha de expiración.',      'url' => $urls['directorio']],
            ['icono' => 'libro',       'titulo' => 'Cuentas y movimientos', 'texto' => 'El Tesorero gestiona las cuentas contables y registra los movimientos diarios.',     'url' => $urls['login']],
            ['icono' => 'estadisticas', 'titulo' => 'Balance General',    'texto' => 'Reporte contable por rango de fechas para la Administración de la Asociación.',        'url' => $urls['login']],
        ];

        // DATOS DE EJEMPLO: reemplazar por cifras reales cuando la API esté conectada
        $estadisticas = [
            ['valor' => 1200, 'prefijo' => '+', 'etiqueta' => 'Médicos registrados'],
            ['valor' => 10,   'prefijo' => '',  'etiqueta' => 'Provincias'],
            ['valor' => 40,   'prefijo' => '+', 'etiqueta' => 'Especialidades'],
            ['valor' => 24,   'prefijo' => '',  'etiqueta' => 'Horas, todos los días (24/7)'],
        ];

        // Quién usa el sistema y qué puede hacer (según los requisitos del proyecto)
        $roles = [
            ['titulo' => 'Médicos',                     'items' => ['Ver y mantener su perfil', 'Consultar su certificación', 'Solicitar cambios de datos']],
            ['titulo' => 'Administrador del CMP',       'items' => ['Registrar médicos', 'Editar su información', 'Gestionar solicitudes de cambio']],
            ['titulo' => 'Tesorero',                    'items' => ['Crear y editar cuentas', 'Registrar movimientos diarios', 'Consultar el historial por cuenta']],
            ['titulo' => 'Administrador de la Asociación', 'items' => ['Generar el Balance General', 'Filtrar por rango de fechas', 'Imprimir el reporte']],
        ];

        // DATOS DE EJEMPLO: comunicados
        $noticias = [
            ['fecha' => '2026-10-20', 'categoria' => 'Certificaciones', 'titulo' => 'Recuerda mantener vigente tu certificación', 'texto' => 'Consulta en tu perfil la fecha de expiración y el puntaje acumulado.'],
            ['fecha' => '2026-10-12', 'categoria' => 'Sistema',         'titulo' => 'Nuevo directorio médico en línea',         'texto' => 'Ahora puedes buscar colegas por especialidad, provincia o apellido.'],
            ['fecha' => '2026-10-05', 'categoria' => 'Tesorería',       'titulo' => 'Reportes contables por rango de fechas',   'texto' => 'El Balance General ya se puede generar e imprimir desde la plataforma.'],
        ];

        require __DIR__ . '/../Views/Client/Home/index.php';
    }
}