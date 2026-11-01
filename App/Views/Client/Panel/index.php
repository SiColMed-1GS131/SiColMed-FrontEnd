<?php
/**
 * Inicio de quien no es administrador (sin gestión financiera).
 * Lo arma PanelController. Variables: $estadisticas, $urls.
 */
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="panel">

    <header class="panel__cabecera">
        <div>
            <h1 class="panel__titulo">Bienvenido</h1>
            <p class="panel__subtitulo">Tu espacio en SiColMed, Colegio Médico de Panamá.</p>
        </div>
        <a class="panel__boton" href="<?= $e($urls['perfil']) ?>">Ver mi perfil</a>
    </header>

    <section class="panel__stats panel__stats--dos" aria-label="Resumen">
        <?php foreach ($estadisticas as $est): ?>
            <article class="panel__tarjeta panel__stat">
                <h2 class="panel__stat-etiqueta"><?= $e($est['etiqueta']) ?></h2>
                <p class="panel__stat-valor"><?= $e($est['valor']) ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <h2 class="panel__seccion">Accesos rápidos</h2>
    <section class="panel__accesos">
        <a class="panel__tarjeta panel__acceso" href="<?= $e($urls['perfil']) ?>">
            <span class="panel__icono panel__icono--usuario" aria-hidden="true"></span>
            <span><strong>Mi perfil</strong><small>Consulta tus datos registrados</small></span>
        </a>
        <a class="panel__tarjeta panel__acceso" href="<?= $e($urls['editar']) ?>">
            <span class="panel__icono panel__icono--editar" aria-hidden="true"></span>
            <span><strong>Editar mi perfil</strong><small>Actualiza lo que se te permite</small></span>
        </a>
        <a class="panel__tarjeta panel__acceso" href="<?= $e($urls['directorio']) ?>">
            <span class="panel__icono panel__icono--lupa" aria-hidden="true"></span>
            <span><strong>Directorio médico</strong><small>Buscar y consultar colegas</small></span>
        </a>
    </section>

</div>
