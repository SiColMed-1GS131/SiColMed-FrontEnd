<?php
/** Error 500: error interno (P4 · Miguel). */
http_response_code(500);
?>
<section class="error-page" aria-labelledby="error-titulo">
    <p class="error-page__codigo">500</p>
    <h1 class="error-page__titulo" id="error-titulo">Algo salió mal</h1>
    <p class="error-page__texto">Ocurrió un error inesperado. Intenta de nuevo en unos minutos.</p>
    <a class="btn btn--primary" href="index.php?c=directorio&amp;a=index">Volver al inicio</a>
</section>
