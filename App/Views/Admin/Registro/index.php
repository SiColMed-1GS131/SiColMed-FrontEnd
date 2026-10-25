<?php
// 1. Configuramos las variables para tu header
$currentPage = 'registro';
$extraCss = 'registro.css';

// 2. Cargamos el header unificado
require_once __DIR__ . '/../../Partials/header.php';
?>

<div class="app-layout">
    
    <!-- 3. Cargamos el sidebar -->
    <?php require_once __DIR__ . '/../../Partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="registro-medico" style="padding: 30px;">
            
            <header class="perfil__cabecera" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h1>Registro Médico</h1>
                    <p>Administración y listado de médicos en el sistema.</p>
                </div>
                <a href="/SiColMed-FrontEnd/registro-medico/nuevo" class="btn btn--primario">Nuevo médico</a>
            </header>

            <?php if (!empty($mensaje)): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <div class="formulario__seccion" style="background-color: white; border: 2px solid var(--rock-bluelight); border-radius: 12px; padding: 25px;">
                <p style="color: var(--venice-bluedark); font-weight: bold; margin-bottom: 15px;">
                    <?= count($medicos ?? []) ?> médico(s) registrado(s)
                </p>

                <?php if (empty($medicos)): ?>
                    <p style="color: #666;">Todavía no hay médicos registrados. Usa el botón «Nuevo médico» para agregar el primero.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($medicos as $medico): ?>
                            <li>
                                <?= htmlspecialchars($medico['nombre'] . ' ' . $medico['apellido']) ?> 
                                - <a href="/SiColMed-FrontEnd/registro-medico/editar?id=<?= $medico['id'] ?>">Editar</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php 
// 4. Cargamos el footer
require_once __DIR__ . '/../../Partials/footer.php'; 
?>