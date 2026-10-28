# todospara.uno

Una plataforma solidaria para conectar a quienes necesitan ayuda con quienes quieren colaborar, mediante donaciones de dinero o insumos.

Proyecto académico del **Grupo 4**, de la **Tecnicatura Universitaria en Programación de la UTN**.

Si recién te sumás al equipo, empezá por abrir la aplicación en tu máquina y ejecutar las pruebas. Después, usá el mapa de carpetas para ubicar dónde va tu cambio.

## Contenido

- [Estado del proyecto](#estado-del-proyecto)
- [Preparar el entorno](#preparar-el-entorno)
- [Abrir la aplicación](#abrir-la-aplicación)
- [Ejecutar las pruebas](#ejecutar-las-pruebas)
- [Entender el recorrido del código](#entender-el-recorrido-del-código)
- [Trabajar en las vistas](#trabajar-en-las-vistas)
- [Preparar MariaDB](#preparar-mariadb)
- [Trabajar en equipo](#trabajar-en-equipo)
- [Resolver problemas frecuentes](#resolver-problemas-frecuentes)

## Estado del proyecto

Estamos en desarrollo. El objetivo es tener registro e inicio de sesión, perfiles, campañas, donaciones, mensajes y administración.

| Parte | Qué tenemos hoy |
| --- | --- |
| Modelos | Clases y validaciones de datos, con pruebas de distinto alcance. |
| Base de datos | Script con las tablas y los niveles iniciales de confianza de usuarios. |
| Repositorios | Consulta y guardado de usuarios y niveles de confianza conectados al registro. Las demás funcionalidades siguen en integración. |
| Pantallas | Inicio con campañas ficticias, formulario de registro y componentes compartidos. |
| Registro | Valida los datos en el servidor, comprueba correos duplicados y guarda el usuario con contraseña hasheada y nivel de confianza inicial. El formulario tiene protección CSRF. |
| Ingreso y salida | Comprueba correo y contraseña, mantiene la sesión al navegar y permite cerrarla desde el menú. Ambos envíos tienen protección CSRF. |
| Contacto | Valida consultas con CSRF, las guarda en `contactos` e intenta enviar el aviso con `mail()` a una dirección simulada. Funciona con o sin una cuenta. |
| Integración | Registro, ingreso, cierre de sesión y contacto están conectados. Las demás funcionalidades todavía no tienen un recorrido completo desde la web. |

**El registro ya crea una cuenta en la base de datos.** El usuario nuevo queda sin verificar y sin permisos de administrador. Registrarse no inicia sesión automáticamente: después podés entrar desde **Ingresar**. Los enlaces que apuntan a `#` representan funcionalidades pendientes.

## Preparar el entorno

Necesitás:

- **PHP 8.1 o superior**, con `mbstring` para trabajar con textos con tildes.
- **Git o GitHub Desktop** para descargar el código y compartir cambios.
- **MariaDB y la extensión PHP `pdo_mysql`** para abrir y procesar el registro y para ejecutar las pruebas de repositorios y controladores. El inicio con datos ficticios y las pruebas de modelos se pueden probar sin MariaDB.

Podés usar el servidor que trae PHP para ver la aplicación. Si ya tenés LAMP o XAMPP, también sirve. No hay dependencias de Composer ni de npm que instalar en esta versión.

### Descargar desde la terminal

```bash
git clone https://github.com/todos-para-uno/plataforma-solidaria.git
cd plataforma-solidaria
```

### Descargar con GitHub Desktop

1. Iniciá sesión en GitHub Desktop.
2. Entrá en **File > Clone repository > URL**.
3. Pegá `https://github.com/todos-para-uno/plataforma-solidaria.git`.
4. Elegí una carpeta en tu máquina y hacé clic en **Clone**.
5. Abrí esa carpeta en tu editor y abrí una terminal ahí.

En los pasos que siguen, **raíz del proyecto** significa la carpeta `plataforma-solidaria`, donde está este README. Los comandos se ejecutan desde esa carpeta.

## Abrir la aplicación

1. Comprobá que PHP esté disponible:

   ```bash
   php -v
   ```

2. Prepará la base y la conexión siguiendo [Preparar MariaDB](#preparar-mariadb). Si ya tenés una base local, comprobá que su estructura coincida con el esquema actual.
3. Iniciá el servidor local:

   ```bash
   php -S localhost:8000 -t public_php
   ```

4. Abrí <http://localhost:8000/> y elegí **Crear cuenta**.
5. Completá el formulario con datos ficticios y enviá. Si los datos son válidos y el correo no está registrado, deberías ver **Cuenta creada correctamente**. Podés comprobar la nueva fila en la tabla `usuarios`.

La terminal queda ocupada mientras el servidor está funcionando. Para detenerlo, presioná `Ctrl+C`. Si querés ejecutar otros comandos mientras tanto, abrí otra terminal en la raíz del proyecto.

En Windows, si usás XAMPP y `php` no se reconoce en PowerShell, indicá la ruta al ejecutable:

```powershell
& "C:\xampp\php\php.exe" -S localhost:8000 -t public_php
```

Si instalaste XAMPP en otra carpeta, ajustá esa ruta. GitHub Desktop gestiona los cambios del código; PHP ejecuta la aplicación.

### Rutas disponibles

Una ruta indica qué pantalla o acción estamos pidiendo. **GET** se usa acá para abrir una página y **POST** para enviar el formulario.

| Método | URL | Resultado |
| --- | --- | --- |
| GET | `/` o `/index.php` | Página de inicio con datos ficticios. |
| GET | `/index.php?ruta=registro` | Formulario de registro con token CSRF; requiere conexión a la base. |
| POST | `/index.php?ruta=registro` | Validación y guardado del usuario; confirmación si se crea la cuenta o mensajes si hay errores de validación. |
| GET | `/index.php?ruta=ingresar` | Formulario de ingreso con token CSRF; si ya hay un usuario autenticado, redirige al inicio. |
| POST | `/index.php?ruta=ingresar` | Comprueba las credenciales e inicia la sesión; redirige al inicio si son correctas. |
| POST | `/index.php?ruta=salir` | Valida el token CSRF, elimina la sesión y su cookie, y redirige al inicio. No acepta GET. |
| POST | `/index.php?ruta=banear` | Un administrador crea un baneo para un usuario común; requiere sesión y token CSRF. Devuelve JSON. |
| POST | `/index.php?ruta=finalizar_baneo` | Un administrador finaliza un baneo vigente sin borrar su historial; requiere sesión y token CSRF. Devuelve JSON. |
| GET | `/index.php?ruta=contacto` | Formulario de consultas con CSRF; completa nombre y correo si hay sesión. Requiere conexión a la base. |
| POST | `/index.php?ruta=contacto` | Valida y guarda la consulta, intenta enviar el correo y redirige al formulario con una confirmación. |
| GET | `/index.php?ruta=mis_donaciones` | Historial con datos e imágenes de ejemplo. |

[`public_php/index.php`](public_php/index.php) recibe las peticiones y elige qué ejecutar según `ruta`. Una ruta desconocida devuelve **404** y un método no admitido, **405**. Las rutas usan parámetros en la URL y no necesitan reglas de reescritura de Apache.

[`app/vistas/parciales/rutas.php`](app/vistas/parciales/rutas.php) construye los enlaces que usan las vistas; el punto de entrada público resuelve esas peticiones. Registro e ingreso tienen bloques propios que llaman a sus controladores, por eso no aparecen en el arreglo `$vistas`. Para probarlos, entrá por `/index.php?ruta=registro` o `/index.php?ruta=ingresar`: abrir las vistas directamente saltea la preparación del formulario y del token CSRF.

Con Apache o XAMPP, configurá `public_php/` como directorio público. Si clonaste el proyecto dentro de `htdocs`, también podés entrar a `/plataforma-solidaria/public_php/index.php` desde tu servidor local.

## Ejecutar las pruebas

Desde la raíz del proyecto:

```bash
php tests/run_tests.php
```

En PowerShell con XAMPP, si PHP no está en el `PATH`:

```powershell
& "C:\xampp\php\php.exe" tests/run_tests.php
```

El ejecutor llama a las pruebas de **modelos, repositorios y controladores**. Necesita MariaDB y una base preparada con el esquema actual. Las pruebas de repositorios y registro escriben datos dentro de transacciones que se revierten al terminar; usá una base local de prueba. Algunos mensajes incluyen etiquetas `<br>` porque también se pensaron para verse en el navegador; revisá la salida además del código de salida.

Para ejecutar solo los modelos, sin conectarte a MariaDB:

```bash
php tests/run_tests.php modelos
```

También podés elegir una prueba por suite y nombre. Por ejemplo:

```bash
php tests/run_tests.php modelos Usuarios
php tests/run_tests.php modelos Camp
php tests/run_tests.php controladores RegistroController
php tests/run_tests.php controladores IngresoController
```

La prueba de registro requiere la base y comprueba el guardado, la contraseña hasheada, los permisos iniciales, el rechazo de correos duplicados, las validaciones y la protección CSRF.

La prueba de ingreso también requiere la base. Comprueba las credenciales, los errores del formulario, la renovación del identificador de sesión y del token CSRF, y el cierre de sesión. Los usuarios de prueba se crean dentro de una transacción que se revierte al terminar.

La prueba de Mis campañas comprueba el acceso con el usuario recuperado por el inicio de sesión, que solo se listen sus campañas y que el menú incluya un token CSRF para salir.

La prueba `php tests/run_tests.php controladores ContactoController` comprueba CSRF, validaciones, guardado e hidratación, la asociación con usuarios y la conservación de consultas cuando falla el correo. Sustituye la función de envío para no enviar correos reales y revierte los datos al terminar. Requiere tener creada la tabla `contactos`.

## Entender el recorrido del código

Usamos **MVC (Modelo-Vista-Controlador)** y repositorios para repartir responsabilidades. Así podemos trabajar en distintas partes y acordar qué datos se pasan entre ellas.

| Ubicación | Responsabilidad | Ejemplo |
| --- | --- | --- |
| `public_php/index.php` | Recibir la petición y elegir la ruta. | Derivar el envío de registro al controlador. |
| `app/controladores/` | Coordinar lo que necesita una petición. | Recibir un formulario y decidir qué vista mostrar. |
| `app/modelos/` | Representar los datos y sus reglas. | La clase `Usuario`, en `Usuarios.php`, valida el correo y genera el hash de la contraseña. |
| `app/repositorios/` | Ejecutar las consultas SQL mediante PDO, la interfaz de PHP para acceder a la base. | Buscar usuarios por correo. |
| `app/vistas/` | Presentar la información en HTML. | Mostrar el formulario y sus mensajes. |
| `app/vistas/parciales/` | Compartir partes de las pantallas. | Cabecera, menú, pie y funciones de presentación. |
| `public_php/assets/` | Guardar CSS, JavaScript e imágenes. | Estilos de botones y menú adaptable. |
| `config/conexion.php` | Crear y reutilizar la conexión con MariaDB. | Función `conectarBd()`. |
| `database/schema.sql` | Definir las tablas y los datos iniciales. | Tablas `usuarios` y `camp`. |
| `tests/` | Comprobar o explorar el comportamiento del código. | Rechazar un nombre vacío sin perder el anterior. |

Por ejemplo, al enviar el registro, el recorrido actual es:

```text
Formulario → public_php/index.php → RegistroController
  → validación de datos y token CSRF
  → consulta de correo y nivel de confianza inicial
  → Usuario::crear() → UsuariosRepository → MariaDB
  → vista de confirmación
```

El controlador comprueba los datos y crea la entidad con `Usuario::crear()`. **Crear un objeto `Usuario` no lo guarda en la base**: el guardado lo realiza `UsuariosRepository::insertarUsuario()`, que prepara los campos y llama a `CrudRepository::save()`. No se pasa `$_POST` completo al repositorio. Si hay errores de validación, se vuelve a mostrar el formulario con el nombre y correo ingresados, sin volver a mostrar las contraseñas.

## Trabajar en las vistas

Antes de conectar una pantalla, acordá con quien trabaja en el controlador los nombres de los campos (`name`), la ruta de envío y los datos que recibirá la vista. En registro ya usamos `nombre`, `mail`, `pass`, `confirmar_pass` y el campo oculto `csrf_token`.

### Reutilizar la cabecera y el pie

Definí las variables de la pantalla antes de incluir la cabecera. Este ejemplo supone un archivo dentro de `app/vistas/`, cargado desde el punto de entrada público:

```php
<?php
$tituloPagina = 'Mi pantalla';
$paginaActual = null; // Usá una clave de rutas.php si corresponde marcar un enlace.
$estilosPagina = [];  // Por ejemplo, ['registro.css'] para cargar esa hoja adicional.
require __DIR__ . '/parciales/cabecera.php';
?>
<section class="seccion">
    <div class="contenedor">
        <h1>Mi pantalla</h1>
    </div>
</section>
<?php require __DIR__ . '/parciales/pie.php'; ?>
```

La cabecera abre la estructura HTML y `<main>`; el pie los cierra. Las variables disponibles están documentadas en [`cabecera.php`](app/vistas/parciales/cabecera.php).

- Para mostrar texto recibido de una persona o de la base dentro del HTML, usá `escapar($texto)`. Por ejemplo: `<?= escapar($nombre) ?>`.
- Para cambiar los destinos de los enlaces compartidos, editá [`rutas.php`](app/vistas/parciales/rutas.php). También tenés que implementar la ruta en `public_php/index.php` para que se pueda abrir.
- Las consultas SQL van en los repositorios. El procesamiento de formularios va en los controladores. Las validaciones del navegador ayudan a completar el formulario, pero el servidor tiene que comprobar los datos de nuevo.

### Reutilizar estilos

[`base.css`](public_php/assets/css/base.css) contiene colores, tamaños y componentes compartidos. Las hojas de cada pantalla, como `registro.css`, agregan su distribución específica y se cargan después.

Para ver ejemplos de botones, formularios, tarjetas y menús, iniciá otro servidor local desde la raíz:

```bash
php -S localhost:8001 -t .
```

Abrí <http://localhost:8001/app/vistas/guia-estilos.php>. Agregá `?sesion=1` para ver el menú de un usuario o `?sesion=admin` para ver el de un administrador. Son **datos simulados**: estos parámetros no inician sesión ni otorgan permisos. También podés abrir `/app/vistas/inicio-demo.php` para ver la maqueta inicial.

Este segundo servidor permite abrir las vistas directamente y es solo para la vista previa local. Para probar el recorrido de la aplicación, usá el servidor de `public_php/` en el puerto 8000.

Si necesitás un componente compartido nuevo, agregalo a `base.css` y mostrá un ejemplo en la guía de estilos. Así el resto del equipo puede encontrarlo y reutilizarlo.

## Preparar MariaDB

Este paso hace falta para usar el registro y para trabajar con repositorios o ejecutar pruebas que acceden a la base.

1. Iniciá MariaDB. En XAMPP, el servicio aparece como **MySQL**.
2. En una instancia local de prueba, importá [`database/schema.sql`](database/schema.sql) con phpMyAdmin o el cliente de MariaDB. El script crea la base `todosparauno`, las tablas y los niveles de confianza iniciales. **Usalo en una base nueva**: las tablas no tienen `IF NOT EXISTS`, así que volver a importarlo genera errores.
3. Revisá [`config/conexion.php`](config/conexion.php). Por defecto intenta conectarse a `localhost`, base `todosparauno`, usuario `root` y contraseña vacía. Para usar tus propias credenciales, creá `config/conexion.local.php`, que está excluido de Git, con un arreglo como este:

   ```php
   <?php
   return [
       'dsn' => 'mysql:host=localhost;dbname=todosparauno;charset=utf8mb4',
       'usuario' => 'tu_usuario',
       'password' => 'tu_contraseña',
   ];
   ```

4. Comprobá que PHP tenga habilitada la extensión `pdo_mysql`. Si importás con phpMyAdmin, también necesitás Apache iniciado.

Si ya tenías una base creada, traer los cambios de Git **no actualiza sus tablas**. Compará la estructura con `database/schema.sql`: el registro espera `usuarios.id_validacion`, relacionada con `validaciones.id`, y una fila en `validaciones` con `nivel = 1`. Una base antigua con la columna `usuarios.validacion` necesita actualizarse antes de probar el guardado. No hace falta borrar tus datos ni volver a importar todo el esquema para corregir esa diferencia.

### Comprobar el registro

Con la base preparada, abrí el formulario desde `/index.php?ruta=registro` y verificá con datos ficticios que:

1. Un formulario válido muestra **Cuenta creada correctamente** y crea una sola fila en `usuarios`, con `admin = 0`, `verificado = 0` y el `id_validacion` correspondiente al nivel `1`.
2. La contraseña se guarda como **hash**, el resultado de `password_hash()`, y se comprueba con `password_verify()`.
3. Un correo repetido, un dato inválido o contraseñas que no coinciden no crean la cuenta y muestran un mensaje útil.
4. Si hay un error, el formulario conserva nombre y correo, sin volver a mostrar las contraseñas.
5. Un envío sin token CSRF válido devuelve **403** y no registra al usuario. Para enviar normalmente, abrí primero el formulario desde la aplicación.

La sesión que se abre para proteger el formulario no representa por sí sola un usuario autenticado. La identidad se guarda únicamente después de un ingreso correcto. La verificación de la cuenta sigue pendiente y no impide ingresar.

### Preparar y comprobar contacto

Para una base nueva, importá [`database/schema.sql`](database/schema.sql) desde phpMyAdmin: ese archivo ya crea `contactos` junto con las demás tablas. Si tenías datos en una base anterior, exportalos antes de reemplazarla. La conexión de la aplicación puede tener permisos limitados a consultar y modificar registros.

Abrí **Contacto**, completá una consulta con datos ficticios y enviá. El servidor valida los campos y el token CSRF, guarda una fila en `contactos` y redirige al formulario con **Recibimos tu consulta**. Recargar esa página de confirmación no repite el envío. Un formulario inválido conserva los campos y no crea registros. Si hay una sesión iniciada, la consulta se asocia al usuario recuperado de esa sesión; las visitas sin cuenta también pueden escribir. Si se elimina una cuenta, sus consultas se conservan con `id_usuario = NULL`.

El aviso usa la función PHP `mail()` y el destino simulado `contacto@todosparauno.test`. La dirección de quien consulta se usa como `Reply-To`, con un remitente fijo del sitio. El dominio `.test` es de demostración: no representa un buzón real. Para probar el transporte local, configurá un capturador de correo y el `sendmail_path` de PHP, o SMTP en los entornos que lo utilicen. `mail()` necesita un transporte configurado; guardar la consulta funciona aunque el aviso falle. La columna `correo_enviado` indica si el transporte aceptó el aviso, no que alguien lo recibió.

### Comprobar el ingreso y la salida

1. Creá una cuenta de prueba y abrí **Ingresar** desde el menú.
2. Enviá su correo y contraseña. Deberías volver al inicio y ver el nombre del usuario en el menú.
3. Navegá al buscador o a contacto. El menú debe conservar al usuario; sus datos se consultan en la base usando el ID guardado en la sesión.
4. Elegí **Salir** desde el menú del usuario. Deberías volver al inicio y ver las opciones **Ingresar** y **Crear cuenta**.
5. Probá un correo inexistente o una contraseña incorrecta. Ambos casos muestran **El correo o la contraseña son incorrectos**, conservan el correo ingresado y no inician sesión.

El cierre se envía mediante un formulario POST con token CSRF. No se puede cerrar la sesión abriendo un enlace GET a `?ruta=salir`. El perfil, el guardado de documentos y las acciones propias de campañas o donaciones todavía necesitan sus controladores y comprobaciones de permisos.

Un baneo vigente impide iniciar sesión y muestra **Tu cuenta se encuentra baneada** con respuesta **403**. Si el usuario ya tenía una sesión abierta, la siguiente petición a la aplicación revoca su identidad, renueva la sesión y el token CSRF, y detiene la acción solicitada con el mismo mensaje. Los baneos vencidos, levantados manualmente o que todavía no comenzaron no bloquean el acceso; un baneo sin fecha de fin se considera permanente. Por ahora, la administración de baneos desde una pantalla sigue pendiente.

`BaneoController` reutiliza `Csrf::validar()`, los repositorios y las validaciones de `Baneo`. Para crear un baneo, el POST a `?ruta=banear` recibe `id_usuario`, `motivo`, `csrf_token` y, opcionalmente, `fecha_fin`. Si la fecha está vacía o no se envía, el baneo es permanente. Una fecha de fin debe ser futura y usar `AAAA-MM-DD HH:MM:SS` o el formato de un campo HTML `datetime-local` (`AAAA-MM-DDTHH:MM`), según la hora de la base. El inicio se asigna al momento de la operación. Para finalizarlo, el POST a `?ruta=finalizar_baneo` recibe `id_baneo`, `csrf_token` y `motivo_unban` (obligatorio, de 1 a 500 caracteres). El levantamiento guarda `fecha_unban = CURRENT_TIMESTAMP` (fecha y hora del desbaneo según la base) y la explicación normalizada sin cambiar `fecha_fin`, que conserva el vencimiento original. Un baneo levantado deja de bloquear el acceso, pero permanece en el historial; otro baneo vigente del mismo usuario sigue bloqueándolo. No se puede levantar de nuevo, ni levantar uno vencido o que aún no comenzó.

La identidad del administrador se obtiene del ID guardado en la sesión y sus permisos se consultan en la base en cada petición. No se permite banearse a uno mismo ni banear a otros administradores. Tampoco se crea otro baneo si el usuario ya tiene uno vigente. Las respuestas JSON incluyen `mensaje` y, cuando la operación tiene éxito, `id_baneo`. Crear devuelve **201**; finalizar, **200**. Los errores de sesión, permisos o CSRF no modifican los registros. La prueba `php tests/run_tests.php controladores BaneoController` verifica estas reglas dentro de una transacción que se revierte al terminar.

### Actualizar una base existente

Para actualizar tu base existente, importá `database/migrations/20261028_imagen_y_desbaneo.sql` desde el cliente de MariaDB o phpMyAdmin, seleccionando tu base existente. Este único archivo agrega `camp.imagen`, `baneados.fecha_unban` (`DATETIME NULL`) y `baneados.motivo_unban`, conservando los datos existentes. Comprueba cada columna y agrega solo las que faltan: podés volver a ejecutarlo si ya tenías `imagen` o habías aplicado una parte.

Si creás una base nueva, importá solamente `database/schema.sql`, que ya incluye estos campos. No ejecutes la migración después de importar el schema actualizado ni vuelvas a importar el schema sobre tu base existente.

El código de baneos requiere estos campos antes de usarse. Los registros anteriores quedan con `fecha_unban = NULL` y motivo nulo; las fechas originales se conservan. El historial anterior no permite distinguir qué baneos se levantaron manualmente, porque esa operación se registraba cambiando `fecha_fin`.

## Buscar campañas

Desde **Explorar campañas**, buscá una palabra del nombre o de la descripción y, si querés, elegí una categoría. Ambos filtros se combinan y solo se muestran campañas activas. Dejá los campos vacíos para explorarlas todas. Los resultados conservan los filtros y la URL se puede compartir o recargar.

Las categorías y campañas se leen de MariaDB mediante `BuscadorController`, `CatCampRepository` y `CampRepository`. La búsqueda no agrega campañas de ejemplo: si la base está vacía, muestra el estado sin resultados. Las tarjetas muestran el tipo de ayuda, la categoría y la descripción; el detalle y la donación se implementan en sus respectivos recorridos.

Para comprobar el buscador con MariaDB preparada:

```bash
php tests/run_tests.php repositorios BuscadorRepository
php tests/run_tests.php controladores BuscadorController
```

Las pruebas crean sus categorías, usuario y campañas dentro de una transacción y deshacen esos registros al terminar. Comprueban filtros combinados, búsqueda en descripción, estados públicos, caracteres especiales, validación y escape del HTML.

## Trabajar en equipo

Una **rama** es una línea de trabajo para desarrollar un cambio. Un **commit** guarda un conjunto de cambios en tu repositorio local. Un **pull request (PR)** propone integrar los commits de tu rama y permite que otra persona los revise antes de sumarlos a `main`.

### Preparar y compartir un cambio

1. Revisá con `git status` si tenés cambios pendientes. Guardalos en la rama que corresponda antes de cambiar de tarea.
2. Con el directorio de trabajo limpio, actualizá `main` y creá una rama con un nombre que describa la tarea:

   ```bash
   git switch main
   git pull --ff-only origin main
   git switch -c docs/guia-registro
   ```

   `--ff-only` permite actualizar sin crear un commit de mezcla. Si Git indica que las ramas divergieron, revisá qué commits tiene cada una con el equipo antes de continuar.

3. Hacé un cambio centrado en esa tarea. Si afecta campos de formularios, tablas o métodos que otra persona está usando, acordá el cambio antes de conectarlo.
4. Probá el recorrido que modificaste y ejecutá las pruebas de modelos. Si cambiaste una regla, agregá una prueba que compruebe el resultado y falle cuando no se cumpla.
5. Revisá `git diff`, agregá los archivos de la tarea y creá un commit. Por ejemplo, para un cambio solo en este README:

   ```bash
   git add README.md
   git commit -m "Aclara cómo probar el registro"
   git push -u origin docs/guia-registro
   ```

6. Abrí un PR en GitHub con destino a `main`. Explicá **qué problema resolviste, cómo probarlo y qué queda pendiente**. Si cambiaste una pantalla, sumá una captura. Otra persona revisa la propuesta antes de integrarla.

Con **GitHub Desktop**, el mismo recorrido se hace con **Current branch > main**, **Fetch origin / Pull origin**, **New branch**, selección de archivos, **Commit**, **Publish branch / Push origin** y **Create Pull Request**.

Si aparece un conflicto, significa que Git necesita ayuda para combinar cambios sobre una misma parte del archivo. Revisá ambas versiones con quien trabajó ahí, conservá el comportamiento acordado y volvé a probar antes de compartir la resolución.

### Escribir comentarios útiles

Explicá para qué sirve una parte, qué datos espera o por qué necesita una decisión particular. Por ejemplo, un CBU se representa como texto para conservar sus ceros iniciales. Los tipos y nombres claros ayudan; el comentario agrega el contexto que el código por sí solo no cuenta.

Si documentás una integración pendiente, indicá qué falta y dónde se hará. Al implementarla, actualizá también el comentario y esta guía.

## Resolver problemas frecuentes

| Problema | Qué revisar |
| --- | --- |
| La terminal no reconoce `php`. | Comprobá que esté instalado y en el `PATH`, la lista de carpetas donde la terminal busca ejecutables. Con XAMPP podés usar la ruta completa que mostramos arriba. |
| Aparece `Call to undefined function mb_strlen()`. | Habilitá `mbstring` en el PHP que estás ejecutando. `php --ini` muestra su configuración y `php -m` lista las extensiones activas. Reiniciá el servidor después del cambio. |
| Aparece `could not find driver` al usar la base. | Revisá con `php -m` que `pdo_mysql` esté habilitada. |
| Aparece `Access denied` o `Connection refused`. | Revisá las credenciales de `config/conexion.local.php` y que MariaDB esté iniciado. Si no existe ese archivo, se usan los valores por defecto de `config/conexion.php`. |
| El registro falla porque no existe `id_validacion`. | Compará la tabla `usuarios` con `database/schema.sql`. Una base local antigua puede tener todavía la columna `validacion`; actualizar el código no actualiza la base. |
| El registro devuelve **403** o falta `$csrfToken`. | Abrí `/index.php?ruta=registro` desde el servidor de `public_php/`, permití las cookies de sesión y enviá desde ese formulario. No abras la vista directamente. |
| Ingresar o salir devuelve **403**. | Recargá el formulario o la página y volvé a enviar. El token anterior deja de ser válido al iniciar o cerrar sesión. Permití las cookies de sesión del sitio. |
| El formulario de ingreso devuelve **405** al enviarlo. | Comprobá que la copia servida por Apache esté actualizada y que `public_php/index.php` acepte POST para `ingresar`. |
| El servidor no puede usar el puerto 8000. | Detené el servidor que ya lo ocupa o elegí otro puerto, por ejemplo `php -S localhost:8002 -t public_php`. Abrí ese mismo puerto en el navegador. |
| La página abre sin estilos. | Entrá por la URL del servidor PHP y revisá que hayas usado `-t public_php` desde la raíz. Abrir el archivo con doble clic no ejecuta PHP. |
| Un enlace no cambia de pantalla. | Revisá si su destino en `rutas.php` sigue siendo `#`: esa funcionalidad todavía está pendiente. |

La organización de esta guía toma como referencia la [documentación de Luanti para preparar el entorno](https://docs.luanti.org/for-creators/development-setup/) y sus [guías de Git](https://docs.luanti.org/for-engine-devs/git-guidelines/): instrucciones por tarea, ejemplos concretos y cambios que se puedan revisar por separado.
