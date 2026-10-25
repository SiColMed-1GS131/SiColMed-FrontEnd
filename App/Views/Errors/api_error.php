<?php
/**
 * Mensaje cuando la API no responde (P4 · Miguel).
 * Variable opcional: $volver (URL a la que regresar).
 */
$volver = $volver ?? 'index.php?c=directorio&a=index';
?>
<section class="error-page error-page--api" role="alert" aria-labelledby="error-titulo">
    <h1 class="error-page__titulo" id="error-titulo">No pudimos obtener los datos</h1>
    <p class="error-page__texto">El servicio de información no está respondiendo en este momento. Intenta de nuevo en unos minutos.</p>
    <a class="btn btn--primary" href="<?= e($volver) ?>">Reintentar</a>
</section>
