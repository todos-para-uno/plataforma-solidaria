# Diagnóstico del rechazo de autorización OAuth

Fecha: 31 de octubre de 2026.

## Resumen de continuidad — actualizado el 12 de noviembre de 2026

**Leer este apartado antes de pedir más pruebas.** El usuario pidió conservar
lo ya intentado para que futuras sesiones de Codex no le hagan repetir pasos.
Los apartados inferiores conservan historia: sus URLs temporales, pendientes
y aplicaciones anteriores no describen necesariamente el estado actual.

### Estado actual

- Línea de tiempo con una fila por fecha, solicitada por la usuaria:
  `docs/investigaciones/mercadopago-linea-de-tiempo.md`. Las fechas sin pruebas
  documentadas se marcan sin registro, sin inferir inactividad. La usuaria
  confirmó el envío manual de la respuesta al ticket el 12/11; Codex no envió
  mensajes a soporte. No se conoce aquí la fecha exacta de apertura del ticket.

- Seguimiento de soporte: después de subir el paquete revisado, el usuario
  informó persistencia del mismo mensaje y recibió respuesta al ticket. Soporte
  confirma el retorno actual `/prueba-mp/retorno.php`, distingue URLs históricas y
  solicita el enlace de autorización actual completo con parámetros públicos,
  sin code/verifier/secret/tokens y sin otra autorización. Se preparó borrador en
  `docs/investigaciones/mercadopago-respuesta-soporte-hosting.md`; no se envió.
  Una consulta posterior al hosting preparó un intento en sesión técnica propia
  y confirmó nuevamente el mismo Client ID, retorno y S256; no se abrió el
  autorizador ni se conservaron state/challenge. Ahora sí fue informado el
  despliegue del paquete por el usuario, sin comparación remota de hashes.

- Bloqueo sin resolver: rechazo OAuth después del ingreso del vendedor de
  prueba, antes de completar el consentimiento/retorno. No hay evidencia de una
  autorización exitosa en esta sesión.
- Mensaje visible: «La aplicación no está preparada para conectarse a Mercado Pago».
- Error interno aportado por el usuario: `type=invalid_redirect_uri`,
  `pageCode.id=invalidRequest`, `contextNumber=16`,
  `uniqueErrorCode=AXPNOTUHLWK4`.
- Client ID actual: `6921561117397304`.
- Prueba independiente: `https://todospara.uno/prueba-mp/`.
- Retorno OAuth: `https://todospara.uno/prueba-mp/retorno.php`.
- El usuario informa que usa Orders y creó la aplicación desde su cuenta real
  habitual. No tiene otra cuenta real para comparar.
- El hosting únicamente prepara OAuth y comprueba la recepción del retorno:
  no canjea el código, no vincula cuentas a la plataforma ni crea pagos.
- El checkout local conservaba Preferences según el registro anterior. No se
  verificó ni implementó una migración a Orders en esta sesión. No confundir
  este antecedente con la API que el usuario informa para su integración.
- El usuario informó que llevaba 12 días sin respuesta de soporte y quiere
  avanzar con investigación propia. No se envió ningún mensaje a soporte desde
  esta sesión.
- Revisión técnica autónoma posterior: ver
  `docs/investigaciones/mercadopago-revision-tecnica-20261112.md`. En navegador
  limpio se comprobó ausencia de parámetros duplicados y llegada al login;
  también llegaron al login controles con retorno no registrado y Client ID 0.
  Llegar al login no demuestra validación previa de esos datos. No hay todavía
  una corrección demostrada. Se preparó un analizador local de HAR que solo
  emite campos permitidos; no pedir nuevas copias manuales de URLs al usuario.
- Backend del hosting: se encontró después una copia de su paquete en
  `.respaldos-locales/prueba-mp-hosting/publico/`. Se revisó y se ejecutó con el
  vector público de RFC7636: seis verificaciones correctas de PKCE, retorno,
  state y escape HTML. No se confirmó igualdad byte a byte con el despliegue.
  No pedir al usuario estos fuentes sin consultar antes el respaldo. Las pruebas
  de seguridad locales pasaron; las del controlador no completaron por permisos
  de BD. Detalle y límites en el informe técnico, apartado backend.

### Pruebas y comprobaciones ya realizadas

| Prueba | Evidencia y resultado | Evitar repetir |
|---|---|---|
| Hosting HTTPS en lugar de túneles locales | El usuario reproduce el rechazo en todospara.uno; la página responde y muestra el retorno correcto. Los túneles también fallaron históricamente. | No proponer pasar a hosting HTTPS como si fuera nuevo. |
| Cambio del retorno en el panel | Usuario informa haber registrado el retorno del hosting. | No pedir nuevamente que copie la URL básica. |
| Lectura autenticada de la aplicación el 12/11 | `GET /applications/6921561117397304`: HTTP 200; `callback_urls` contiene exactamente el retorno del hosting; comparación literal positiva. | No afirmar que hay diferencia de URL sin nueva evidencia. |
| Estado de la aplicación el 12/11 | `active=true`, `blocked=false`, `disabled=false`, `use_pkce=true`, `site_id=MLA`. | No tratar bloqueo o PKCE desactivado como hechos. |
| Validación técnica de credenciales el 12/11 | Grant `client_credentials`: HTTP 200. No demuestra que Authorization Code funcione. | No confundir credenciales válidas con consentimiento OAuth exitoso. |
| Preparación independiente en el hosting el 12/11 | Enlace a `https://auth.mercadopago.com.ar/authorization`, Client ID correcto, `response_type=code`, `platform_id=mp`, retorno exacto, state de 64 caracteres, challenge de 43 y método S256. Sin ingreso ni autorización de cuentas por Codex. | No diagnosticar state/PKCE faltantes por el enlace censurado compartido por el usuario. |
| Inspección del error en Firefox | Captura con solicitud `/authorization` HTTP 400; el usuario extrajo los campos internos consignados arriba. | No pedir otra vez encontrar la solicitud o copiar el mismo error. |
| Autorizador global `.com` en lugar de `.com.ar` | Se indicó preparar intento nuevo y cambiar solo el host, conservando parámetros. El usuario informó el mismo error. No aportó campos internos nuevos para esa variante. | No volver a recomendar cambiar el dominio como solución. |
| Eliminar y volver a registrar el mismo retorno | Se indicó quitar URL, guardar, agregarla de nuevo, guardar y mantener PKCE. El usuario informó que sigue el mismo error. No hubo lectura autenticada posterior a esta última operación. | No volver a recomendar este ciclo sin evidencia nueva. |
| Consulta automatizada del autorizador sin sesión | HTTP 403. No reproduce ni explica el HTTP 400 del navegador autenticado. | No usar ese 403 como diagnóstico del intento del usuario. |
| Retornos y encoding históricos | Se comprobaron coincidencia byte a byte y codificación única para túneles previos; sin espacios ni doble encoding detectados. | No insistir en doble encoding como causa demostrada. |
| Variantes históricas del enlace | Registro previo del 4/11: scopes mínimos explícitos y omisión de `platform_id` también fallaron. | Consultar evidencia histórica antes de repetir variantes. |
| Modificación histórica del campo singular `callback_url` | Intento previo por API: HTTP 403, sin cambio persistido. El plural `callback_urls` sí contiene el retorno. | No repetir escrituras no documentadas ni afirmar que el singular vacío explica la causa. |
| Authorization Code sintético, histórico | HTTP 400 `invalid_grant`, sin `invalid_client` ni `unsupported_grant_type`. | No equivale a probar un código válido ni el flujo de consentimiento. |
| Otro vendedor de prueba, histórico | El registro del 31/10 informa el mismo error con otro vendedor. | No presentarlo como comparación inédita. |

### Propuestas no realizadas y datos no disponibles

- Cuenta real diferente de la propietaria: propuesta para comparar, **no
  realizada**. El usuario no tiene otra cuenta. No convertirlo en requisito ni
  pedirle crear una cuenta nueva.
- Ventana privada con la cuenta real propietaria: **realizada**; el usuario
  confirmó el mismo mensaje después de ingresar sus credenciales. El tipo
  interno de ese intento concreto no se capturó; ver detalle abajo.
- No se recibió hora exacta del error del 12/11, nickname del vendedor ni un
  nuevo `x-request-id`. No es necesario volver a pedirlos para identificar el
  tipo de error, que ya está confirmado.
- No hay una corrección comprobada ni evidencia suficiente para decidir si el
  origen es una condición interna de la aplicación, del autorizador o de la
  cuenta. El rechazo no prueba por sí solo una incompatibilidad de Orders.
- No modificar `sandbox_mode`, `allow_flow`, certificación ni el campo singular
  vacío basándose únicamente en conjeturas.

### Cómo continuar sin volver a empezar

Paquete preparado a pedido del usuario para igualar el hosting al fuente
revisado: `output/mercadopago/prueba-mp-revisada-20261112.zip`. Contiene únicamente
`index.php`, `retorno.php`, `config.php` en la raíz, idénticos byte a byte a
`.respaldos-locales/prueba-mp-hosting/publico/`. Se verificaron nombres, contenido
e integridad del ZIP; hashes aparte en el archivo `.sha256.txt` junto al ZIP.
Configuración solo pública: Client ID actual y base `https://todospara.uno/prueba-mp`.
Destino: carpeta remota que ya sirve `/prueba-mp/`, con copia previa de sus tres
archivos. No subir plataforma completa ni configuración privada local. La
instalación remota aún no fue confirmada; no afirmar que ya se desplegó ni que
el paquete corrige el rechazo de MP. Tras reemplazar, preparar un intento nuevo
sin editar el enlace, usando el retorno original `/prueba-mp/retorno.php`.

Última revisión autónoma: leer
`docs/investigaciones/mercadopago-revision-tecnica-20261112.md` antes de proponer
intervenciones. Para la incertidumbre de la última solicitud se preparó
`utilidades/diagnosticar_oauth_har.py`, verificado con datos sintéticos para omitir
secretos e identificar duplicados/continuaciones. No se ejecutó sobre una traza
autenticada real. No pedir al usuario el HAR original ni repetir el recorrido
si ya existe una captura utilizable. Los archivos HAR se excluyeron de Git.

Nuevo control **realizado** a pedido del usuario («probemos algo distinto»): usar
su cuenta real habitual, **propietaria de la aplicación**, en lugar del vendedor
de prueba, manteniendo aplicación, URI original y PKCE. El usuario informó que
ahora el error aparece inmediatamente al pulsar conectar, antes de mostrar el
formulario de credenciales. No se recibió el tipo interno ni código de incidente
de este nuevo intento, y no se verificó qué cuenta reconoció el autorizador.
Si ya había una sesión real abierta, la ausencia de formulario es compatible con
reutilización de esa sesión; no prueba rechazo anterior a identificar la cuenta
ni demuestra todavía un cambio de causa. Falta clasificar el error de este
intento nuevo (sin pedir nuevamente URL/Client ID ya comprobados).
Después el usuario abrió una ventana privada, preparó la prueba con sus datos
reales y confirmó que el mensaje «La aplicación no está preparada para conectarse
a Mercado Pago» aparece **después de ingresar sus credenciales reales**. No
hubo consentimiento exitoso informado. No se capturaron type/uniqueErrorCode
del intento real en privado; no atribuirle automáticamente el AXPNOTUHLWK4 del
intento anterior ni afirmar que su causa interna quedó comprobada. Este resultado
debilita una explicación exclusivamente ligada a vendedores de prueba, pero la
cuenta real es propietaria de la aplicación y no equivale a un tercero real.
No pedir repetir este control ni volver a preguntar si ocurrió antes/después.
Codex había descartado esta cuenta en la conversación sin aportar evidencia de
una prohibición; se corrige esa exclusión. No se afirma que la autoautorización
esté soportada ni que este control equivalga a autorizar a un tercero. El mismo
mensaje no descarta por sí solo un problema específico de cuentas de prueba
porque la cuenta propietaria puede recibir un tratamiento diferente. No requirió otra
cuenta, crear aplicaciones ni cambiar configuración. No pedir códigos de retorno.

Prueba de segunda URI **realizada, sin resolver el error**: el usuario agregó
`https://todospara.uno/prueba-mp/` sin quitar el retorno original y recibió la
indicación de preparar un intento nuevo, copiar el enlace y borrar únicamente
`retorno.php`. Informó «sigue apareciendo exactamente el mismo error». No aportó
otro cuerpo interno ni captura de los parámetros de esa variante; no afirmar
haber observado directamente la solicitud final ni un nuevo uniqueErrorCode.
Consulta autenticada posterior: HTTP 200 y `callback_urls` contiene **ambas**
direcciones exactas. PKCE sigue activo, active=true, blocked=false y
disabled=false; callback_url singular sigue vacío. Las credenciales técnicas
respondieron HTTP 200. Los temporales de esta consulta se eliminaron.
La prueba debilita una anomalía exclusiva del registro original, pero no prueba
por sí sola que toda la aplicación o el dominio estén bloqueados. No volver a
pedir agregar la segunda URI ni repetir esta variante sin evidencia nueva.
No se creó otra aplicación/cuenta, no se modificó la API de cobros ni se
completó el canje de códigos durante las consultas de Codex.

Corrección explícita del usuario sobre las filas de Red: estaba copiando la fila
**200**, no la 400. La URL `/session/replicator/...` corresponde a esa fila 200.
Luego seleccionó la fila **400** y aportó su URL sin query:
`https://auth.mercadopago.com.ar/authorization`.
Por tanto, el rechazo 400 sí ocurre en `/authorization`. Queda retirada la
conclusión provisional que lo ubicaba en replicator. No proponer cambios de
sesión/cookies basándose en aquella confusión. La prueba sugerida de ingresar
primero al vendedor y generar OAuth después fue retirada antes de su ejecución;
no registrarla como realizada. Falta extraer los parámetros de la fila 400 real
para completar la comparación actual después del login.

Comparación de navegación aportada después del análisis del 12/11:
el usuario compartió dos prefijos iguales de `/session/replicator/...`, con un
parámetro `go` que contiene la URL `/authorization`. No se conserva el
identificador de sesión. En ese contexto, `redirect_uri%3Dhttps%253A...` es
compatible con la codificación anidada normal: una capa para `go` y otra para
el redirect_uri del enlace OAuth interior. No demuestra doble encoding
incorrecto del generador. Los prefijos compartidos no permiten identificar
independientemente las solicitudes inicial y final: el usuario aclaró después
que copiaba la fila 200. No registrarlos como comparación concluyente de ambas
solicitudes ni como evidencia de que el 400 ocurre en replicator.

Análisis de hipótesis solicitado por el usuario el 12/11 (sin causas confirmadas):

- Posible discrepancia entre la configuración que expone la API y la que usa el
  autorizador. El campo singular vacío es un indicio para investigar, no una
  explicación demostrada ni autorización para editar campos internos.
- Posible transformación/pérdida de parámetros durante la ida y vuelta del login.
  La nueva comprobación útil sería comparar la solicitud inicial y la final que
  devuelve 400 en una misma sesión del navegador del usuario, extrayendo solo
  client_id, redirect_uri y presencia/método de PKCE. La preparación independiente
  de Codex no prueba por sí sola la conservación de parámetros tras ese login.
  Las comparaciones históricas exactas debilitan esta hipótesis.
- Posible restricción o inconsistencia de habilitación OAuth de la aplicación,
  su tipo o su relación con vendedores de prueba. active=true y client_credentials
  HTTP 200 no verifican Authorization Code; tampoco prueban esta hipótesis.
- Una regla adicional sobre la URI/dominio no documentada es posible, pero está
  debilitada por la reproducción tanto en túneles como en un dominio estable.
- Credenciales privadas ausentes en el hosting, webhook, base de datos, retorno
  del comprador y body de Orders no intervienen en la solicitud de autorización
  que ya devuelve el 400. No pedir configurar esos componentes como arreglo de
  este rechazo previo al callback.
- Ninguna fuente oficial consultada confirma que Orders o las cuentas de prueba
  expliquen este invalid_redirect_uri concreto. No cambiar de API ni crear otra
  cuenta/aplicación solo basándose en esta especulación.

1. Partir de la contradicción comprobada: `invalid_redirect_uri` con retorno
   exactamente registrado, incluso en hosting HTTPS estable.
2. Elegir una investigación que produzca evidencia nueva; antes de pedir una
   intervención al usuario, explicar qué variable cambia respecto de esta tabla.
3. No repetir enlaces censurados como supuestos enlaces incompletos: el usuario
   quitó state y challenge por indicación de Codex, no de la solicitud real.
4. Mantener separadas la recepción del callback, el canje del código, la creación
   de orders y la confirmación del pago. El rechazo actual impide alcanzar los
   pasos posteriores.

Referencias históricas complementarias:
`docs/investigaciones/mercadopago-oauth-evidencia.json` y
`docs/investigaciones/mercadopago-soporte-oauth.md`.

## Reintento en hosting (12 de noviembre de 2026)

- El usuario informa preparación a las 14:18:44 -03:00; no se recibió hora exacta del error.
- Respuesta del autorizador aportada por el usuario: `type=invalid_redirect_uri`, `contextNumber=16`, `uniqueErrorCode=AXPNOTUHLWK4`.
- Consulta autenticada actual: client_credentials HTTP 200; GET de aplicación 6921561117397304 HTTP 200.
- `callback_urls` contiene exactamente `https://todospara.uno/prueba-mp/retorno.php`; comparación literal positiva. `callback_url` singular vacío, sin atribuirle causalidad.
- `active=true`, `blocked=false`, `disabled=false`, `use_pkce=true`, `site_id=MLA`.
- El hosting genera ese mismo retorno y Client ID, state presente y PKCE S256. La consulta automatizada sin sesión al autorizador devolvió 403; no equivale al rechazo 400 del navegador del usuario.
- No se crearon pagos ni se modificó la aplicación remota. Los archivos temporales con credenciales del diagnóstico fueron eliminados.
- Comparación posteriormente realizada por el usuario: endpoint OAuth global documentado `https://auth.mercadopago.com/authorization`, conservando parámetros. Resultado informado: mismo error. También informó persistencia del error tras quitar y volver a registrar el retorno en el panel; ver resumen de continuidad.

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
