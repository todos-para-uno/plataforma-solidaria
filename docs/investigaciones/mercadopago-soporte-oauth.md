# Caso para soporte: OAuth rechaza el retorno registrado

## Solicitud lista para enviar

Aplicación **todosparauno**, ID **6921561117397304**, titular **3536116**, Argentina.

Al autorizar un vendedor de prueba, Mercado Pago responde HTTP **400** y muestra «La aplicación no está preparada para conectarse a Mercado Pago». Reprodujimos el fallo desde un navegador separado, iniciando sesión con el vendedor de prueba. El error ocurre antes del consentimiento y antes de recibir un código OAuth.

La respuesta de la página (`script#__NORDIC_RENDERING_CTX__`) identifica expresamente:

- `type: invalid_redirect_uri`.
- `error_type: OauthParamsValidator invalidRequest`.
- Flujo: `appstore`.

El retorno recibido por el autorizador y el devuelto por la API autenticada de la aplicación coinciden exactamente:

```text
https://mean-councils-camcorders-equity.trycloudflare.com/mercadopago-retorno.php
```

Tiene 81 caracteres, no está codificado dos veces y no contiene parámetros variables. El autorizador recibe Client ID 6921561117397304, response_type=code, platform_id=mp, PKCE S256 y challenge de 43 caracteres.

### Referencias para buscar en logs

| Variante | Código visible | X-Request-ID |
| --- | --- | --- |
| Solicitud estándar con PKCE y platform_id=mp, luego del login | PSO16-POQWN8VDDLY8 | 473a3689-be51-46de-b5dd-0050514e743c |
| Permisos explícitos read write offline_access | PSO16-KB2Y03KUTLA5 | Disponible en captura de diagnóstico local |
| Sin platform_id, manteniendo los demás parámetros | PSO16-G1Y0AWQ6BQNJ | 26479b59-6dcc-459b-90d8-e36ec67e2336 |

Pruebas reproducidas el **2026-11-04**, con evidencia consolidada a las **16:28:58 UTC**. Las variantes fueron intentos de diagnóstico y no concedieron permisos.

### Estado remoto comprobado con autenticación

`GET https://api.mercadolibre.com/applications/6921561117397304` respondió 200 y devolvió:

- origin=MP, active=true.
- blocked=false, disabled=false, partial_blocked.blocked=false.
- use_pkce=true.
- callback_urls contiene exactamente el retorno anterior.
- callback_url está vacío.
- scopes incluye read, write, offline_access, payments y permisos de Preferences, pagos y Orders.
- allow_flow=[] y certification_status=not_certified. No se presupone que esos valores expliquen el error.

`GET /applications/6921561117397304/grants` confirmó total=0.

El flujo client_credentials de la aplicación respondió HTTP 200. Un intercambio de authorization_code con un código deliberadamente sintético respondió HTTP 400 invalid_grant, sin invalid_client ni unsupported_grant_type. Esta prueba no sustituye un consentimiento válido.

### Prueba de corrección reversible intentada

OPTIONS del recurso documenta PUT /applications/:id y los atributos callback_url y callback_urls. Se intentó completar únicamente callback_url con la misma URL que ya figura en callback_urls. La API rechazó la actualización con **403**. Un GET posterior verificó callback_url vacío y callback_urls sin cambios. No se cambiaron credenciales, scopes, PKCE ni configuración de cobros.

### Lo que necesitamos que revisen

1. ¿Qué regla produce invalid_redirect_uri cuando la URL recibida coincide exactamente con callback_urls?
2. ¿El flujo appstore/OauthParamsValidator está leyendo el campo callback_url vacío en lugar de callback_urls? Es una hipótesis a verificar, no una causa confirmada.
3. ¿Hay alguna restricción adicional sobre ese dominio HTTPS temporal o sobre la relación entre la aplicación integradora y los vendedores de prueba?
4. ¿Cómo puede corregirse el registro del retorno si la edición del panel actualiza callback_urls y la actualización del campo singular por API responde 403?

## Alcance y pruebas que ya no hace falta repetir

El webhook externo payment respondió 200 con firma válida. El servidor HTTPS responde, la migración está aplicada y las 128 comprobaciones de pagos/OAuth locales pasan. El cliente OAuth y el endpoint de tokens aceptan las credenciales de la aplicación.

El usuario ya probó distintos vendedores, ventanas privadas y ambos dominios de autorización; también existía una aplicación anterior que fallaba. No se propone crear otra aplicación ni cambiar Orders/Preferences como diagnóstico de este rechazo anterior al consentimiento.

No se han realizado cobros reales. La aplicación local conserva produccion=false. No se alteraron flags de disponibilidad o certificación de la aplicación.

## Evidencia y fuentes

- [Resumen de metadatos y pruebas sin secretos](mercadopago-oauth-evidencia.json).
- Captura de la página de error disponible localmente en `/tmp/solidaria-oauth-error.png`.
- [Recurso oficial de consulta de aplicaciones](https://developers.mercadolibre.com.ar/gestiona-tus-aplicaciones).
- [OAuth de Mercado Pago](https://www.mercadopago.com.ar/developers/es/docs/security/oauth/creation).

Documento preparado para revisión y envío manual. **No enviado a soporte**. No adjuntar códigos OAuth, state, verifier, cookies, contraseñas o tokens.
