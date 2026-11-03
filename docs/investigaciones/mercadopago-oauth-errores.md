# Investigación: `invalid_redirect_uri` y `OauthParamsValidator invalidRequest`

Fecha: 2026-11-03. Investigación realizada con el MCP de Mercado Pago, herramienta `search_documentation`, país `MLA` (Argentina), idiomas español e inglés.

## Resultado

La documentación recuperada exige que `redirect_uri` sea estática y coincida exactamente con una URL registrada en la aplicación. Es la primera comprobación propuesta para `invalid_redirect_uri`. Sin embargo, los resultados no contienen una definición literal de ese código ni de `OauthParamsValidator invalidRequest`: no permiten afirmar que ambos mensajes tengan la misma causa. [S1, S2]

No se reprodujo el incidente ni se consultó la configuración de la aplicación remota. El resultado es una guía de diagnóstico basada en fuentes oficiales, no una causa raíz confirmada para esta instalación.

### Comprobación posterior del túnel

El 2026-11-03 se comprobó la URL pública presente en la configuración local: `https://clone-rosa-showers-smell.trycloudflare.com`. `curl -I --max-time 20` falló con `Could not resolve host`, también fuera del sandbox. Como control, `https://www.cloudflare.com` respondió HTTP 200 desde el mismo entorno sin restricciones. Esto confirma un fallo de resolución del dominio configurado desde esta máquina en ese momento; es compatible con un túnel temporal que dejó de estar disponible.

Esta observación explica que el callback sea inaccesible, pero por sí sola no demuestra el origen de `invalid_redirect_uri`: sigue pendiente comparar la URL enviada con la registrada. Al restablecer el túnel, si la dirección cambia, habrá que actualizar la configuración local y el callback completo registrado en Mercado Pago.

## Hallazgos y alcance de la evidencia

### Nuevo intento del 2026-11-03

El usuario precisó después la secuencia: el botón de conexión abre Mercado Pago sin error; tras ingresar las credenciales en Mercado Pago aparece un signo de exclamación rojo, el texto «La aplicación no está preparada para conectarse a Mercado Pago» y un botón «IR AL INICIO». Es un mensaje observado por el usuario, sin estado HTTP ni respuesta técnica capturados. También observó un POST a `/jms/lgz/background/automation/experimental`; no se confirmó que ese POST causara el rechazo. La búsqueda literal del mensaje mediante MCP devolvió documentación general, sin una explicación específica. No debe interpretarse como prueba de que falte habilitar producción.

El usuario confirmó que utiliza una cuenta de prueba, que la URL registrada es correcta y que PKCE está habilitado. Estos puntos se consideran comprobados por el usuario; no volver a solicitarlos sin evidencia nueva que lo justifique. La revisión del código muestra que `test_token` se envía únicamente en el intercambio POST `/oauth/token`, posterior al callback: modificar ese campo no constituye una corrección justificada para un rechazo anterior al retorno. El endpoint argentino `/authorization` también aparece en la [guía oficial de configuración de Split](https://www.mercadopago.com/developers/es/docs/split-payments/split-1-1/integration-configuration/create-configuration), recuperada mediante MCP; no hay evidencia para atribuir el problema al dominio regional usado por el código. No se confirmó aún el rol de la cuenta de prueba ni una restricción de Mercado Pago que explique el mensaje.

A las 18:19 UTC, tras el nuevo intento informado por el usuario, el registro local no mostraba llegadas posteriores a las 18:12:24 UTC. Las entradas de 18:12:14 y 18:12:24 correspondían a comprobaciones del agente, no a una autorización completada. Por tanto, no hay evidencia registrada de que este nuevo intento alcanzara el callback.

`application_list` del MCP devolvió dos aplicaciones: `todosparauno old` y `todosparauno`. Se comparó localmente el `client_id` configurado con esos resultados: corresponde a `todosparauno`, es numérico y no tiene espacios alrededor. El callback construido es `https://ohio-monica-pool-sufficiently.trycloudflare.com/mercadopago-retorno.php`. No se consultaron secretos con el MCP ni se verificó remotamente la lista de callbacks registrados. Sigue pendiente obtener el mensaje exacto del nuevo rechazo y comparar el callback registrado en esa aplicación.

| Tema | Confirmado por las fuentes | Interpretación para investigar |
| --- | --- | --- |
| URL de retorno OAuth | Debe coincidir exactamente con la registrada y ser estática. La información variable se transporta mediante `state`. [S1, S2] | Comparar esquema, dominio, puerto, ruta, barra final, espacios y parámetros. Estas diferencias son ejemplos de diagnóstico derivados de la regla de coincidencia; no un catálogo oficial del error. |
| Identidad de aplicación | `client_id` es el número de la aplicación. [S2] | Confirmar que la URL se registró en la misma aplicación identificada por ese valor. |
| Autorización en navegador | La guía muestra `GET https://auth.mercadopago.com/authorization` con `client_id`, `response_type=code`, `platform_id=mp`, `state` y `redirect_uri`. [S2] | Si el rechazo aparece antes de recibir `code`, revisar primero esta solicitud y la configuración de la aplicación. |
| Intercambio del código | Se realiza mediante `POST https://api.mercadopago.com/oauth/token`. Los parámetros van en el cuerpo; deben cuidarse headers y campos enviados. [S1, S2] | Si el fallo aparece en el POST, revisar su cuerpo y respuesta completos antes de atribuirlo al enlace de autorización. |
| PKCE | Al habilitarlo se requieren los parámetros de PKCE. La URL de ejemplo usa `code_challenge` y `code_challenge_method`; el intercambio utiliza `code_verifier`. [S2] | Verificar si está habilitado y si los valores corresponden al mismo intento. |
| Código temporal | `authorization_code` dura 10 minutos y se utiliza una sola vez. [S3] | Tras corregir la solicitud, iniciar una autorización nueva en lugar de reutilizar un código consumido o vencido. |

`OauthParamsValidator invalidRequest` sugiere por su nombre un rechazo de validación de parámetros, pero **eso es una inferencia**, no una definición encontrada en Mercado Pago. Tampoco se comprobó su equivalencia con el código OAuth `invalid_request`. Sin respuesta HTTP, endpoint y parámetros del intento no se puede identificar qué campo falló.

La búsqueda literal de `invalid_redirect_uri` devolvió páginas de Checkout sobre redirecciones y `back_urls`. No explican el error de OAuth y no se usaron para establecer su causa.

## Procedimiento propuesto

### Diagnóstico temporal implementado

En `config/mercadopago.local.php` se activó `'diagnostico_oauth' => true`; el valor predeterminado compartido es `false`. Para desactivarlo, cambiar esa opción local a `false`.

Al pulsar Conectar Mercado Pago, se muestra el endpoint, Client ID, callback decodificado, parámetros públicos y resultados de validación de state y PKCE calculados a partir del enlace generado. No se muestran la URL completa, state, verifier, challenge ni secretos. El botón Continuar conserva el mismo intento en la sesión del servidor, exige CSRF y respeta su vencimiento de diez minutos. La pantalla no consulta ni valida la configuración remota de Mercado Pago.

Se comprobó por HTTP local con la cuenta de demostración: pantalla de diagnóstico correcta, callback esperado, verificaciones sin fallos y continuación HTTP 303 al endpoint OAuth. La prueba no siguió la redirección hacia Mercado Pago ni autorizó una cuenta externa. Pasaron las nuevas comprobaciones del controlador de ocultación de valores sensibles, continuación de un solo uso, usuario y vencimiento. La suite completa de ese controlador terminó más adelante por una incompatibilidad de la base existente: `donacion.id_usuario` no admite NULL, requerido por el caso de invitado. No se modificó ese esquema para este diagnóstico.

El usuario aportó una captura del intento de las 18:56:27 UTC del 2026-11-03: endpoint `https://auth.mercadopago.com.ar/authorization`, Client ID `6921561117397304`, callback `https://ohio-monica-pool-sufficiently.trycloudflare.com/mercadopago-retorno.php`, `response_type=code`, `platform_id=mp`, método `S256` y entorno local Prueba. Todas las comprobaciones locales aparecen como Correcto. El callback coincide visualmente con el de la captura anterior del panel de Mercado Pago. No se observa una discrepancia en esos datos; no se ha demostrado que la solicitud fuera aceptada por Mercado Pago ni se ha capturado todavía su motivo técnico de rechazo para este intento.

El usuario informó que continuar ese intento produjo el mismo rechazo y señaló la diferencia entre el dominio regional usado por el código y el endpoint de la [guía general de OAuth](https://www.mercadopago.com/developers/es/docs/security/oauth/creation). Se cambió exclusivamente el endpoint de autorización a `https://auth.mercadopago.com/authorization` para probar esta hipótesis. La guía de Split también publica el dominio argentino. Se verificó por HTTP local que la continuación apunta al dominio general. El usuario repitió la prueba e informó el mismo error: el cambio de dominio no resolvió el incidente y no debe proponerse nuevamente como solución ya comprobada.

Confirmaciones adicionales del usuario: también probó con dos vendedores distintos y en ventanas privadas; las credenciales de producción están disponibles, y la captura de configuración muestra URL, PKCE y permisos de lectura, escritura y acceso offline correctos. No repetir esas hipótesis como pendientes sin nueva evidencia.

Hipótesis evaluada y descartada por la evidencia posterior del usuario: habilitación pendiente del entorno/credenciales de la aplicación integradora. La [documentación de credenciales](https://www.mercadopago.com/developers/es/docs/your-integrations/credentials), recuperada nuevamente por MCP, sitúa `Client ID` y `Client Secret` en las credenciales de producción y describe su activación. Esto no prueba que la activación sea requisito para autorizar vendedores de prueba ni explica oficialmente el mensaje observado. La aplicación `todosparauno` mostró las credenciales de producción disponibles; no quedó una activación pendiente demostrada. No cambiar `produccion=false`, no sustituir las cuentas de prueba ni realizar cobros reales para comprobarla.

1. Identificar dónde aparece el error: apertura de `/authorization`, callback o POST a `/oauth/token`. Conservar fecha, estado HTTP, mensaje completo y cualquier identificador de solicitud.
2. Comparar la URL registrada con el valor de `redirect_uri` decodificado de la solicitud de autorización y con el enviado en el intercambio. Usar el mismo callback estático en ambos pasos. Revisar que la codificación del enlace no altere el valor ni lo codifique dos veces. Esta última revisión es una recomendación técnica, no una causa documentada del mensaje.
3. Comprobar `client_id` y la configuración de PKCE en esa aplicación. La guía presenta una inconsistencia de nombres: un párrafo dice `code_method`, mientras que la URL y la explicación del parámetro usan `code_challenge_method`. Tomar esta discrepancia en cuenta al revisar la implementación. [S2]
4. Para el POST de token, verificar `grant_type=authorization_code`, credenciales de esa aplicación, código reciente, `redirect_uri` y, si corresponde, `code_verifier`. La indicación de no usar query params en buenas prácticas se refiere al POST de token; la autorización del navegador sí los usa. [S1, S2]
5. Si persiste, enviar a soporte de Mercado Pago la evidencia del intento y la comparación de URLs, ocultando secretos, tokens y códigos. Pedir específicamente qué validación dispara `OauthParamsValidator invalidRequest`.

## Fuentes

- **S1 — [Buenas prácticas de integración de OAuth](https://www.mercadopago.com/developers/es/docs/security/oauth/best-practices)**. Recuperada mediante MCP. Sustenta coincidencia exacta, URL estática, `state`, headers y ubicación de parámetros del POST.
- **S2 — [Obtener Access Token](https://www.mercadopago.com/developers/es/docs/security/oauth/creation)**. Recuperada mediante MCP. Sustenta el flujo de autorización, identificación de aplicación, intercambio y PKCE.
- **S3 — [OAuth: introducción](https://www.mercadopago.com/developers/es/docs/subscriptions/additional-content/security/oauth/introduction)**. Recuperada mediante MCP; es una variante oficial bajo Suscripciones. Sustenta vigencia y uso único del código.
- **Referencia enlazada por S1/S2 — [POST OAuth token](https://www.mercadopago.com/developers/es/reference/authentication/oauth/_oauth_token/post)**. Se intentó abrir como verificación complementaria, pero la herramienta web no pudo acceder. No se atribuyen hallazgos a contenido de esta referencia que no se haya recuperado.

## Registro de consultas reproducibles

Todas se ejecutaron con `siteId=MLA` en `search_documentation`. Los resultados son búsquedas semánticas: devolver una página no demuestra que contenga el término literal.

| Idioma | Consulta | Límite | Resultado relevante |
| --- | --- | --- | --- |
| es | `invalid_redirect_uri` | 10 | Una página de Checkout sobre apertura externa; sin definición literal. |
| es | `OauthParamsValidator invalidRequest` | 10 | Introducción a OAuth; sin explicación literal. |
| es | `OauthParamsValidator` | 20 | Introducción a OAuth; sin explicación literal. |
| es | `invalidRequest` | 20 | Dos páginas de reembolsos; sin utilidad para el diagnóstico OAuth. |
| es | `OAuth redirect_uri errores invalid_redirect_uri` | 20 | Incluyó S1 y S2. |
| es | `OAuth buenas prácticas redirect_uri` | 20 | S1 como primer resultado. |
| es | `OAuth obtener Access Token redirect_uri` | 20 | S2 como primer resultado. |
| en | `invalid_redirect_uri` | 50 | Dos páginas de retorno de Checkout; sin definición literal. |
| en | `OauthParamsValidator invalidRequest` | 50 | Sin resultados. |
| en | `oauth token invalid_request redirect_uri` | 50 | Flujo y buenas prácticas; sin definición del mensaje del validador. |

Como complemento, una búsqueda web restringida a `mercadopago.com` de los términos literales `OauthParamsValidator` e `invalid_redirect_uri` no devolvió resultados. La ausencia de resultados no prueba que el error no exista ni que no tenga documentación interna.
