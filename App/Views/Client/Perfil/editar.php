<?php
$currentPage = 'perfil';
require_once __DIR__ . '/../../Partials/header.php';
?>

<link rel="stylesheet" href="/SiColMed-FrontEnd/Public/Assets/CSS/Dedicated/perfil.css">
<div class="app-layout">

    <?php
    require_once __DIR__ . '/../../Partials/sidebar.php';
    ?>

    <main class="main-content">

        <div class="perfil-medico">
            <header class="perfil__cabecera">
                <h1>Mi Perfil Médico</h1>
                <p>Consulta y actualiza tu información profesional.</p>
            </header>

        <!-- Formulario Base del usuario general -->
        <form action="#" method="post" class="formulario" enctype="multipart/form-data">

                <fieldset class="formulario__seccion">
                    <legend>Información Personal</legend>
                    <div class="formulario__grid">
                        <div class="campo">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" value="Juan" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="segundo_nombre">Segundo Nombre</label>
                            <input type="text" id="segundo_nombre" name="segundo_nombre" value="Carlos" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="apellido_paterno">Apellido Paterno</label>
                            <input type="text" id="apellido_paterno" name="apellido" value="Pérez" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="apellido_materno">Apellido Materno</label>
                            <input type="text" id="apellido_materno" name="segundo_apellido" value="Gómez" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="cedula">Cédula</label>
                            <input type="text" id="cedula" name="cedula" value="8-000-0000" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="registro_medico">Número de Registro Médico</label>
                            <input type="text" id="registro_medico" name="registro_medico" value="RM-12345" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="1980-05-15" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label for="edad">Edad</label>
                            <input type="number" id="edad" name="edad" value="46" readonly class="input-readonly">
                        </div>

                        <!-- Campos editables de contacto -->
                        <div class="campo campo--full">
                            <label for="foto">Foto de Perfil</label>
                            <input type="file" id="foto" name="foto" accept="image/*">
                        </div>
                        <div class="campo campo--full">
                            <label>Dirección Residencial</label>
                            <div class="direccion-grid">
                                <input type="text" name="dir_provincia" placeholder="Provincia" value="Panamá">
                                <input type="text" name="dir_distrito" placeholder="Distrito" value="Panamá">
                                <input type="text" name="dir_corregimiento" placeholder="Corregimiento" value="Bella Vista">
                                <input type="text" name="dir_calle" placeholder="Calle" value="Calle 50">
                                <input type="text" name="dir_edificio" placeholder="Edificio" value="Torre Global">
                                <input type="text" name="dir_numero" placeholder="Número (Apto/Casa)" value="15A">
                            </div>
                        </div>
                        <div class="campo">
                            <label for="tel_residencial">Teléfono residencial</label>
                            <input type="tel" id="tel_residencial" name="tel_residencial" value="200-0000">
                        </div>
                        <div class="campo">
                            <label for="celular">Celular</label>
                            <input type="tel" id="celular" name="celular" value="6000-0000">
                        </div>
                        <div class="campo">
                            <label for="email_personal">Email personal</label>
                            <input type="email" id="email_personal" name="email_personal" value="juan.perez@email.com">
                        </div>
                        <div class="campo">
                            <label for="apartado_postal">Apartado postal</label>
                            <input type="text" id="apartado_postal" name="apartado_postal" value="0819-00000">
                        </div>
                    </div>
                </fieldset>


                <fieldset class="formulario__seccion">
                    <legend>Educación</legend>

                    <div class="formulario__grid">
                        <div class="campo">
                            <label>Título Básico</label>
                            <input type="text" value="Doctor en Medicina" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Institución</label>
                            <input type="text" value="Universidad de Panamá" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Lugar</label>
                            <input type="text" value="Panamá" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Fecha</label>
                            <input type="date" value="2005-12-15" readonly class="input-readonly">
                        </div>
                    </div>
                    <p class="nota"><em>Nota: Para agregar o modificar especialidades y sub-especialidades, contacte al CMP.</em></p>
                </fieldset>


                <fieldset class="formulario__seccion">
                    <legend>Información Laboral</legend>
                    <div class="formulario__grid">
                        <div class="campo campo--full">
                            <label for="empresa">Empresa o Institución</label>
                            <input type="text" id="empresa" name="empresa" value="Hospital Santo Tomás">
                        </div>
                        <div class="campo">
                            <label for="cargo">Cargo</label>
                            <input type="text" id="cargo" name="cargo" value="Médico Especialista">
                        </div>
                        <div class="campo">
                            <label for="tel_trabajo">Teléfono de trabajo</label>
                            <input type="tel" id="tel_trabajo" name="tel_trabajo" value="507-5000">
                        </div>
                        <div class="campo">
                            <label for="email_laboral">Email laboral</label>
                            <input type="email" id="email_laboral" name="email_laboral" value="jperez@hst.gob.pa">
                        </div>
                        <div class="campo campo--full">
                            <label>Dirección Laboral</label>
                            <textarea name="dir_laboral" rows="2">Calle 34 Este, Avenida Balboa, Ciudad de Panamá</textarea>
                        </div>
                    </div>
                </fieldset>


                <fieldset class="formulario__seccion">
                    <legend>Organizaciones a las que pertenece</legend>
                    <div class="formulario__grid">
                        <div class="campo">
                            <label for="profesor">Profesor Universitario</label>
                            <input type="text" id="profesor" name="profesor" value="Universidad Tecnológica de Panamá">
                        </div>
                        <div class="campo">
                            <label for="gremios">Gremios</label>
                            <input type="text" id="gremios" name="gremios">
                        </div>
                        <div class="campo">
                            <label for="sociedades">Sociedades</label>
                            <input type="text" id="sociedades" name="sociedades" value="Sociedad Panameña de Cardiología">
                        </div>
                        <div class="campo">
                            <label for="clubes">Clubes Cívicos</label>
                            <input type="text" id="clubes" name="clubes">
                        </div>
                        <div class="campo campo--full">
                            <label for="otras_org">Otros...</label>
                            <input type="text" id="otras_org" name="otras_org">
                        </div>
                    </div>
                </fieldset>


                <fieldset class="formulario__seccion">
                    <legend>Información de Certificación (Consulta)</legend>
                    <div class="formulario__grid">
                        <div class="campo">
                            <label>Estado</label>
                            <span class="badge badge--vigente">Vigente</span>
                        </div>
                        <div class="campo">
                            <label>Fecha en que se obtuvo</label>
                            <input type="date" value="2020-01-10" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Puntaje</label>
                            <input type="number" value="95" readonly class="input-readonly">
                        </div>
                        <div class="campo">
                            <label>Fecha de expiración</label>
                            <input type="date" value="2025-01-10" readonly class="input-readonly">
                        </div>
                    </div>
                </fieldset>

                <div class="formulario__acciones">
                    <button type="submit" class="btn btn--primario">Guardar mis cambios</button>
                </div>
            </form>
        </div>

    </main>

</div>

<?php
require_once __DIR__ . '/../../Partials/footer.php';
?>