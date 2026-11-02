<?php
// Configuración de cada computadora. Este archivo NO se sube a Git (ver .gitignore);
// copia local.php.example como local.php y ajusta los valores.
// Las variables de entorno (Docker) tienen prioridad: solo se completan las que faltan.
if (getenv('USE_MOCK') === false) {
    putenv('USE_MOCK=0');   // el modo de prueba solo se activa con USE_MOCK=1
}
if (getenv('API_URL') === false) {
    putenv('API_URL=http://localhost:8000');
}
