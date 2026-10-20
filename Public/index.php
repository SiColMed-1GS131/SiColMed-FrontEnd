<?php
$api = getenv('API_URL') ?: 'http://api:8000';
$respuesta = @file_get_contents("$api/health/db");

header('Location: authPages/login.html');
exit;
?>
