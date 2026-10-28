<?php
$currentPage = 'perfil';
$extraCss = 'perfil.css';
require_once __DIR__ . '/../../Partials/header.php';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/../../Partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="perfil-medico">
            <header class="perfil__cabecera" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Mi Perfil Médico</h1>
                    <p>Vista general de tus datos registrados.</p>
                </div>
                <!-- Botón que lleva a la pantalla de edición -->
                <a href="editar.php" class="btn btn--primario">Editar Perfil</a>
            </header>

            <!-- Datos en modo lectura (Sin inputs) -->
            <div class="formulario__seccion">
                <legend>Información Personal</legend>
                <div class="formulario__grid">
                    <div class="campo">
                        <label>Nombre Completo</label>
                        <p>Juan Carlos Pérez Gómez</p>
                    </div>
                    <div class="campo">
                        <label>Cédula</label>
                        <p>8-000-0000</p>
                    </div>
                    <div class="campo">
                        <label>Número de Registro Médico</label>
                        <p>RM-12345</p>
                    </div>
                    <div class="campo">
                        <label>Fecha de Nacimiento</label>
                        <p>15/05/1980 (46 años)</p>
                    </div>
                    <div class="campo campo--full">
                        <label>Dirección Residencial</label>
                        <p>Calle 50, Torre Global, 15A. Bella Vista, Panamá, Panamá.</p>
                    </div>
                    <div class="campo">
                        <label>Contacto</label>
                        <p>Cel: 6000-0000 | Tel: 200-0000</p>
                    </div>
                    <div class="campo">
                        <label>Email personal</label>
                        <p>juan.perez@email.com</p>
                    </div>
                </div>
            </div>


        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../Partials/footer.php'; ?>