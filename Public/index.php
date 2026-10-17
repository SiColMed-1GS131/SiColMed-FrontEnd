<?php
$api = getenv('API_URL') ?: 'http://api:8000';
$respuesta = @file_get_contents("$api/health/db");
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Certificaciones Médicas</title></head>
<body>
  <h1>Certificaciones Médicas - Panamá</h1>
  <pre><?= htmlspecialchars($respuesta ?: 'No se pudo conectar con la API') ?></pre>
</body>
</html>