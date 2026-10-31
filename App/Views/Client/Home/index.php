<?php
/**
 * Página de inicio de SiColMed.
 * Variables que entrega HomeController: $base, $urls, $especialidades, $provincias,
 * $servicios, $estadisticas, $roles, $noticias.
 */
// Si alguien abre este archivo directo (sin pasar por el controlador), lo mandamos a la portada.
if (!isset($urls, $servicios)) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    header('Location: ' . preg_replace('#/App/Views/.*$#', '/Public/index.php', $script));
    exit;
}

$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SiColMed · Colegio Médico de Panamá</title>
    <meta name="description" content="SiColMed: directorio de médicos, perfiles profesionales y gestión contable del Colegio Médico de Panamá.">
    <link rel="icon" type="image/svg+xml" href="<?= $e($base) ?>/Assets/IMG/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $e($base) ?>/Assets/CSS/base.css">
    <link rel="stylesheet" href="<?= $e($base) ?>/Assets/CSS/Dedicated/home.css">
</head>
<body class="home">
<a class="home__saltar" href="#contenido">Saltar al contenido</a>

<!-- ================= BARRA SUPERIOR ================= -->
<header class="topbar" id="topbar">
    <div class="topbar__inner">
        <a class="marca" href="<?= $e($urls['inicio']) ?>" aria-label="SiColMed, inicio">
            <img class="marca__logo" src="<?= $e($base) ?>/Assets/IMG/LOGO-CM.png" alt="" width="40" height="40">
            <span class="marca__nombre">SICOLMED</span>
        </a>

        <button class="topbar__menu" id="btnMenu" type="button" aria-expanded="false" aria-controls="navegacion" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>

        <nav class="nav" id="navegacion" aria-label="Principal">
            <ul class="nav__lista">
                <li><a class="nav__link" href="#inicio">Inicio</a></li>
                <li><a class="nav__link" href="#servicios">Servicios</a></li>
                <li><a class="nav__link" href="#roles">¿Quién lo usa?</a></li>
                <li><a class="nav__link" href="#noticias">Novedades</a></li>
                <li><a class="nav__link" href="#contacto">Contacto</a></li>
            </ul>
            <div class="nav__acciones">
                <a class="boton boton--solido boton--pildora" href="<?= $e($urls['registro']) ?>">Regístrate</a>
                <a class="boton boton--borde boton--pildora" href="<?= $e($urls['login']) ?>">Iniciar sesión</a>
            </div>
        </nav>
    </div>
</header>

<main id="contenido">

    <!-- ================= HERO ================= -->
    <section class="hero" id="inicio" aria-labelledby="hero-titulo">
        <div class="hero__inner">
            <p class="hero__etiqueta">Colegio Médico de Panamá</p>
            <h1 class="hero__titulo" id="hero-titulo">Gestión moderna para la comunidad médica de Panamá</h1>
            <div class="hero__acciones">
                <a class="boton boton--claro" href="<?= $e($urls['directorio']) ?>">
                    Explorar el directorio <span class="icono icono--flecha_derecha" aria-hidden="true"></span>
                </a>
                <a class="boton boton--fantasma" href="<?= $e($urls['login']) ?>">Iniciar sesión</a>
            </div>
        </div>
    </section>

    <!-- ================= TARJETAS SUPERPUESTAS ================= -->
    <section class="destacados" aria-label="Accesos rápidos">
        <div class="destacados__inner">

            <article class="tarjeta tarjeta--buscar">
                <h2 class="tarjeta__titulo">Encuentra un médico</h2>
                <form class="buscar" action="<?= $e($urls['inicio']) ?>" method="get">
                    <input type="hidden" name="c" value="directorio">
                    <input type="hidden" name="a" value="index">

                    <div class="campo-home">
                        <label for="q">Apellido</label>
                        <input type="text" id="q" name="q" placeholder="Ej. Pérez" autocomplete="off">
                    </div>
                    <div class="campo-home">
                        <label for="especialidad">Especialidad</label>
                        <select id="especialidad" name="especialidad">
                            <option value="">Todas</option>
                            <?php foreach ($especialidades as $valor => $nombre): ?>
                                <option value="<?= $e($valor) ?>"><?= $e($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo-home">
                        <label for="provincia">Provincia</label>
                        <select id="provincia" name="provincia">
                            <option value="">Todas</option>
                            <?php foreach ($provincias as $valor => $nombre): ?>
                                <option value="<?= $e($valor) ?>"><?= $e($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="boton boton--solido buscar__boton" type="submit">
                        <span class="icono icono--lupa" aria-hidden="true"></span> Buscar
                    </button>
                </form>
            </article>

            <article class="tarjeta tarjeta--azul">
                <h2 class="tarjeta__titulo">¿Qué puedes hacer?</h2>
                <ul class="accesos">
                    <li>
                        <a class="acceso" href="<?= $e($urls['directorio']) ?>">
                            <span class="acceso__texto">
                                <strong>Consultar el directorio</strong>
                                <small>Abierto para todos, sin iniciar sesión</small>
                            </span>
                            <span class="acceso__flecha" aria-hidden="true"><span class="icono icono--flecha_derecha"></span></span>
                        </a>
                    </li>
                    <li>
                        <a class="acceso" href="<?= $e($urls['login']) ?>">
                            <span class="acceso__texto">
                                <strong>Acceder a mi perfil</strong>
                                <small>Para médicos con cuenta</small>
                            </span>
                            <span class="acceso__flecha" aria-hidden="true"><span class="icono icono--flecha_derecha"></span></span>
                        </a>
                    </li>
                    <li>
                        <a class="acceso" href="<?= $e($urls['registro']) ?>">
                            <span class="acceso__texto">
                                <strong>Crear mi cuenta</strong>
                                <small>Con tu número de idoneidad profesional</small>
                            </span>
                            <span class="acceso__flecha" aria-hidden="true"><span class="icono icono--flecha_derecha"></span></span>
                        </a>
                    </li>
                </ul>
            </article>

        </div>
    </section>

    <!-- ================= SERVICIOS ================= -->
    <section class="seccion" id="servicios" aria-labelledby="servicios-titulo">
        <div class="contenedor">
            <header class="seccion__cabecera">
                <h2 class="seccion__titulo" id="servicios-titulo">Todo el Colegio en una plataforma</h2>
                <p class="seccion__texto">Seis herramientas pensadas para médicos y para quienes administran el Colegio.</p>
            </header>

            <ul class="servicios">
                <?php foreach ($servicios as $s): ?>
                <li class="servicio">
                    <a class="servicio__enlace" href="<?= $e($s['url']) ?>">
                        <span class="servicio__icono"><span class="icono icono--<?= $e($s['icono']) ?>" aria-hidden="true"></span></span>
                        <h3 class="servicio__titulo"><?= $e($s['titulo']) ?></h3>
                        <p class="servicio__texto"><?= $e($s['texto']) ?></p>
                        <span class="servicio__mas">Ver más <span class="icono icono--flecha_derecha" aria-hidden="true"></span></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- ================= ROLES ================= -->
    <section class="seccion seccion--crema" id="roles" aria-labelledby="roles-titulo">
        <div class="contenedor">
            <header class="seccion__cabecera">
                <h2 class="seccion__titulo" id="roles-titulo">Una plataforma para cada rol</h2>
                <p class="seccion__texto">Cada persona ve únicamente lo que necesita para hacer su trabajo.</p>
            </header>

            <ul class="roles">
                <?php foreach ($roles as $r): ?>
                <li class="rol">
                    <h3 class="rol__titulo"><?= $e($r['titulo']) ?></h3>
                    <ul class="rol__lista">
                        <?php foreach ($r['items'] as $item): ?>
                            <li><span class="icono icono--gancho" aria-hidden="true"></span><?= $e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- ================= NOVEDADES ================= -->
    <section class="seccion" id="noticias" aria-labelledby="noticias-titulo">
        <div class="contenedor">
            <header class="seccion__cabecera">
                <h2 class="seccion__titulo" id="noticias-titulo">Novedades</h2>
                <p class="seccion__texto">Comunicados y avisos para la comunidad del Colegio.</p>
            </header>

            <div class="noticias">
                <?php foreach ($noticias as $n):
                    $f = strtotime($n['fecha']);
                ?>
                <article class="noticia">
                    <time class="noticia__fecha" datetime="<?= $e($n['fecha']) ?>">
                        <strong><?= date('d', $f) ?></strong>
                        <span><?= $e($meses[(int) date('n', $f)]) ?> <?= date('Y', $f) ?></span>
                    </time>
                    <div class="noticia__cuerpo">
                        <span class="noticia__categoria"><?= $e($n['categoria']) ?></span>
                        <h3 class="noticia__titulo"><?= $e($n['titulo']) ?></h3>
                        <p class="noticia__texto"><?= $e($n['texto']) ?></p>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ================= LLAMADO FINAL ================= -->
    <section class="llamado" aria-labelledby="llamado-titulo">
        <div class="llamado__inner">
            <div>
                <h2 class="llamado__titulo" id="llamado-titulo">¿Eres médico colegiado?</h2>
                <p class="llamado__texto">Crea tu cuenta con tu número de idoneidad y mantén tu información al día.</p>
            </div>
            <div class="llamado__acciones">
                <a class="boton boton--claro" href="<?= $e($urls['registro']) ?>">Crear mi cuenta</a>
                <a class="boton boton--fantasma" href="<?= $e($urls['login']) ?>">Ya tengo cuenta</a>
            </div>
        </div>
    </section>

</main>

<!-- ================= PIE ================= -->
<footer class="pie" id="contacto">
    <div class="pie__inner">
        <div class="pie__marca">
            <a class="marca marca--clara" href="<?= $e($urls['inicio']) ?>">
                <img class="marca__logo" src="<?= $e($base) ?>/Assets/IMG/LOGO-CM.png" alt="" width="40" height="40">
                <span class="marca__nombre">SICOLMED</span>
            </a>
            <p>Sistema de Información para la Gestión del Colegio Médico de Panamá.</p>
        </div>

        <nav class="pie__col" aria-label="Enlaces del pie">
            <h3>Plataforma</h3>
            <ul>
                <li><a href="<?= $e($urls['directorio']) ?>">Directorio médico</a></li>
                <li><a href="<?= $e($urls['login']) ?>">Iniciar sesión</a></li>
                <li><a href="<?= $e($urls['registro']) ?>">Regístrate</a></li>
            </ul>
        </nav>

        <div class="pie__col">
            <h3>Contacto</h3>
            <!-- Reemplazar por los datos reales del Colegio -->
            <ul class="pie__contacto">
                <li><span class="icono icono--ubicacion" aria-hidden="true"></span> Ciudad de Panamá, Panamá</li>
                <li><span class="icono icono--telefono" aria-hidden="true"></span> (+507) 000-0000</li>
                <li><span class="icono icono--mail" aria-hidden="true"></span> correo@ejemplo.com</li>
            </ul>
        </div>
    </div>
    <div class="pie__base">
        <p>© <?= date('Y') ?> SiColMed · Colegio Médico de Panamá. Todos los derechos reservados.</p>
        <p><a href="#">Política de privacidad</a> · <a href="#">Términos de servicio</a></p>
    </div>
</footer>

<script src="<?= $e($base) ?>/Assets/JavaScript/Dedicated/home.js" defer></script>
</body>
</html>