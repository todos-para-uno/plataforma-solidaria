# Diagnóstico del rechazo de autorización OAuth

Fecha: 31 de octubre de 2026.

## Verificación del hosting y aplicación actual (3 de noviembre de 2026)

### Causa de validación identificada en el HTML de respuesta

Comparación adicional del 3 de noviembre, posterior al análisis del HTML:

- Client ID del backend y de la aplicación consultada: `6921561117397304`.
- Retorno raw: `https://clone-rosa-showers-smell.trycloudflare.com/mercadopago-retorno.php`.
- Valor codificado: `https%3A%2F%2Fclone-rosa-showers-smell.trycloudflare.com%2Fmercadopago-retorno.php`.
- El raw y el elemento de `callback_urls` tienen 74 bytes y son idénticos byte por byte. No contienen espacios ni caracteres de control.
- El código usa `http_build_query` sobre el raw, sin preencoding. Decodificar una vez recupera exactamente el raw. El valor codificado coincide con el request de Firefox aportado por el usuario.
- Por tanto, no se detectan doble encoding, caracteres invisibles, diferencias de esquema/host/path/puerto/slash ni un Client ID distinto en los valores comprobados. Esto no explica todavía por qué el validador rechaza el retorno registrado.

La vista sin procesar aportada por el usuario contiene estos campos de la respuesta activa, fuera del diccionario de traducciones:

```json
{
  "typeError": {
    "pageCode": {"id": "invalidRequest", "contextNumber": "16"},
    "code": 400,
    "type": "invalid_redirect_uri"
  },
  "error_type": "OauthParamsValidator invalidRequest",
  "uniqueErrorCode": "STO1XPWGSCWF"
}
```

Código completo de incidente renderizado: `PSO16-STO1XPWGSCWF`. Request ID: `fa1c898a-3d34-4885-b403-da434dcfbe3d`.

Esto identifica la validación que falla: la aceptación del redirect_uri por el autorizador. No identifica aún el motivo por el que lo considera inválido. Una nueva consulta de metadatos posterior a recibir el HTML confirmó el mismo callback_urls exacto, callback_url singular vacío y PKCE activo. No hay base para rellenar el campo singular mediante una API no documentada ni para afirmar que Cloudflare esté bloqueado.

Consulta actualizada para soporte:

> El HTML del rechazo HTTP 400 de nuestra aplicación 6921561117397304 contiene typeError.type=invalid_redirect_uri, error_type=OauthParamsValidator invalidRequest y contextNumber=16. Código de incidente PSO16-STO1XPWGSCWF; request ID fa1c898a-3d34-4885-b403-da434dcfbe3d, 03/11/2026 a las 13:16:25 UTC. El redirect_uri enviado es https://clone-rosa-showers-smell.trycloudflare.com/mercadopago-retorno.php y coincide exactamente con el elemento de callback_urls que devuelve la API de aplicaciones. ¿Qué condición adicional está rechazando el autorizador o qué configuración debe corregirse? Necesitamos que revisen ese intento específico.

Se conservan únicamente campos de diagnóstico. No se guarda el HTML completo, que contiene datos de sesión. Los textos alternativos del diccionario de traducciones no describen errores ocurridos en este intento.

### Reintento desde Cloudflare confirmado por el usuario

Evidencia de Firefox aportada por el usuario:

- Solicitud: `GET https://auth.mercadopago.com.ar/authorization`.
- Estado: HTTP 400, respuesta `text/html; charset=utf-8`.
- Fecha del servidor: 3 de noviembre de 2026, 13:16:25 UTC (10:16:25 en Argentina).
- `x-request-id`: `fa1c898a-3d34-4885-b403-da434dcfbe3d`.
- La información enviada corresponde a una sesión cuyo alias tiene prefijo TESTUSER. Esto no verifica por sí solo el tipo vendedor, país o habilitación de la cuenta.
- No se incorporan cookies, identificadores de sesión, state, challenge ni cabeceras de solicitud al diagnóstico. El cuerpo HTML aportado posteriormente incluye un código de error adicional; ver el diagnóstico específico siguiente.

Agregar este request ID y horario al ticket permite que soporte busque el rechazo exacto. HTTP 400 y `x-cache: Error from cloudfront` no identifican por sí solos la causa ni demuestran que CloudFront sea el origen del problema.

El usuario confirmó el mismo mensaje: “La aplicación no está preparada para conectarse a Mercado Pago”, después de ingresar con el vendedor. La aplicación local envía ahora `https://clone-rosa-showers-smell.trycloudflare.com/mercadopago-retorno.php`. Una consulta posterior del 3 de noviembre confirmó que `callback_urls` contiene exactamente ese retorno, con `use_pkce=true`, `active=true`, `blocked=false` y `disabled=false`. El par de credenciales y la consulta de metadatos respondieron HTTP 200.

El enlace compartido por el usuario usa el Client ID actual, `response_type=code`, `platform_id=mp`, el retorno registrado y challenge con método S256. No se conservan state ni challenge del intento. El nuevo túnel respondió HTTP 200 antes de la prueba.

La cadena enviada coincide con la publicada en callback_urls. Sin embargo, el HTML aportado posteriormente identifica invalid_redirect_uri: la coincidencia en los metadatos no demuestra que el autorizador acepte ese retorno. Se corrige la conclusión anterior que descartaba el retorno como causa del rechazo.

Texto para consultar a soporte de Mercado Pago:

> En Argentina, la aplicación 6921561117397304 rechaza la autorización de un vendedor de prueba con “La aplicación no está preparada para conectarse a Mercado Pago”, después del ingreso. Se reproduce tanto desde una prueba independiente en hosting HTTPS como desde nuestra aplicación local mediante Cloudflare. En la prueba actual, redirect_uri coincide exactamente con callback_urls: https://clone-rosa-showers-smell.trycloudflare.com/mercadopago-retorno.php. PKCE está activado y enviamos code_challenge_method=S256. Los metadatos indican active=true, blocked=false y disabled=false. Las credenciales funcionan con client_credentials (HTTP 200), aunque eso no demuestra que Authorization Code esté habilitado. ¿Pueden indicar qué validación impide autorizar esta aplicación con el vendedor de prueba y si requiere alguna habilitación adicional? Podemos aportar User ID del vendedor, captura y hora exacta del intento.

Adjuntar una captura del mensaje y el horario del intento. No adjuntar contraseñas, Client Secret, tokens, authorization codes, state ni verifier. El dominio de Cloudflare es temporal; si cambia, actualizar el retorno de la consulta.

- El usuario informa que el error ocurre después de ingresar con el vendedor desde `https://todospara.uno/prueba-mp/`. Falta conservar el texto exacto del rechazo actual.
- Esta página es una prueba independiente: prepara el enlace de autorización y comprueba el retorno. No canjea códigos ni vincula cuentas a la plataforma. Los cambios de renovación y la base de la plataforma no intervienen en esa prueba.
- La página respondió HTTP 200. Se preparó un intento en una sesión técnica propia, sin ingresar con vendedores ni autorizar cuentas.
- El enlace generado usa `https://auth.mercadopago.com.ar/authorization`, Client ID `6921561117397304`, `response_type=code`, `platform_id=mp` y `redirect_uri=https://todospara.uno/prueba-mp/retorno.php`. Incluye state y challenge de 43 caracteres con método S256. No se guardaron sus valores.
- El par de credenciales actual respondió HTTP 200 con `client_credentials`. La consulta de metadatos también respondió HTTP 200 y confirmó el mismo retorno en `callback_urls`, `use_pkce=true`, permisos `read`, `write`, `offline_access`, `active=true`, `blocked=false` y `disabled=false`.
- Los metadatos conservan `sandbox_mode=true` y `allow_flow=[]`; no hay evidencia suficiente para atribuirles el rechazo ni modificarlos.
- La configuración local apunta a otro retorno, en un túnel que no resuelve. Esa diferencia requiere corregirse si se retoman las pruebas locales, pero no explica por sí sola el rechazo del enlace correcto que genera el hosting.
- La base local contiene `scope` y `requiere_reautorizar`.

Próximo dato necesario: texto o captura del error de Mercado Pago después del ingreso del vendedor, incluyendo cualquier identificador de incidente visible. No compartir contraseñas, códigos de autorización, tokens, state ni verifier. Con ese dato se puede consultar al soporte por la habilitación de Authorization Code con PKCE y vendedor de prueba para la aplicación actual, sin atribuir el problema a un campo no documentado.

Los apartados siguientes corresponden a la aplicación anterior y a túneles históricos.

## Reintento con túnel restablecido (31/10, hora argentina)

- PHP volvió a ejecutarse en `localhost:8000`. Se restableció el túnel y se actualizó la configuración privada a `https://manuals-disk-relating-answered.trycloudflare.com`.
- El sitio respondió HTTP 200. Una consulta nueva de metadatos confirmó que `callback_urls` contiene exactamente `https://manuals-disk-relating-answered.trycloudflare.com/mercadopago-retorno.php` y `use_pkce=true`.
- La validación de credenciales con `client_credentials` y la consulta de metadatos respondieron HTTP 200. La aplicación continúa activa, sin bloqueo informado; conserva `sandbox_mode=true` y `allow_flow=[]`. Estos campos no bastan para identificar la causa.
- El usuario informó el mismo error con otro vendedor y precisó que aparece después de ingresar las credenciales del vendedor. No se recibieron nuevos callbacks de esos intentos.
- A continuación se comprobó el callback sin parámetros: HTTP 303. La llegada de `2026-11-01T00:20:41Z` corresponde a esa comprobación técnica, no a una autorización de Mercado Pago.
- El usuario confirmó que la aplicación se creó desde su cuenta real. Queda por obtener la causa concreta del rechazo del autorizador. No se modificaron permisos, PKCE ni el entorno de pagos.

Los apartados siguientes conservan las verificaciones anteriores; su dominio de túnel es histórico.

## Error observado

Mercado Pago muestra “La aplicación no está preparada para conectarse a Mercado Pago” al intentar autorizar con un vendedor de prueba. El sitio genera el enlace y Mercado Pago permite llegar al ingreso, pero no se completa el consentimiento ni se recibe el código de autorización.

## Verificaciones realizadas

- Client ID confirmado en Credenciales → Producción: `3176742631675868`.
- Se verificó el par Client ID/Client Secret mediante el grant `client_credentials`: HTTP 200. Esto confirma las credenciales para recursos propios, no que el flujo Authorization Code para terceros esté habilitado.
- Se consultó `GET https://api.mercadolibre.com/applications/3176742631675868` con la autorización temporal de diagnóstico: HTTP 200.
- Los tokens temporales de diagnóstico no se mostraron ni se guardaron.
- No se crearon pagos, no se autorizaron cuentas de vendedores y no se modificó la aplicación de Mercado Pago por API.

Extracto de metadatos, sin credenciales:

```json
{
  "id": 3176742631675868,
  "site_id": "MLA",
  "active": true,
  "blocked": false,
  "disabled": false,
  "partial_blocked": {"blocked": false},
  "sandbox_mode": true,
  "allow_flow": [],
  "callback_url": "",
  "callback_urls": [
    "https://detective-mill-idea-interfaces.trycloudflare.com/mercadopago-retorno.php"
  ],
  "use_pkce": true
}
```

Los scopes incluyen `read`, `write`, `offline_access` y permisos de Mercado Pago. La captura del panel también muestra los tres permisos seleccionados.

El retorno correcto está en el campo plural `callback_urls`. El campo singular vacío no demuestra un error de configuración.

## Solicitud generada

```text
https://auth.mercadopago.com.ar/authorization
  ?client_id=3176742631675868
  &response_type=code
  &platform_id=mp
  &redirect_uri=https%3A%2F%2Fdetective-mill-idea-interfaces.trycloudflare.com%2Fmercadopago-retorno.php
  &state=VALOR_ALEATORIO_POR_INTENTO
  &code_challenge=SHA256_EN_BASE64URL_DEL_VERIFIER
  &code_challenge_method=S256
```

El enlace real va en una sola línea. El retorno está codificado una vez mediante `http_build_query()`, coincide con el registrado y no contiene parámetros de enrutamiento. No editar el enlace manualmente: state y verifier pertenecen a la sesión del navegador que inició la conexión.

## Configuración local

El entorno conserva `produccion=false`. El intercambio posterior de Authorization Code incluye `test_token=true` y `code_verifier`. Ese intercambio todavía no se alcanza cuando ocurre este rechazo; agregar test_token no prueba ni corrige por sí solo la causa del mensaje de autorización.

## Consulta concreta para Mercado Pago

> En la aplicación 3176742631675868 (Argentina, Checkout Pro Orders), el par de credenciales funciona con client_credentials. La API de aplicaciones indica active=true, blocked=false, disabled=false, use_pkce=true y callback_urls contiene exactamente el retorno enviado, URL-encoded. Sin embargo, al ingresar con el vendedor de prueba en el enlace de Authorization Code aparece “La aplicación no está preparada para conectarse a Mercado Pago”.
>
> La API también devuelve sandbox_mode=true y allow_flow=[]. ¿Qué significan estos campos para una aplicación nueva de Mercado Pago con Orders? ¿Está habilitada para Authorization Code con PKCE y vendedores de prueba, o requiere habilitación/configuración adicional? ¿Qué dato o identificador del error necesitan para verificar el rechazo del lado del autorizador?

La consulta no requiere compartir Client Secret, Access Tokens, contraseñas, state ni code_verifier. No se debe cambiar sandbox_mode o allow_flow mediante una API no documentada: falta confirmación sobre su significado y la forma admitida de configurarlos para Mercado Pago.

## Prueba de llegada al callback

`public_php/mercadopago-retorno.php` registra la llegada antes de validar sesión,
state o PKCE cuando existe `.respaldos-locales/mp-oauth-diagnostico.activo`.
El registro privado `.respaldos-locales/mp-oauth-llegadas.log` contiene únicamente
la fecha UTC y el evento fijo `callback_recibido`. No registra parámetros, códigos,
IP, cookies ni cabeceras. Ambos archivos quedan fuera de la raíz pública y de Git.
Eliminar el archivo de activación desactiva el diagnóstico.

Se comprobó el acceso por el túnel con una solicitud sin parámetros: HTTP 303 y
una línea registrada. Esa llamada de control no es un retorno de Mercado Pago.
El punto de comparación queda en `.respaldos-locales/mp-oauth-inicio-prueba.txt`.
Para el intento real, iniciar desde el botón de conexión en el sitio público,
sin abrir manualmente el callback. Comparar el número de líneas y su horario.
Una llegada por sí sola no demuestra una autorización exitosa ni identifica
su origen. La ausencia de llegadas solo es concluyente para esta prueba si
el túnel y el registro siguen funcionando.

Este registro mínimo no modifica la configuración de logs del servidor PHP o
del proxy; estos podrían registrar URLs completas independientemente de él.

## Referencias

- [Flujos OAuth de Mercado Pago](https://www.mercadopago.com.ar/developers/es/docs/security/oauth/creation)
- [Consulta de metadatos de aplicaciones](https://developers.mercadolibre.com.ar/gestiona-tus-aplicaciones)

Los metadatos anteriores describen la aplicación consultada en esta fecha. No prueban, por sí solos, cuál es la regla de autorización que rechazó al vendedor de prueba.
