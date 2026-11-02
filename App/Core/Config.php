<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Configuración leída del entorno (variables de entorno o App/Configs/local.php).
 * Un solo lugar para la URL de la API: nada más en el código la escribe a mano.
 */
final class Config
{
    /** URL de la API que usa PHP (en Docker: http://api:8000). */
    public static function apiUrl(): string
    {
        $url = getenv('API_URL');
        return rtrim(is_string($url) && $url !== '' ? $url : 'http://api:8000', '/');
    }
}
