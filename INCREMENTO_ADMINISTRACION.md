# Incremento administrativo 1 — backend (pendiente de pruebas funcionales)

Base: versión del proyecto con pruebas de integración de ingreso, validada en LAMPP.

## Cambios
- `UsuariosRepository::buscarAdministracion`: búsqueda parcial, sin hashes, con paginación y estados de baneo/bloqueo.
- `UsuariosRepository::actualizarHashPassword`: actualización dedicada del hash.
- `AdministracionUsuariosController`: endpoints JSON para búsqueda, cambio de contraseña y desbloqueo con nueva contraseña.
- `public_php/index.php`: rutas `admin_usuarios` (GET), `admin_password` (POST), `admin_desbloquear` (POST).

## Seguridad
- Permisos administrativos consultados en base de datos en cada petición.
- CSRF obligatorio para POST.
- No permite cambiar la contraseña de ningún administrador, incluido uno mismo.
- El desbloqueo limpia el estado de autenticación en la misma transacción que el cambio de contraseña.
- El desbloqueo NO levanta baneos.
- No se modifica el esquema ni otras vistas.

## Pendiente
- Pruebas funcionales e integración en LAMPP/MySQL.
- Interfaz del panel, modales y paginación dinámica (siguiente incremento).
- Lectura de donaciones, campañas y mensajes.

## Ejemplos (administrador autenticado)
- `GET index.php?ruta=admin_usuarios&q=ana&limite=15&pagina=1`
- `POST index.php?ruta=admin_password` con `id_usuario`, `password`, `confirmacion`, `csrf_token`.
- `POST index.php?ruta=admin_desbloquear` con los mismos campos.

No usar contraseñas reales en registros de pruebas.
