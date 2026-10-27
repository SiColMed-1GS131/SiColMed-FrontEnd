# SiColMed: análisis técnico y mejoras aplicadas

## Cómo funciona

No es un proyecto React: es una aplicación PHP 8.3 con MVC parcial, vistas PHP y JavaScript nativo. Apache sirve exclusivamente `Public/` y redirige las rutas a `Public/index.php`. El `Router` busca la ruta y ejecuta el controlador. El controlador gestiona los datos y carga la vista. Para consultar o modificar registros, los repositorios llaman a `App/Services/ApiClient.php`, que se comunica con el backend FastAPI configurado mediante `API_URL`.

**Flujo de ejemplo:** `GET /registro-medico` → `Router` → `RegistroController::index()` → `RegistroMedicoRepository::listar()` → `ApiClient::getJson('/medicos')` → FastAPI → vista `Registro/index.php`.

## Cambios realizados

1. **Integración API:** implementados `getJson()` y `patchJson()` en `ApiClient`, utilizados por `RegistroMedicoRepository`, y consolidado el transporte GET/POST/PATCH en una función reutilizable. Se conserva el formato `['status', 'body']` que esperan los consumidores actuales; se añaden parámetros query, timeout y restricción de redirecciones.
2. **CSRF registro médico:** token de sesión de un solo uso tras guardado correcto, campo oculto en el formulario, comprobación de POST y exclusión del token del cuerpo remitido a la API. La autorización real continúa pendiente y NO queda resuelta por CSRF.
3. **HTML compartido:** `header.php` antes creaba un documento HTML vacío completo; `footer.php` cerraba contenedores extra. Ahora el layout tiene un único `<html>`/`<body>`.
4. **Navegación:** enlace del registro en barra lateral corregido a `/registro-medico`.
5. **Calidad:** lint de archivos PHP validado. Sin modificación del backend ni de los datos reales.

## Problemas importantes aún pendientes

- **Crítico: no existe control de acceso** en `RegistroController::verificarAcceso()`, por lo que las rutas de registro médico NO están protegidas; implementar validación de sesión/roles en el servidor coordinada con FastAPI. No basta consultar el token de JavaScript.
- **Alto: integración incompleta:** `DirectorioController` y `MovimientosController` utilizan clases no definidas o API incompatibles con el `Router` y `ApiClient` actuales, y no se registran sus rutas. Se deben adaptar por módulo, validando los endpoints reales.
- **Alto: autenticación partida:** login opera en JavaScript con JWT en Web Storage, pero el cliente PHP no transmite un Bearer token al backend. Antes de activar módulos privados, unificar autenticación con sesión segura y cookies HttpOnly o una estrategia BFF consensuada con el equipo de backend.
- **Alto: modo demo:** `USE_MOCK=1` simula guardados sin persistir registros. Nunca confundirlo con pruebas integradas ni producción.
- **Medio: archivos:** el formulario acepta foto y documentos, pero el controlador todavía solo envía `$_POST`. Integrar archivos según contrato y límites verificados del backend.
- **Medio: enlaces:** `/directorio`, `/Perfil`, `/Gestion` y otros no están registrados en el enrutador actual; validar nombres, permisos y pantallas antes de habilitarlos.
- **Medio: páginas legales y recuperación de contraseña:** hay enlaces `href="#"` sin implementación.
- **Medio: persistencia JWT:** Web Storage expone tokens a XSS. Replantear el diseño de autenticación antes de producción.
- **Medio: listado médico:** el repositorio advierte que el backend puede exigir un filtro para `GET /medicos`; confirmar contrato antes de modificarlo.

## Ejecutar localmente

**Docker:** `docker build -t sicolmed-front .` y después `docker run --rm -p 8080:80 -e API_URL=http://host.docker.internal:8000 sicolmed-front` (en una red Docker entre servicios usar el nombre interno de la API). Abrir `http://localhost:8080/`.

**PHP para pruebas de interfaz:** desde raíz del proyecto `USE_MOCK=1 php -S localhost:8080 -t Public Public/index.php` en Linux/macOS; para PowerShell configurar `$env:USE_MOCK="1"` antes del comando `php -S`. El modo mock solo cubre registro médico; login demo usa `?mock=1` en localhost y es independiente.

**Verificaciones mínimas:** ejecutar lint `find App Public -name '*.php' -exec php -l {} \;`. Probar manualmente `/login`, `/register`, `/registro-medico`, nuevo/editar y errores 404/405. Sin backend real no se puede certificar que GET/PATCH y contratos de datos funcionen de punta a punta.
