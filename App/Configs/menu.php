<?php
/**
 * App/Configs/menu.php · definición del menú lateral.
 *
 * Para agregar o quitar una opción se edita SOLO este archivo.
 * La vista (Partials/sidebar.php) únicamente dibuja lo que aquí se declara.
 *
 * Campos de cada opción:
 *   label    Texto visible.
 *   icon     Sufijo de la clase CSS del icono (.sidebar__icon--{icon}), ver sidebar.css.
 *   url      Enlace (se omite en un grupo: toma el de su primer hijo visible).
 *   match    Controladores (?c=) que marcan la opción como activa.
 *   uri      Fragmentos de la URL que también la marcan como activa.
 *   roles    Roles de Auth que la ven. Sin este campo, la ven todos.
 *   children Submenú (un solo nivel). El grupo se muestra si algún hijo es visible.
 */

$entrada = '/SiColMed-FrontEnd/Public/index.php';

return [
    [
        'label' => 'Inicio',
        'icon'  => 'inicio',
        'url'   => "$entrada?c=panel&a=index",
        'match' => ['panel'],
    ],
    [
        'label' => 'Directorio médico',
        'icon'  => 'directorio',
        'url'   => "$entrada?c=directorio&a=index",
        'match' => ['directorio'],
    ],
    [
        'label' => 'Registro médico',
        'icon'  => 'registro',
        'url'   => "$entrada?c=registro&a=index",
        'match' => ['registro'],
    ],
    [
        'label' => 'Perfil médico',
        'icon'  => 'perfil',
        'url'   => '/SiColMed-FrontEnd/App/Views/Client/Perfil/index.php',
        'match' => ['perfil'],
        'uri'   => ['/Perfil/'],
    ],
    [
        'label'    => 'Gestión financiera',
        'icon'     => 'gestion',
        'children' => [
            [
                'label' => 'Cuentas',
                'icon'  => 'cuentas',
                'url'   => "$entrada?c=cuentas&a=index",
                'match' => ['cuentas'],
                'roles' => [Auth::TESORERO, Auth::ADMIN],
            ],
            [
                'label' => 'Movimientos',
                'icon'  => 'movimientos',
                'url'   => "$entrada?c=movimientos&a=index",
                'match' => ['movimientos'],
                'roles' => [Auth::TESORERO, Auth::ADMIN],
            ],
            [
                'label' => 'Reportes',
                'icon'  => 'reportes',
                'url'   => "$entrada?c=reportes&a=balance",
                'match' => ['reportes'],
                'roles' => [Auth::ADMIN_ASOCIACION, Auth::ADMIN],
            ],
        ],
    ],
];