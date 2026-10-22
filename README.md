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
| Repositorios | Métodos para consultar y guardar datos; todavía hay diferencias con el modelo y el esquema que debemos resolver al integrarlos. |
| Pantallas | Inicio con campañas ficticias, formulario de registro y componentes compartidos. |
| Registro | Se puede abrir y enviar el formulario. El envío muestra una confirmación de prueba. |
| Integración | Falta conectar el registro con la validación del servidor, el guardado y la sesión. Las demás funcionalidades tampoco tienen un recorrido completo desde la web. |

**Enviar el formulario todavía no crea una cuenta.** El controlador de registro no lee ni conserva las contraseñas enviadas. Los enlaces que apuntan a `#` representan funcionalidades pendientes.

## Preparar el entorno

Necesitás:

- **PHP 8.1 o superior**, con `mbstring` para trabajar con textos con tildes.
- **Git o GitHub Desktop** para descargar el código y compartir cambios.
- **MariaDB y la extensión PHP `pdo_mysql`** cuando trabajes con la base de datos. Las pantallas actuales y las pruebas de modelos se pueden probar sin MariaDB.

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

2. Iniciá el servidor local:

   ```bash
   php -S localhost:8000 -t public_php
   ```

3. Abrí <http://localhost:8000/> y elegí **Crear cuenta**.
4. Completá el formulario con datos ficticios y enviá. Deberías ver **Formulario recibido** y la aclaración de que todavía no se creó una cuenta.

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
| GET | `/index.php?ruta=registro` | Formulario de registro. |
| POST | `/index.php?ruta=registro` | Confirmación de prueba, sin crear una cuenta. |

[`public_php/index.php`](public_php/index.php) recibe las peticiones y elige qué ejecutar según `ruta`. Una ruta desconocida devuelve **404** y un método no admitido, **405**. Las rutas usan parámetros en la URL y no necesitan reglas de reescritura de Apache.

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

El ejecutor llama a las pruebas de **modelos**; no se conecta a MariaDB. Las de `Usuarios` y `Camp` comprueban condiciones y lanzan una excepción si algo falla. Otras son demostraciones que imprimen valores; `ValidacionesTest` informa sus fallos por pantalla. Por eso, revisá también la salida: que el comando termine sin error no garantiza que todos los comportamientos estén comprobados. Algunos mensajes incluyen etiquetas `<br>` porque también se pensaron para verse en el navegador.

Para ejecutar solo las pruebas de usuarios o campañas:

```bash
php tests/modelos/UsuariosTest.php
php tests/modelos/CampTest.php
```

Las funciones de `tests/repositorios/` **escriben datos en la base** y no se ejecutan con el comando principal. Antes de habilitarlas, prepará una instancia de MariaDB exclusiva para pruebas y revisá qué registros crean.

## Entender el recorrido del código

Usamos **MVC (Modelo-Vista-Controlador)** y repositorios para repartir responsabilidades. Así podemos trabajar en distintas partes y acordar qué datos se pasan entre ellas.

| Ubicación | Responsabilidad | Ejemplo |
| --- | --- | --- |
| `public_php/index.php` | Recibir la petición y elegir la ruta. | Derivar el envío de registro al controlador. |
| `app/controladores/` | Coordinar lo que necesita una petición. | Recibir un formulario y decidir qué vista mostrar. |
| `app/modelos/` | Representar los datos y sus reglas. | `Usuarios` valida el correo y genera el hash de la contraseña. |
| `app/repositorios/` | Ejecutar las consultas SQL mediante PDO, la interfaz de PHP para acceder a la base. | Buscar usuarios por correo. |
| `app/vistas/` | Presentar la información en HTML. | Mostrar el formulario y sus mensajes. |
| `app/vistas/parciales/` | Compartir partes de las pantallas. | Cabecera, menú, pie y funciones de presentación. |
| `public_php/assets/` | Guardar CSS, JavaScript e imágenes. | Estilos de botones y menú adaptable. |
| `config/conexion.php` | Crear y reutilizar la conexión con MariaDB. | Función `conectarBd()`. |
| `database/schema.sql` | Definir las tablas y los datos iniciales. | Tablas `usuarios` y `camp`. |
| `tests/` | Comprobar o explorar el comportamiento del código. | Rechazar un nombre vacío sin perder el anterior. |

Por ejemplo, al enviar el registro, el recorrido actual es:

```text
Formulario → public_php/index.php → RegistroController → vista de confirmación
```

Cuando se integre el guardado, el controlador también tendrá que validar los datos con el modelo y usar el repositorio. **Crear un objeto `Usuarios` no lo guarda en la base.** A su vez, `CrudRepository::save()` recibe un arreglo y no ejecuta las validaciones del modelo. Al conectarlos, armá explícitamente los campos que se pueden guardar; evitá pasar `$_POST` completo.

## Trabajar en las vistas

Antes de conectar una pantalla, acordá con quien trabaja en el controlador los nombres de los campos (`name`), la ruta de envío y los datos que recibirá la vista. En registro ya usamos `nombre`, `mail`, `pass` y `confirmar_pass`.

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

Este paso hace falta cuando trabajás con repositorios o con la integración del guardado.

1. Iniciá MariaDB. En XAMPP, el servicio aparece como **MySQL**.
2. En una instancia local de prueba, importá [`database/schema.sql`](database/schema.sql) con phpMyAdmin o el cliente de MariaDB. El script crea la base `todosparauno`, las tablas y los niveles de confianza iniciales. **Usalo en una base nueva**: las tablas no tienen `IF NOT EXISTS`, así que volver a importarlo genera errores.
3. Revisá [`config/conexion.php`](config/conexion.php). Actualmente intenta conectarse a `localhost`, base `todosparauno`, usuario `root` y contraseña vacía. Ajustá las credenciales a tu instalación local y dejalas fuera del commit que compartas.
4. Comprobá que PHP tenga habilitada la extensión `pdo_mysql`. Si importás con phpMyAdmin, también necesitás Apache iniciado.

### Comprobar el registro cuando se integre

Este recorrido todavía está pendiente. Cuando se conecten las partes, verificá con datos ficticios que:

1. Un formulario válido crea una sola fila en `usuarios`, con los permisos iniciales esperados.
2. La contraseña se guarda como **hash**, el resultado de `password_hash()`, y se comprueba con `password_verify()`.
3. Un correo repetido, un dato inválido o contraseñas que no coinciden no crean la cuenta y muestran un mensaje útil.
4. Si hay un error, el formulario conserva nombre y correo, sin volver a mostrar las contraseñas.

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
| Aparece `Access denied` o `Connection refused`. | Revisá las credenciales de `config/conexion.php` y que MariaDB esté iniciado. |
| El servidor no puede usar el puerto 8000. | Detené el servidor que ya lo ocupa o elegí otro puerto, por ejemplo `php -S localhost:8002 -t public_php`. Abrí ese mismo puerto en el navegador. |
| La página abre sin estilos. | Entrá por la URL del servidor PHP y revisá que hayas usado `-t public_php` desde la raíz. Abrir el archivo con doble clic no ejecuta PHP. |
| Un enlace no cambia de pantalla. | Revisá si su destino en `rutas.php` sigue siendo `#`: esa funcionalidad todavía está pendiente. |

La organización de esta guía toma como referencia la [documentación de Luanti para preparar el entorno](https://docs.luanti.org/for-creators/development-setup/) y sus [guías de Git](https://docs.luanti.org/for-engine-devs/git-guidelines/): instrucciones por tarea, ejemplos concretos y cambios que se puedan revisar por separado.
