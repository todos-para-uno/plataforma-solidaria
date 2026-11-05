# Cabeceras de seguridad y sesión inactiva

Implementación posterior a la revisión de `checklist-2026-11-05.md`.

## Cabeceras de la aplicación

`public_php/index.php` aplica `CabecerasSeguridad` antes de validar la ruta o el método. Las respuestas HTML, JSON, redirecciones y errores propios de la aplicación reciben:

- `Content-Security-Policy`: recursos del propio sitio y los orígenes específicos utilizados por mapas y búsqueda de direcciones. Bloquea scripts inline, eval, plugins, formularios externos y el uso de la aplicación dentro de iframes.
- `X-Frame-Options: DENY`, coherente con `frame-ancestors 'none'`.
- `X-Content-Type-Options: nosniff`.
- `Referrer-Policy: strict-origin-when-cross-origin`.

También se elimina `X-Powered-By` en estas respuestas. Los estilos inline siguen permitidos porque las barras de progreso y Leaflet los usan; esta excepción no habilita scripts inline. Las vistas previas permiten imágenes `blob:` y `data:`. Leaflet se restringe a su versión 1.9.4 en unpkg; los mosaicos usan `tile.openstreetmap.org`, las búsquedas `photon.komoot.io` y los mapas embebidos `www.openstreetmap.org`.

La aplicación no configura aquí HSTS, redirección HTTPS ni cabeceras de archivos servidos directamente por Apache/Nginx: esas partes deben completarse y verificarse al elegir el hosting y estabilizar HTTPS. Tampoco se aplican estas cabeceras al abrir una vista PHP directamente por fuera del punto de entrada de la aplicación.

## Diez minutos de inactividad

- El ingreso correcto registra `ultima_actividad` en la sesión del servidor.
- Cada solicitud válida al punto de entrada comprueba el tiempo transcurrido antes de consultar la identidad o ejecutar un controlador.
- A los **600 segundos o más** sin solicitudes autenticadas se invalida la identidad, se descartan los datos de esa sesión, se regenera su identificador y se crea un CSRF nuevo. No depende del recolector de sesiones de PHP ni de JavaScript.
- Un POST vencido se detiene antes de ejecutar la acción. Las páginas redirigen al ingreso con un aviso; las API administrativas o solicitudes que piden JSON reciben HTTP 401.
- Las sesiones de visitantes no se vencen mediante esta regla. Una sesión autenticada abierta antes de instalar el cambio comienza su primera ventana al volver a navegar.
- Se considera actividad una solicitud a la aplicación, incluidas las solicitudes AJAX. Mover el mouse o escribir sin enviar una solicitud no renueva el plazo. No se agregaron consultas periódicas que mantengan la sesión abierta por sí solas.
- Una pestaña que ya está abierta puede seguir mostrando su contenido; la siguiente navegación o envío exige ingresar si pasaron diez minutos. No hay temporizador visual de cierre en el navegador.

Los mensajes técnicos visibles usados por el equipo se mantienen, tal como se solicitó. Su restricción para producción queda pendiente.

## Comprobaciones repetibles

```bash
php tests/run_tests.php seguridad SesionInactiva
php tests/run_tests.php controladores IngresoController
python3 tests/seguridad/http_seguridad.py
```

La prueba HTTP necesita PHP, Python 3 y una base local preparada con las cuentas/campañas de demostración. Usa un servidor ligado exclusivamente a `127.0.0.1`, sesiones y router temporales fuera del directorio público. No crea usuarios ni modifica perfiles o contraseñas; comprueba que los POST vencidos no alcancen esas acciones. No envía correos. El router de autenticación de prueba no se instala en `public_php`.

Referencias: [OWASP, gestión de sesiones](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html) y [OWASP, CSP](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html).
