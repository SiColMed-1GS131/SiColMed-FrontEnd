<?php
/** Error 404: página no encontrada (P4 · Miguel). */
http_response_code(404);
?>
<section class="error-page" aria-labelledby="error-titulo">
    <p class="error-page__codigo">404</p>
    <h1 class="error-page__titulo" id="error-titulo">Página no encontrada</h1>
    <p class="error-page__texto">La página que buscas no existe o fue movida.</p>
    <a class="btn btn--primary" href="index.php?c=directorio&amp;a=index">Ir al directorio médico</a>
</section>
