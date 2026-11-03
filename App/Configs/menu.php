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
 *   children Submenú (un solo nivel). El grupo se muestra si algún hijo es visible.
 *
 * Quién ve cada opción NO se declara aquí: Partials/sidebar.php la muestra solo si
 * Auth::puedeEntrar() permite el controlador (?c=) de su url, es decir, con los mismos
 * PERMISOS de App/Middleware/Auth.php que autorizan la página. Para cambiar quién ve
 * una opción, cambia sus permisos en Auth.php.
 */

$entrada = '/index.php';   // Docker: el webroot es Public/

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
        'label' => 'Mi perfil',
        'icon'  => 'perfil',
        'url'   => "$entrada?c=perfil&a=index",
        'match' => ['perfil'],
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
            ],
            [
                'label' => 'Movimientos',
                'icon'  => 'movimientos',
                'url'   => "$entrada?c=movimientos&a=index",
                'match' => ['movimientos'],
            ],
            [
                'label' => 'Reportes',
                'icon'  => 'reportes',
                'url'   => "$entrada?c=reportes&a=balance",
                'match' => ['reportes'],
            ],
        ],
    ],
];