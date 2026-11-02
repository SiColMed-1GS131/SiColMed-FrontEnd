<?php
declare(strict_types=1);

namespace App\Controllers;

class PerfilController {

    /**
     * Muestra la pantalla principal del perfil (Solo lectura)
     * Ruta esperada: /Perfil
     */
    public function index() {
        require_once __DIR__ . '/../Views/Client/Perfil/index.php';
    }

    /**
     * Muestra el formulario para modificar los datos
     * Ruta esperada: /Perfil/editar
     */
    public function editar() {
        require_once __DIR__ . '/../Views/Client/Perfil/editar.php';
    }

    /**
     * Recibe los datos del formulario cuando el usuario hace clic en "Guardar"
     * Ruta esperada: POST a /Perfil/actualizar
     */
    public function actualizar() {
        header("Location: /index.php?c=perfil&a=index");
        exit();
    }
}