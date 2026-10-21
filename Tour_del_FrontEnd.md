# Tour del FrontEnd — SiColMed

> Guía de orientación para comprender la estructura del proyecto, ubicar correctamente el código y trabajar de forma consistente con el equipo.

## 1. ¿Qué muestra la estructura?

```text
SiColMed-FrontEnd/
├── App/
│   ├── Configs/
│   ├── Controllers/
│   ├── Core/
│   ├── Helpers/
│   ├── Middleware/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   │   └── Crypto/
│   ├── Views/
│   │   ├── Access/
│   │   ├── Admin/
│   │   │   ├── Home/
│   │   │   ├── Partials/
│   │   │   └── Perfil/
│   │   ├── Auth/
│   │   ├── Client/
│   │   │   ├── Home/
│   │   │   ├── Partials/
│   │   │   └── Perfil/
│   │   ├── Partials/
│   │   └── Portal/
└── Public/
│   ├── Assets/
│   ├── CSS/
│   │   └── Dedicated/
│   ├── IMG/
│   ├── JavaScript/
│   │   ├── Dedicated/
│   │   └── Functions/
│   ├── MP3/
│   ├── Uploads/
└── └── └── Perfil/

```

**Importante:** En el Directorio App es donde se colocara el el código de riesgo "Alto", "Medio" o "Importante" mientras que en el Directorio Public se colocara el código de riesgo "Bajo" o nulo

## 2. Mapa rápido: ¿dónde va cada cosa?

### App/

| Carpeta                  | Responsabilidad recomendada                                                                                                                                            | Ejemplos de contenido                                                                                             |
| ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| `App/Configs/`           | Centralizar configuraciones de la aplicación.                                                                                                                          | URL base de la API, rutas configurables, opciones de entorno, constantes de configuración.                        |
| `App/Controllers/`       | Coordinar acciones provenientes de las vistas.                                                                                                                         | Controladores de formularios, acciones de pantalla, navegación coordinada.                                        |
| `App/Core/`              | Reunir piezas fundamentales y reutilizables del funcionamiento de la aplicación.                                                                                       | Inicialización, contratos compartidos, gestión central de errores, clientes base.                                 |
| `App/Helpers/`           | Funciones pequeñas, puras y reutilizables.                                                                                                                             | Formatear fechas, normalizar texto, calcular etiquetas visuales.                                                  |
| `App/Middleware/`        | Ejecutar lógica transversal alrededor de peticiones, rutas o acciones, **si el framework lo permite**.                                                                 | Verificaciones de sesión, interceptores, manejo de encabezados.                                                   |
| `App/Models/`            | Describir las estructuras y reglas de los datos del frontend.                                                                                                          | Tipos, interfaces, DTO, validadores y modelos de formulario.                                                      |
| `App/Repositories/`      | Encapsular el acceso y la consulta de datos.                                                                                                                           | Consultas a endpoints, métodos para obtener, guardar o actualizar registros.                                      |
| `App/Services/`          | Implementar operaciones y lógica de negocio o de aplicación.                                                                                                           | Validación de flujos, coordinación de varias consultas, reglas de permisos visibles en UI.                        |
| `Services/Crypto/`       | Agrupar los servicios relacionados con operaciones criptográficas, como cifrado, descifrado y manejo seguro de información sensible.                                   | Clases de cifrado, funciones de descifrado, generación de valores aleatorios seguros y utilidades criptográficas. |
| `App/Views/`             | Presentar la interfaz y manejar la interacción visual.                                                                                                                 | Páginas, componentes, formularios, tablas, estados de carga y mensajes.                                           |
| `Views/Access/`          | Organizar las interfaces relacionadas con el control de acceso y la presentación de páginas de autorización o restricción de funcionalidades.                          | Páginas de acceso denegado, permisos insuficientes y restricciones de acceso.                                     |
| `Views/Admin/`           | Agrupar las interfaces destinadas a los usuarios con rol administrativo, manteniendo separadas sus funcionalidades de las correspondientes a otros roles.              | Panel administrativo, gestión de usuarios y módulos de administración.                                            |
| `Views/Admin/Home/`      | Contener las vistas correspondientes a la pantalla principal del administrador, donde se presenta información general y accesos a las funcionalidades administrativas. | Dashboard administrativo, tarjetas informativas, indicadores y accesos rápidos.                                   |
| `Views/Admin/Partials/`  | Almacenar componentes visuales reutilizables dentro de las interfaces administrativas para mantener una estructura consistente y evitar duplicación de código.         | Menús laterales, barras de navegación, encabezados, pies de página y ventanas modales.                            |
| `Views/Admin/Users/`     | Organizar las interfaces utilizadas por los administradores para visualizar y gestionar los usuarios registrados en el sistema.                                        | Listado de usuarios, formularios de registro, edición de usuarios y detalles de cuentas.                          |
| `Views/Auth/`            | Centralizar las interfaces relacionadas con los procesos de autenticación y recuperación del acceso al sistema.                                                        | Inicio de sesión, recuperación de contraseña, restablecimiento de credenciales y formularios de autenticación.    |
| `Views/Client/`          | Agrupar las interfaces destinadas a los usuarios con rol de cliente, separando sus funcionalidades y componentes de los módulos administrativos.                       | Panel del cliente, información de cuenta, funcionalidades disponibles y consultas personales.                     |
| `Views/Client/Home/`     | Contener las vistas correspondientes a la pantalla principal del cliente, proporcionando un punto de acceso a sus funcionalidades e información relevante.             | Dashboard del cliente, resumen de información, tarjetas y accesos rápidos.                                        |
| `Views/Client/Partials/` | Almacenar componentes visuales reutilizables exclusivamente en las interfaces del cliente, facilitando el mantenimiento de una presentación uniforme.                  | Menú lateral del cliente, encabezados, barras de navegación, pies de página y componentes compartidos.            |
| `Views/Client/Perfil/`   | Organizar las interfaces que permiten al cliente consultar y administrar la información de su perfil personal.                                                         | Visualización de datos personales, edición de perfil, cambio de contraseña y fotografía de usuario.               |
| `Views/Partials/`        | Centralizar componentes visuales reutilizables entre diferentes módulos y roles del sistema, evitando duplicar estructuras comunes en las vistas.                      | Componentes globales, mensajes de alerta, ventanas modales, encabezados y elementos compartidos.                  |
| `Views/Portal/`          | Organizar las vistas correspondientes al portal general del sistema, incluyendo páginas públicas o interfaces que no pertenezcan exclusivamente a un rol específico.   | Página de bienvenida, información institucional, páginas informativas y contenido público.                        |
|                          |                                                                                                                                                                        |                                                                                                                   |

### Public/

| Carpeta                    | Responsabilidad recomendada                                                                                                                           | Ejemplos de contenido                                                          |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `Public/Assets/`           | Almacenar recursos estáticos públicos.                                                                                                                | Logos, íconos, imágenes y fuentes públicas autorizadas.                        |
| `Public/Assets/CSS`        | Almacenar las hojas de estilo encargadas de definir la apariencia, distribución y diseño responsivo de las interfaces.                                | `base.css`, estilos generales y carpeta `Dedicated/`.                          |
| `Public/Assets/Dedicated`  | Organizar los estilos CSS exclusivos de determinadas vistas o módulos, evitando mezclar estilos específicos con los globales.                         | `login.css`, `dashboard.css`, `pacientes.css`.                                 |
| `Public/Assets/IMG`        | Almacenar y organizar los recursos gráficos utilizados en las distintas interfaces del sistema                                                        | Logotipos, iconos, ilustraciones, imágenes institucionales y fondos.           |
| `Public/Assets/JavaScript` | Centralizar los scripts que permiten gestionar eventos, interacciones y comportamientos dinámicos del FrontEnd.                                       | Scripts de vistas, funciones reutilizables y utilidades JavaScript.            |
| `JavaScript/Dedicated/`    | Contener los scripts exclusivos de una vista o módulo, manteniendo separada su lógica de interacción.                                                 | `login.js`, `dashboard.js`, `pacientes.js`.                                    |
| `JavaScript/Functions/`    | Almacenar funciones reutilizables que pueden ser utilizadas por diferentes vistas o módulos para evitar la duplicación de código.                     | Validación de formularios, formateo de fechas, alertas y funciones auxiliares. |
| `Public/Assets/MP3`        | Organizar los archivos de audio utilizados para proporcionar notificaciones o respuestas sonoras en la interfaz.                                      | Sonidos de notificación, alertas, confirmaciones y errores.                    |
| `Public/Assets/Uploads`    | Organizar los archivos cargados por los usuarios mediante las funcionalidades del sistema, aplicando controles de acceso y validaciones de seguridad. | Imágenes de perfil y otros archivos permitidos para acceso público.            |
| `Uploads/Perfil`           | Almacenar y organizar las imágenes de perfil cargadas por los usuarios, de acuerdo con las políticas de acceso del sistema.                           | Fotografías de perfil e imágenes de usuario.                                   |

## 3. Tour por las carpetas

### `App/Configs/` — Configuración

**Qué colocar:** parámetros que se necesitan en distintos módulos: rutas base, tiempos de espera, banderas de funcionalidad, nombres de claves de almacenamiento y configuración del cliente HTTP.

**Cómo trabajar:**

1. Consulta primero cómo se leen actualmente las variables de entorno.
2. Declara una sola fuente de verdad para cada parámetro.
3. Evita repetir direcciones de API o valores de configuración en las vistas.
4. **Nunca** guardes secretos, contraseñas ni claves privadas en el código del frontend: los archivos enviados al navegador pueden ser inspeccionados.

### `App/Controllers/` — Coordinación de acciones

**Qué colocar:** lógica que recibe eventos o intenciones de una vista, solicita una operación al servicio correspondiente y entrega resultados o errores a la UI.

**Cómo trabajar:** un controlador puede encargarse de la acción “guardar formulario”, pero debería delegar la lógica de negocio en un servicio y el acceso a datos en un repositorio. No mezcles aquí código visual con consultas HTTP sin una razón clara.

**Nota:** en frameworks basados en componentes o hooks, esta capa puede estar implementada con controladores, composables, hooks o handlers; conserva el patrón que el proyecto realmente use.

### `App/Core/` — Fundamentos compartidos

**Qué colocar:** componentes técnicos que dan soporte al resto de la aplicación: inicialización, abstracciones comunes, manejo estándar de errores, contratos base o mecanismos de estado general.

**Cómo trabajar:** coloca aquí solo funcionalidades realmente transversales. Evita convertir `Core` en una carpeta para código que no sabes dónde ubicar.

### `App/Helpers/` — Utilidades reutilizables

**Qué colocar:** pequeñas funciones que transforman o presentan datos, como formatear fechas, limpiar espacios o construir etiquetas.

**Cómo trabajar:** prioriza funciones fáciles de probar, sin dependencias de la interfaz ni llamadas a APIs. Si una función empieza a decidir reglas del negocio, probablemente pertenece a `Services`.

### `App/Middleware/` — Lógica transversal

**Qué colocar:** controles ejecutados de forma común antes, durante o después de ciertas acciones, según la tecnología: interceptores HTTP, comprobaciones de sesión o protección de navegación.

**Cómo trabajar:** define claramente dónde se registra el middleware, en qué orden corre y qué ocurre cuando falla. **La validación de permisos en frontend solo mejora la experiencia: la autorización real debe verificarse también en el backend.**

### `App/Models/` — Estructuras de datos

**Qué colocar:** tipos e interfaces, esquemas de validación, representaciones de entidades y objetos de intercambio con la API (DTO).

**Cómo trabajar:** diferencia los datos que envía/recibe el backend de los que necesita la interfaz. Mantén nombres y propiedades consistentes; al cambiar un contrato de API, revisa su impacto en repositorios, servicios y vistas.

### `App/Repositories/` — Acceso a datos

**Qué colocar:** operaciones que consultan o modifican datos desde una fuente externa, como `listar`, `obtenerPorId`, `crear`, `actualizar` o `eliminar`.

**Cómo trabajar:** reutiliza un cliente HTTP configurado, centraliza rutas y parámetros, transforma respuestas cuando corresponda y propaga errores de manera consistente. Evita que las vistas construyan URLs o hagan peticiones duplicadas.

### `App/Services/` — Operaciones y reglas

**Qué colocar:** lógica que resuelve una necesidad funcional, por ejemplo validar una operación, preparar datos antes de enviarlos, combinar resultados de varios repositorios o establecer reglas de un flujo.

**Cómo trabajar:** define métodos con intención clara y deja que el servicio hable con los repositorios cuando requiera datos. Evita referencias directas a componentes visuales para mantener esta capa reutilizable.

### `App/Views/` — Interfaz de usuario

**Qué colocar:** páginas, componentes, formularios, tablas, menús, estilos asociados y estados de la interfaz (cargando, vacío, éxito, error), respetando la convención de organización que exista.

**Cómo trabajar:** divide pantallas grandes en componentes mantenibles, conserva la accesibilidad de botones y formularios, muestra errores útiles y evita que la presentación contenga lógica compleja de negocio o llamadas HTTP repetidas.

### `Public/Assets/` — Recursos estáticos

**Qué colocar:** archivos que el frontend debe servir públicamente, como logotipos, ilustraciones e íconos.

**Cómo trabajar:** usa nombres descriptivos (`logo-sicolmed.svg`), optimiza tamaño y formato, evita duplicados y revisa las rutas públicas según el framework. **No incluyas archivos confidenciales**, porque este directorio suele ser accesible desde el navegador.

## 4. Flujo recomendado entre carpetas

Ejemplo: una persona abre un formulario y guarda un registro.

```text
Usuario interactúa con una pantalla
                │
                ▼
           Views
 (muestra y captura datos)
                │
                ▼
         Controllers
    (coordina la acción)
                │
                ▼
           Services
 (valida y aplica las reglas)
                │
                ▼
         Repositories
   (accede a la fuente de datos)
                │
                ▼
        API / Backend
                │
                ▼
  Respuesta de vuelta hacia la vista
```

Las demás carpetas apoyan ese recorrido: `Models` define formas de datos, `Configs` aporta ajustes, `Core` reúne mecanismos base, `Helpers` ofrece utilidades, `Middleware` ejecuta controles transversales y `Public/Assets` proporciona recursos visuales.

Este flujo es **una referencia**, no una obligación: en una aplicación simple puede no hacer falta un controlador adicional y el framework podría manejar la navegación o el estado de otra forma.

## 5. ¿Dónde ubico un archivo nuevo?

- ¿Se **ve** en la pantalla? → `Views`.
- ¿Define **cómo son los datos**? → `Models`.
- ¿Hace una **consulta o escritura de datos**? → `Repositories`.
- ¿Resuelve una **regla o proceso funcional**? → `Services`.
- ¿Coordina un **evento de una pantalla**? → `Controllers`, si se usa esa capa.
- ¿Es una **función general pequeña**? → `Helpers`.
- ¿Es una **capacidad técnica central**? → `Core`.
- ¿Es una **opción configurable**? → `Configs`.
- ¿Actúa **transversalmente** sobre rutas o solicitudes? → `Middleware`.
- ¿Es una **imagen o recurso público**? → `Public/Assets`.

Si un archivo cumple varias funciones, considera dividirlo para evitar dependencias innecesarias.

## 6. Forma de trabajo recomendada

1. **Antes de programar:** identifica el módulo afectado, revisa implementaciones similares y confirma contratos de la API.
2. **Planifica cambios por capas:** empieza por el modelo de datos, sigue con acceso a datos y lógica, y finalmente conecta la interfaz. Adapta el orden según la tarea.
3. **Respeta los nombres existentes:** usa los estilos de nombres, extensiones, imports y exportaciones que ya estén estandarizados.
4. **Maneja los estados:** considera carga, error, resultado vacío y éxito; no asumas que toda solicitud funciona.
5. **Protege la información:** no expongas credenciales, tokens ni datos sensibles en logs o recursos públicos. Verifica las reglas de autorización en servidor.
6. **Prueba lo modificado:** ejecuta las pruebas y verificaciones de formato/lint disponibles en el repositorio; comprueba el flujo desde la vista hasta la API.
7. **Entrega cambios pequeños:** documenta qué se cambió, cómo probarlo y qué dependencias o endpoints hacen falta.

## 7. Errores comunes que conviene evitar

| Evitar                                                             | Mejor práctica                                                        |
| ------------------------------------------------------------------ | --------------------------------------------------------------------- |
| Consultar la API directamente en múltiples componentes de `Views`. | Centralizar el acceso a datos en `Repositories`, cuando aplique.      |
| Repetir reglas de validación funcional en distintas pantallas.     | Consolidar reglas compartidas en `Services` o esquemas reutilizables. |
| Guardar funciones grandes y específicas en `Helpers`.              | Asignarles una capa con responsabilidad clara.                        |
| Añadir secretos en `Configs` o `Public/Assets`.                    | Mantenerlos en el servidor y exponer solo configuración segura.       |
| Usar `Middleware` frontend como única barrera de seguridad.        | Verificar permisos y acceso también desde el backend.                 |
| Crear archivos sin revisar patrones existentes.                    | Mantener consistencia con el proyecto y sus convenciones.             |

## 8. Checklist antes de finalizar una funcionalidad

- [ ] Identifiqué la carpeta correcta para cada responsabilidad.
- [ ] Reutilicé modelos, configuraciones y componentes existentes cuando correspondía.
- [ ] Evité duplicar llamadas a API y reglas de negocio.
- [ ] Contemplé estados de carga, éxito y error.
- [ ] Revisé manejo de datos sensibles y permisos.
- [ ] Probé el flujo de principio a fin.
- [ ] Dejé documentados los cambios y dependencias importantes.
