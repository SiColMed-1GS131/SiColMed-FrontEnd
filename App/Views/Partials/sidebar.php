<?php
/**
 * App/Views/Partials/sidebar.php · menú lateral.
 *
 * Solo dibuja: el contenido del menú está en App/Configs/menu.php
 * y los estilos en Public/Assets/CSS/sidebar.css.
 */
require_once __DIR__ . '/../../Middleware/Auth.php';

// ---------- Contexto de la petición ----------
$sbControlador = strtolower($_GET['c'] ?? '');
$sbUri         = $_SERVER['REQUEST_URI'] ?? '';

// ---------- Helpers locales (prefijo sb para no chocar con variables de la vista) ----------

/** ¿El rol actual puede ver esta opción? (sin 'roles' = visible para todos) */
$sbVisible = static function (array $op): bool {
    return empty($op['roles']) || Auth::es(...$op['roles']);
};

/** ¿Esta opción corresponde a la página actual? */
$sbActivo = static function (array $op) use ($sbControlador, $sbUri): bool {
    if ($sbControlador !== '' && in_array($sbControlador, $op['match'] ?? [], true)) {
        return true;
    }
    // Directorio también se abre como ?a=... sin controlador
    if ($sbControlador === '' && isset($_GET['a']) && in_array('directorio', $op['match'] ?? [], true)) {
        return true;
    }
    foreach ($op['uri'] ?? [] as $fragmento) {
        if (strpos($sbUri, $fragmento) !== false) {
            return true;
        }
    }
    return false;
};

// ---------- Armado del menú: solo lo que el rol puede ver, con su estado ----------
$sbItems = [];
foreach (require __DIR__ . '/../../Configs/menu.php' as $op) {
    if (!empty($op['children'])) {
        $hijos = [];
        foreach ($op['children'] as $hijo) {
            if ($sbVisible($hijo)) {
                $hijo['activo'] = $sbActivo($hijo);
                $hijos[] = $hijo;
            }
        }
        if (!$hijos) {
            continue;                                   // el rol no ve ningún hijo: no se muestra el grupo
        }
        $op['children'] = $hijos;
        $op['url']      = $op['url'] ?? $hijos[0]['url'];
        $op['activo']   = in_array(true, array_column($hijos, 'activo'), true);
    } elseif ($sbVisible($op)) {
        $op['activo'] = $sbActivo($op);
    } else {
        continue;
    }
    $sbItems[] = $op;
}
?>
<aside class="sidebar" aria-label="Menú principal">

    <div class="sidebar__brand">
        <img class="sidebar__logo"
             src="/SiColMed-FrontEnd/Public/Assets/IMG/LOGO-CM.png"
             alt="SiColMed" draggable="false">
        <span class="sidebar__brand-name">SiColMed</span>
    </div>

    <nav class="sidebar__nav">
        <ul class="sidebar__list">
            <?php foreach ($sbItems as $op):
                $tieneHijos = !empty($op['children']); ?>
            <li class="sidebar__item<?= $tieneHijos ? ' sidebar__item--group' : '' ?><?= ($tieneHijos && $op['activo']) ? ' is-open' : '' ?>">

                <a class="sidebar__link<?= $op['activo'] ? ' is-active' : '' ?>"
                   href="<?= htmlspecialchars($op['url'], ENT_QUOTES, 'UTF-8') ?>"
                   <?= $op['activo'] ? 'aria-current="page"' : '' ?>>
                    <span class="sidebar__icon sidebar__icon--<?= $op['icon'] ?>" aria-hidden="true"></span>
                    <span class="sidebar__text"><?= htmlspecialchars($op['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($tieneHijos): ?>
                        <span class="sidebar__chevron" aria-hidden="true"></span>
                    <?php endif; ?>
                </a>

                <?php if ($tieneHijos): ?>
                <ul class="sidebar__sub">
                    <?php foreach ($op['children'] as $hijo): ?>
                    <li>
                        <a class="sidebar__link<?= $hijo['activo'] ? ' is-active' : '' ?>"
                           href="<?= htmlspecialchars($hijo['url'], ENT_QUOTES, 'UTF-8') ?>"
                           <?= $hijo['activo'] ? 'aria-current="page"' : '' ?>>
                            <span class="sidebar__icon sidebar__icon--<?= $hijo['icon'] ?>" aria-hidden="true"></span>
                            <span class="sidebar__text"><?= htmlspecialchars($hijo['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
