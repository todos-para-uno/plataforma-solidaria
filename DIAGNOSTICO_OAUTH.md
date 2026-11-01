# Diagnóstico del rechazo de autorización OAuth

Fecha: 31 de octubre de 2026.

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
