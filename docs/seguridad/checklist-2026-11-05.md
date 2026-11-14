# Revisión de seguridad de todospara.uno

Fecha: 2026-11-05 UTC (2026-11-04 en Argentina). Rama revisada: `main`, commit `fea9031`.

Se contrastaron los 50 ítems de la checklist con el código, pruebas existentes, un servidor PHP local temporal con raíz `public_php/`, DNS público y avisos públicos de dependencias. No se modificaron código de aplicación, credenciales, DNS ni configuraciones del proveedor. No se enviaron correos ni se escanearon puertos externos. Los datos de las pruebas de base se revirtieron.

Esto es una revisión acotada, no una certificación ni un pentest exhaustivo. La inexistencia de hosting de destino se clasifica como **Despliegue pendiente**, no como vulnerabilidad comprobada. Los bloques DNS y correo sí se pueden evaluar parcialmente porque `todospara.uno` y la casilla ya existen.

Estados: **Comprobado**, **Parcial**, **Pendiente**, **No verificado**, **Despliegue pendiente**. Comprobado significa que hay evidencia para el alcance descrito, no una garantía absoluta. HSTS y SPF aparecen dos veces en la checklist; no se usa un porcentaje global que los cuente como controles independientes.

## Hallazgos que se pueden trabajar ahora

| Prioridad | Hallazgo | Evidencia | Acción propuesta |
| --- | --- | --- | --- |
| Alta, antes de publicar | Se muestran mensajes internos de excepciones al crear campañas. | `app/controladores/CrearCampanaController.php:45` agrega `Detalle técnico (desarrollo)` al error visible, incluso si `display_errors` está desactivado. El servicio de imágenes puede incluir rutas de disco en sus excepciones. | Mantener el mensaje técnico en logs y permitir su visualización solo bajo un modo de desarrollo explícito. Revisar además el tratamiento general de errores de base. |
| Alta, antes de publicar | Faltan cabeceras generales de protección de la aplicación. | En respuestas locales no aparecen CSP, X-Frame-Options, Referrer-Policy ni nosniff general. Este último existe para avatares y subidas. | Centralizar cabeceras en la entrada de la aplicación. Diseñar CSP considerando Leaflet, unpkg, mapas de OpenStreetMap, Photon y estilos utilizados; evitar copiar una política que rompa esos recursos. Verificar también respuestas 4xx/5xx. |
| Alta, antes de publicar | No hay un control explícito de expiración de sesión por inactividad. | No se encontró una marca de última actividad ni vencimiento absoluto en el flujo de sesión. El PHP local tiene `session.gc_maxlifetime=7200`, que no equivale a un cierre controlado por la aplicación. | Establecer y probar vencimiento por inactividad y un límite absoluto en el servidor; invalidar sesión y token CSRF al vencer. |
| Alta al habilitar acceso público | Contacto no tiene limitación de frecuencia ni protección específica contra automatización. | CSRF está presente; no se encontró CAPTCHA ni control de frecuencia en `ContactoController`. Cada consulta válida puede intentar un aviso SMTP. | Añadir límites de frecuencia y una estrategia antibots apropiada. CSRF y CAPTCHA resuelven problemas distintos. Mantener el guardado independiente del correo. |
| Alta, antes de publicar | El esquema de demostración crea administradores con contraseña publicada, y la conexión predeterminada usa root sin contraseña. | `README.md`, datos demo de `database/schema.sql` y valores de respaldo de `config/conexion.php`. La conexión privada de esta máquina es distinta y no se publican sus datos. | Separar instalación académica de producción. No desplegar cuentas demo ni usar root; crear un usuario de base con permisos mínimos. |
| Media | El PHP local expone su versión mediante X-Powered-By. | Respuestas de la vista previa: `X-Powered-By: PHP/8.4.27`. | Configurar `expose_php=Off` en el entorno final y revisar cabeceras del servidor/proxy. Ocultar versiones no reemplaza las actualizaciones. |
| Media | El navegador descarga Leaflet desde un CDN sin atributos de integridad. | `app/vistas/crear_campana.php:14` y `app/vistas/punto_entrega.php:5`, versión fijada en 1.9.4. | Evaluar servir la biblioteca localmente o incorporar SRI con los hashes oficiales al preparar CSP. |

Estas son propuestas: no se aplicaron cambios funcionales durante la revisión.

## A. SSL/TLS y HTTPS

Los ocho controles se refieren al sitio web de destino. La conexión TLS del SMTP de DonWeb es un servicio distinto y no acredita el HTTPS del sitio.

| ID | Control | Estado | Evidencia o condición para evaluarlo |
| --- | --- | --- | --- |
| A1 | Certificado válido y vigente | Despliegue pendiente | No hay endpoint de producción definido. |
| A2 | Cobertura de subdominios activos | Despliegue pendiente | Falta inventario de los nombres que publicarán la aplicación. No es obligatorio usar wildcard: varios certificados correctos también pueden cubrirlos. |
| A3 | Cadena completa de certificados | Despliegue pendiente | Verificar desde clientes externos cuando exista el endpoint. |
| A4 | Renovación automática | Despliegue pendiente | Depende del proveedor y del mecanismo de emisión elegido. |
| A5 | Redirección HTTP a HTTPS | Despliegue pendiente | El desarrollo local por HTTP no se considera un fallo de producción. |
| A6 | Solo TLS 1.2 o superior | Despliegue pendiente | Configuración del servidor o proxy que termine TLS. |
| A7 | Protocolos y cifrados modernos | Despliegue pendiente | Debe comprobarse la negociación del endpoint final. |
| A8 | HSTS de un año | Despliegue pendiente | Activar después de estabilizar HTTPS. `includeSubDomains` requiere confirmar que todos los subdominios afectados funcionan por HTTPS. |

## B. DNS y dominio

Consultas públicas por DNS-over-HTTPS al resolutor de Google. Los resultados reflejan ese resolutor y ese momento, no una revisión administrativa del panel ni una inspección exhaustiva de la zona.

| ID | Control | Estado | Evidencia o límite |
| --- | --- | --- | --- |
| B1 | DNSSEC | Pendiente | Las consultas de DS y DNSKEY de `todospara.uno` no devolvieron registros; no se observó una delegación firmada. No se infiere esto solo de la bandera AD. |
| B2 | NS redundantes | Parcial | Se publican `ns3.hostmar.com` y `ns4.hostmar.com`. No se verificó separación geográfica ni independencia de infraestructura. |
| B3 | CAA | Pendiente de validar política | No apareció CAA en el nombre `todospara.uno`; no se comprobó toda la herencia ni subdominios. Definir las CA autorizadas con el proveedor antes de agregar restricciones. |
| B4 | Ausencia de CNAME abandonados | No verificado | No se dispone de inventario completo de subdominios o servicios contratados. No se realizaron intentos de takeover. |
| B5 | SPF | Parcial | Publicado: `v=spf1 include:comp.hostmar.com -all`. La cadena de includes, el límite de consultas y la alineación de un correo real requieren validación adicional. MX nulo no corresponde a este dominio, que recibe correo. |
| B6 | AXFR restringido | No verificado | No se intentaron transferencias de zona; revisar con DonWeb. |

## C. Cabeceras HTTP

| ID | Control | Estado | Evidencia o límite |
| --- | --- | --- | --- |
| C1 | HSTS | Despliegue pendiente | Mismo control que A8; no sirve como prueba emitirlo sobre el HTTP local. |
| C2 | CSP, incluido frame-ancestors | Pendiente | No hay política general en el código ni en las respuestas locales. |
| C3 | X-Frame-Options | Pendiente | No apareció en las respuestas locales. Definir `DENY` o `SAMEORIGIN` según el uso de la aplicación. |
| C4 | X-Content-Type-Options: nosniff | Parcial | Presente para la imagen de perfil y en `.htaccess` de subidas; falta cobertura general. PHP integrado no procesa `.htaccess`. |
| C5 | Referrer-Policy | Pendiente | Hay políticas puntuales en enlaces/iframes, pero no una cabecera general. |

## D. Seguridad del correo

El envío a DonWeb usa SMTP autenticado y verifica certificado y nombre del servidor. Esto protege el tramo aplicación → SMTP; no demuestra por sí solo SPF/DKIM/DMARC ni el cifrado de todos los saltos entre servidores de correo.

| ID | Control | Estado | Evidencia o límite |
| --- | --- | --- | --- |
| D1 | SPF | Parcial | Mismo registro de B5. Se comprobó su publicación, no todos los caminos de envío. |
| D2 | DKIM configurado y firmando | No verificado | `default._domainkey.todospara.uno` no existe, pero el selector puede ser otro. Se necesita `DKIM-Signature` y `Authentication-Results` de un mensaje recibido o el selector del panel; no enviar contraseñas. |
| D3 | DMARC en quarantine o reject | Pendiente | Publicado: `v=DMARC1; p=none`. Existe DMARC, pero no solicita cuarentena ni rechazo. Validar primero los emisores legítimos y la alineación para evitar bloquear correo válido. |
| D4 | Reportes DMARC rua | Pendiente | El registro observado no contiene `rua`. Son reportes agregados de autenticación, no exclusivamente avisos de entregas fallidas. |
| D5 | MTA-STS | Pendiente | `_mta-sts.todospara.uno` devuelve NXDOMAIN. No se comprobó una política HTTPS independiente al no existir el anuncio DNS. Coordinar con quien administra los MX. |
| D6 | TLS-RPT | Pendiente | `_smtp._tls.todospara.uno` devuelve NXDOMAIN. |

No se cambió ninguna política del dominio ni se envió un correo nuevo durante esta auditoría. La prueba de entrega a bandeja de entrada confirmada por el usuario en la implementación anterior no equivale a un resultado `dkim=pass` o `dmarc=pass` comprobado aquí.

## E. Cookies y sesiones

| ID | Control | Estado | Evidencia o límite |
| --- | --- | --- | --- |
| E1 | Cookie Secure | Parcial | `public_php/index.php:69` la habilita al detectar HTTPS en PHP. En la prueba HTTP local no se envía Secure, comportamiento esperado. Si se usa proxy TLS, habrá que configurar una detección confiable y probarlo; no confiar ciegamente en headers enviados por el cliente. |
| E2 | HttpOnly | Comprobado | Configurado en la entrada y observado en Set-Cookie local. |
| E3 | SameSite | Comprobado | `Lax` configurado y observado. No sustituye el token CSRF. |
| E4 | Caducidad por inactividad | Pendiente | No existe una comprobación explícita por solicitud de última actividad ni de edad absoluta de sesión. |

Además: modo estricto de sesión, regeneración del ID tras autenticar y comprobación de identidad/baneo al navegar. Las contraseñas usan bcrypt mediante `password_hash` y `password_verify`. Las pruebas de ingreso verificaron los casos implementados, pero no constituyen una prueba exhaustiva de todos los ataques a sesiones.

## F. Ficheros y directorios

| ID | Control | Estado | Evidencia o límite |
| --- | --- | --- | --- |
| F1 | Configuración privada fuera de acceso público | Parcial | No hay `.env` usado por el proyecto: se usan archivos `config/*.local.php`, fuera de `public_php` e ignorados por Git. Con esa raíz pública no se recuperó el contenido privado. Hay que mantener esta separación en el hosting. |
| F2 | .git no accesible | Parcial | `.git` está fuera de `public_php`. La petición local a `/.git/HEAD` devolvió el inicio HTML de la aplicación por fallback, no la referencia de Git. No confundir HTTP 200 con exposición del archivo. |
| F3 | Backups y SQL fuera de la web | Parcial | `database/`, `tests/` y `.respaldos-locales/` están fuera de la raíz pública correcta. El SQL versionado contiene datos de demostración; no se deben versionar dumps de datos reales. |
| F4 | Listado de directorios desactivado | Parcial | `Options -Indexes` en subidas. No hay una regla general del hosting todavía. La vista previa PHP no demuestra configuración de Apache/Nginx. |
| F5 | Permisos mínimos, sin 777 | Parcial | No se encontraron rutas escribibles por cualquiera en las carpetas inspeccionadas. Configuraciones privadas: 0600. Carpetas locales de avatar y campañas: 0775. El servicio de avatar define 0600/0700 para contenido que crea; comprobar también permisos de carpetas ya existentes. 0644/0755 no son valores universales para archivos privados. |
| F6 | Diagnósticos y pruebas fuera del sitio público | Parcial | No se encontró `phpinfo()` en la aplicación revisada. Tests y utilidades están fuera de `public_php`; la herramienta de correo rechaza acceso web. Hay que retirar o deshabilitar diagnósticos de cualquier otra rama antes de desplegar. |

La vista previa local usa el fallback del servidor PHP: `/config/correo.local.php`, `/.git/HEAD`, `/database/schema.sql`, `/tests/run_tests.php` y `/utilidades/probar_correo.php` retornaron HTML del inicio, no sus archivos. Eso no sustituye una prueba con el servidor final. Publicar la raíz completa del repositorio en vez de `public_php/` sería una configuración distinta que requiere protección explícita.

## G. Puertos y servicios

| ID | Control | Estado | Condición para evaluarlo |
| --- | --- | --- | --- |
| G1 | SSH restringido | Despliegue pendiente | Revisar el servidor elegido. No se escaneó infraestructura de DonWeb. |
| G2 | Base de datos sin exposición externa | Despliegue pendiente | El hostname local de PDO no demuestra el bind ni el firewall del servidor final. |
| G3 | RDP cerrado o restringido | Despliegue pendiente | Si el destino no usa RDP, se clasificará como no aplicable. |
| G4 | SFTP/FTPS en lugar de FTP plano | Despliegue pendiente | Definir el mecanismo de publicación. |
| G5 | Panel del proveedor restringido | No verificado | Depende de controles de acceso de la cuenta del hosting/proveedor, fuera del repositorio. |

## H. Aplicación y contenido

| ID | Control | Estado | Evidencia o límite |
| --- | --- | --- | --- |
| H1 | Software y dependencias mantenidos | Parcial | No es un CMS. PHPMailer 7.1.1 y Leaflet 1.9.4: la API pública de GitHub no devolvió advisories que afecten esas versiones en la consulta realizada. No es garantía de ausencia de vulnerabilidades; PHP, extensiones, SO y servidor final requieren su propia revisión. |
| H2 | Versiones ocultas | Pendiente | PHP local anuncia su versión. La configuración de Server y proxy del destino queda pendiente. Ocultar versiones reduce información, no reemplaza actualizar. |
| H3 | Administración protegida | Parcial | Login, permiso admin obtenido desde la base, CSRF para escrituras y bloqueo por intentos fallidos están implementados y tienen pruebas. No hay 2FA. Una URL personalizada no reemplaza autenticación ni autorización. Las cuentas demo no deben usarse en producción. |
| H4 | WAF | Despliegue pendiente | No se configuró ni comprobó un WAF para un sitio de producción. No sustituye los controles de la aplicación. |
| H5 | Validación, SQLi y XSS | Parcial favorable | Consultas parametrizadas y listas permitidas para identificadores/ordenamientos, escape HTML con `htmlspecialchars`, JSON con flags HEX en datos de scripts y uso de `textContent` en administración. No se encontró una inyección concreta en los recorridos revisados; no se certifica toda la aplicación. |
| H6 | Formularios con CSRF y protección antibots | Parcial | Tokens aleatorios de 32 bytes y comparación con `hash_equals`; verificaciones de CSRF en acciones revisadas. No hay CAPTCHA ni protección de frecuencia para Contacto. El bloqueo de acceso es por cuenta, no un límite global de solicitudes. |

Controles adicionales comprobados en las subidas: MIME real, formatos permitidos, tamaño/píxeles limitados, recodificación de imagen, nombres aleatorios y prohibición de ejecución adicional para Apache. Los avatares se sirven por una ruta autenticada que no permite elegir el avatar de otra persona.

## I. Infraestructura y operaciones

| ID | Control | Estado | Evidencia o límite |
| --- | --- | --- | --- |
| I1 | Backups diarios externos y restauración | Despliegue pendiente | No hay una política operativa verificable en el repositorio. Git no respalda por sí solo la base ni las imágenes subidas. |
| I2 | Monitorización de integridad | Despliegue pendiente | Git permite comparar código versionado, pero no existe evidencia de alertas operativas automáticas. |
| I3 | Logs y retención de 90 días | Parcial | Hay uso de `error_log` y `log_errors` habilitado en PHP local. No se verificó retención, rotación, permisos ni logs de acceso del destino. No registrar contraseñas, cookies o mensajes privados completos. |
| I4 | Respuesta a incidentes | Pendiente documental | No se encontró un plan de respuesta en las fuentes revisadas. Se puede redactar antes del despliegue: responsables, contención, revocación de credenciales, preservación de evidencia y restauración. |

## Validación realizada

Todas las suites seleccionadas terminaron con código 0: **198 comprobaciones**, sin envíos de correo reales.

| Suite | Comprobaciones |
| --- | ---: |
| CorreoServicio | 22 |
| ContactoController | 29 |
| IngresoController | 53 |
| AdministracionUsuariosController | 23 |
| BaneoController | 43 |
| PerfilImagenController | 16 |
| CrearCampanaImagenes | 12 |

En la vista previa sin sesión, `admin_mensajes` y `admin_usuarios` devolvieron 401. Un GET a la acción `banear` devolvió 405. Se inspeccionaron cookies y cabeceras HTTP, contenido de las respuestas a rutas sensibles y permisos locales. Las pruebas existentes también cubren rechazos por CSRF, intentos de acceso con roles incorrectos y lecturas de avatares ajenos en los casos implementados.

Evidencia de la sesión, sin valores de cookies ni contraseñas:

- `/tmp/todosparauno-seguridad-local.json`: pruebas, cabeceras, permisos y comprobación de contenido de rutas sensibles.
- `/tmp/todosparauno-seguridad-dns.json`: DNS público y consultas de advisories.
- `/tmp/todosparauno-seguridad-*.log`: resultados de las suites. Son temporales, no forman parte del repositorio y pueden desaparecer.

## Orden de trabajo propuesto

1. Resolver exposición de errores internos, cabeceras de aplicación y vencimiento de sesiones, conservando la experiencia de desarrollo local.
2. Incorporar límites para Contacto y decidir el refuerzo de autenticación de administradores según el alcance de la entrega y el futuro acceso público.
3. Verificar DKIM/alineación con un correo recibido y coordinar con DonWeb reportes DMARC y una evolución controlada de la política. No cambiar `p=reject` sin validar los emisores legítimos.
4. Preparar una configuración de producción sin cuentas demo ni root, con raíz pública exclusiva, secretos privados y política de errores/logs.
5. Cuando se elija hosting, completar A, G y los controles operativos, y volver a comprobar las cabeceras, cookies, archivos y DNS desde el entorno real.

## Fuentes primarias

- [OWASP: gestión de sesiones](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html).
- [OWASP: cabeceras HTTP](https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Headers_Cheat_Sheet.html).
- [OWASP: CSP](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html).
- [OWASP: autenticación](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html).
- [OWASP: carga de archivos](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html).
- [RFC 9989: DMARC](https://www.rfc-editor.org/info/rfc9989/).
- [RFC 9990: reportes agregados de DMARC](https://www.rfc-editor.org/info/rfc9990/).
- [RFC 8461: MTA-STS](https://www.rfc-editor.org/info/rfc8461/).
- [DNS-over-HTTPS de Google: SPF público consultado](https://dns.google/resolve?name=todospara.uno&type=TXT).
- [DNS-over-HTTPS de Google: DMARC público consultado](https://dns.google/resolve?name=_dmarc.todospara.uno&type=TXT).
- [Advisories públicos para PHPMailer 7.1.1](https://api.github.com/advisories?ecosystem=composer&affects=phpmailer%2Fphpmailer%407.1.1).
- [Advisories públicos para Leaflet 1.9.4](https://api.github.com/advisories?ecosystem=npm&affects=leaflet%401.9.4).
