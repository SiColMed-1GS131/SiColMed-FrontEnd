<?php
// App/Controllers/DirectorioController.php

class DirectorioController {
    
    // Acción principal: Muestra la lista y los filtros
    public function index() {
        // 1. Recibimos los filtros de la URL (GET)
        $filtros = [
            'q' => $_GET['q'] ?? '',
            'especialidad' => $_GET['especialidad'] ?? '',
            'asociacion' => $_GET['asociacion'] ?? '',
            'certificacion' => $_GET['certificacion'] ?? '',
            'provincia' => $_GET['provincia'] ?? '',
            'lugar_trabajo' => $_GET['lugar_trabajo'] ?? ''
        ];

        // 2. Pedimos los datos a la API (usando la clase que preparó Lione/P1)
        $api = new ApiClient();
        $medicos = $api->get('/medicos', $filtros);

        // 3. Regla de negocio: Ordenar alfabéticamente por apellido (ascendente)
        usort($medicos, function($a, $b) {
            return strcmp($a['apellido'], $b['apellido']);
        });

        // 4. Lógica de Paginación (50 por página)
        $porPagina = 50;
        $paginaActual = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $totalMedicos = count($medicos);
        $totalPaginas = ceil($totalMedicos / $porPagina); // Calcula el total de páginas
        
        // Extraemos solo los 50 médicos que corresponden a la página actual
        $offset = ($paginaActual - 1) * $porPagina;
        $medicosPaginados = array_slice($medicos, $offset, $porPagina);

        // 5. Enviamos los datos, los filtros y las variables de paginación a la vista
        View::render('Directorio/index', [
            'medicos' => $medicosPaginados,
            'filtros' => $filtros,
            'paginaActual' => $paginaActual,
            'totalPaginas' => $totalPaginas
        ]);
    }

    // Acción para ver el perfil público de un solo médico
    public function ver() {
        $id = $_GET['id'] ?? null;
        
        // Si alguien entra sin un ID válido, lo regresamos al directorio
        if (!$id) {
            header('Location: ?c=directorio&a=index');
            exit;
        }

        // Pedimos los datos de ese médico en específico
        $api = new ApiClient();
        $medico = $api->get('/medicos/' . $id);

        // Llamamos a tu otra vista
        View::render('Directorio/ver', [
            'medico' => $medico
        ]);
    }
}