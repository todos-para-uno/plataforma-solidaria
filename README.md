# todospara.uno

Una plataforma solidaria para conectar a quienes necesitan ayuda con quienes quieren colaborar, mediante donaciones de dinero o insumos.

Proyecto académico del **Grupo 4**, de la **Tecnicatura Universitaria en Programación de la UTN**.

## Estado del proyecto

Estamos en desarrollo. Ya trabajamos en las clases del modelo y estamos avanzando con los repositorios, las pantallas y su integración. La aplicación todavía no está lista para usarse de principio a fin.

## ¿Qué buscamos construir?

- Registro, inicio de sesión y perfil de usuario.
- Creación y búsqueda de campañas solidarias.
- Registro de donaciones de dinero o insumos.
- Mensajes entre usuarios.
- Administración de categorías y campañas.

## Tecnologías y requisitos

- PHP 8.1 o superior, con las extensiones `mbstring` y `pdo_mysql`.
- MariaDB.
- HTML, CSS y JavaScript.
- Un servidor local con PHP, como Apache en un entorno LAMP (Linux) o XAMPP (Windows).

## ¿Cómo nos organizamos?

Usamos **MVC (Modelo-Vista-Controlador)** y repositorios para separar responsabilidades:

| Carpeta | ¿Qué va acá? |
| --- | --- |
| `app/modelos/` | Clases que representan los datos y sus validaciones. |
| `app/repositorios/` | Consultas SQL y acceso a la base de datos. |
| `app/controladores/` | Recepción de peticiones y coordinación entre modelos, repositorios y vistas. |
| `app/vistas/` | Pantallas y presentación de la información. |
| `public_php/` | Archivos públicos; `assets/` contiene CSS, JavaScript e imágenes. |
| `database/` | Script de creación de la base de datos. |
| `tests/` | Pruebas de las clases. |

**Para trabajar en las vistas:** podemos usar PHP para mostrar datos, pero las consultas SQL van en los repositorios y el procesamiento de formularios, en los controladores. Antes de conectar una pantalla, acordamos los `name` de sus campos, la URL de envío y los datos que necesita mostrar.

## Primeros pasos

### Linux

1. Cloná el repositorio:

   ```bash
   git clone https://github.com/todos-para-uno/plataforma-solidaria.git
   cd plataforma-solidaria
   ```

2. En una instalación local de MariaDB, ejecutá `database/schema.sql` para crear la base `todosparauno` y sus tablas.
3. Para ejecutar las pruebas de modelos desde la raíz del proyecto:

   ```bash
   php tests/run_tests.php
   ```

### Windows (con GitHub Desktop)

1. Abrí GitHub Desktop e iniciá sesión con tu cuenta de GitHub.
2. Entrá en **File > Clone repository > URL** y pegá:

   ```text
   https://github.com/todos-para-uno/plataforma-solidaria.git
   ```

3. Elegí la carpeta local donde guardar el proyecto y hacé clic en **Clone**.
4. Iniciá MariaDB desde tu entorno local. Si usás XAMPP, iniciá el servicio **MySQL** del panel e importá `database/schema.sql` desde phpMyAdmin. El script crea la base `todosparauno` y sus tablas.
5. Abrí PowerShell en la carpeta `plataforma-solidaria` y ejecutá las pruebas:

   ```powershell
   php tests/run_tests.php
   ```

   Si PHP no está en el `PATH` y tenés XAMPP instalado en `C:\xampp`, usá:

   ```powershell
   & "C:\xampp\php\php.exe" tests/run_tests.php
   ```

GitHub Desktop gestiona el repositorio; PHP y MariaDB se ejecutan con el entorno local. Para importar con phpMyAdmin, también debe estar iniciado Apache.

Ayuda: [clonar un repositorio con GitHub Desktop](https://docs.github.com/en/desktop/adding-and-cloning-repositories/cloning-and-forking-repositories-from-github-desktop).

## Probar las rutas de registro

Desde la raíz del proyecto, iniciá el servidor local:

```bash
php -S localhost:8000 -t public_php
```

Abrí `http://localhost:8000/` y elegí **Crear cuenta**. Las rutas disponibles son:

| Método | URL | Resultado |
| --- | --- | --- |
| GET | `/index.php` | Página de inicio. |
| GET | `/index.php?ruta=registro` | Formulario de registro. |
| POST | `/index.php?ruta=registro` | Cartel de prueba: «acá iría iniciar la sesión». |

Las rutas se resuelven desde `public_php/index.php`, sin reglas de reescritura.
Con Apache o XAMPP, usá `public_php/` como directorio público, o entrá a
`/plataforma-solidaria/public_php/index.php` si clonaste el proyecto en el directorio web.

Este recorrido todavía no valida los datos en el servidor, no guarda usuarios ni
inicia una sesión. La confirmación aclara que la cuenta todavía no fue creada.
No requiere MariaDB para probarlo. Las contraseñas enviadas no se muestran ni se conservan.

## Prueba futura con MariaDB

Cuando se integre el guardado, la prueba completa será:

1. Preparar una instancia de MariaDB de prueba e importar `database/schema.sql`.
2. Configurar `config/conexion.php` con un usuario y contraseña que tengan acceso
   a esa base. La configuración actual usa `root` sin contraseña y puede no
   coincidir con la instalación local.
3. Enviar el formulario con datos ficticios y un correo que todavía no exista.
4. Consultar `usuarios` desde phpMyAdmin o el cliente de MariaDB y comprobar que
   se creó una sola fila, con los datos esperados y los permisos iniciales.
5. Verificar con `password_verify()` que la contraseña coincide con el hash
   almacenado; la contraseña original nunca debe guardarse como texto.

La instancia debe ser exclusiva para pruebas: el script crea la base `todosparauno`
y las pruebas de repositorios actuales escriben datos. `php tests/run_tests.php`
ejecuta únicamente las pruebas de modelos.

## Trabajo en equipo

Cada funcionalidad se desarrolla en una rama propia y se propone mediante un **pull request** para revisar antes de integrar en `main`. Los cambios de base de datos o arquitectura se acuerdan con el equipo y se documentan en el repositorio.
