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

La configuración de conexión y las rutas de la aplicación se documentarán al completar su integración.

## Trabajo en equipo

Cada funcionalidad se desarrolla en una rama propia y se propone mediante un **pull request** para revisar antes de integrar en `main`. Los cambios de base de datos o arquitectura se acuerdan con el equipo y se documentan en el repositorio.
