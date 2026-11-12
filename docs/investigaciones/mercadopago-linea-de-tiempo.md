# Línea de tiempo del bloqueo OAuth de Mercado Pago

Actualizada al 12/11/2026. Fechas en Argentina (UTC-03:00).
Una fila por día, reconstruida a partir del diagnóstico, informes y conversación.
«Sin registro fechado» significa que no tenemos evidencia para atribuir una
prueba a ese día; no significa que no se haya trabajado ni que soporte no haya
actuado. No se conoce aquí la fecha exacta de apertura del ticket.

| Fecha | Pruebas o acciones y resultado |
|---|---|
| 31/10/2026 | Se probó OAuth desde el entorno local mediante túnel HTTPS; se restableció el túnel, se actualizaron y contrastaron los retornos registrados, se verificaron credenciales y estado de la aplicación y se probó otro vendedor de prueba. Persistió el rechazo después del ingreso. Un acceso técnico al callback respondió 303 y dejó constancia de llegada, pero no fue una autorización de Mercado Pago. Estos registros incluyen la aplicación anterior. |
| 01/11/2026 | Sin registro de una prueba OAuth que permita asignarla con certeza a este día. |
| 02/11/2026 | Sin registro fechado de pruebas en la documentación disponible. |
| 03/11/2026 | Se documentó el rechazo tanto desde la prueba independiente del hosting como desde túneles HTTPS. Se restableció un túnel que había dejado de resolver; se revisaron Client ID, coincidencia exacta del retorno, codificación y PKCE. Firefox mostró HTTP 400 y se extrajo `invalid_redirect_uri`; incidente `PSO16-STO1XPWGSCWF`, request ID `fa1c898a-3d34-4885-b403-da434dcfbe3d`. El rechazo persistió pese a la coincidencia del retorno registrado. |
| 04/11/2026 | Se reprodujo el rechazo en navegador separado con vendedor de prueba; se verificaron estado, permisos, PKCE y retorno de la aplicación actual. Las variantes con permisos explícitos y sin `platform_id` tampoco funcionaron. La escritura del campo singular `callback_url` fue rechazada con 403 y no persistió. El canje con código sintético devolvió `invalid_grant`, sin probar un consentimiento válido. Se corrigió una validación local posterior al callback y pasaron 128 comprobaciones. El simulador de webhook de pagos respondió 200 con firma válida; eso no resolvió ni validó OAuth. Incidente principal: `PSO16-POQWN8VDDLY8`. |
| 05/11/2026 | Sin registro fechado de pruebas en la documentación disponible. |
| 06/11/2026 | Sin registro fechado de pruebas en la documentación disponible. |
| 07/11/2026 | Sin registro fechado de pruebas en la documentación disponible. |
| 08/11/2026 | Sin registro fechado de pruebas en la documentación disponible. |
| 09/11/2026 | Sin registro fechado de pruebas en la documentación disponible. |
| 10/11/2026 | Sin registro fechado de pruebas en la documentación disponible. |
| 11/11/2026 | Sin registro fechado de pruebas en la documentación disponible. |
| 12/11/2026 | Se volvió a probar el hosting con `https://todospara.uno/prueba-mp/retorno.php` y se confirmó por API su registro exacto; error interno `invalid_redirect_uri`, incidente `AXPNOTUHLWK4`. No resolvieron el rechazo: usar el autorizador `.com`, quitar/reagregar la URL y agregar un retorno alternativo del mismo hosting. La cuenta real propietaria también mostró el mensaje después del ingreso en ventana privada (sin captura de su error interno). Se revisó el PHP del paquete: sintaxis y seis comprobaciones de PKCE/enlace correctas; 26 pruebas locales de seguridad correctas y prueba del controlador detenida por permisos de BD. Se volvió a subir el paquete revisado y el usuario informó el mismo error. Los controles anónimos llegaron al login incluso con datos inválidos: llegar al login no demuestra aceptación. Soporte respondió confirmando el retorno actual y solicitando el enlace de autorización vigente; la usuaria confirmó que envió la respuesta. El bloqueo continúa sin causa raíz confirmada. |

## Contexto que debe acompañar la línea de tiempo

- Aplicación actual: `6921561117397304`; la aplicación anterior fue
  `3176742631675868`. No atribuir todos los incidentes históricos a una sola.
- Las URLs temporales de túneles son históricas. En el intento actual se envía
  `https://todospara.uno/prueba-mp/retorno.php`, que también confirma soporte.
- El fallo identificado ocurre en `/authorization`, antes del retorno al
  servidor. Los webhooks, las pruebas locales y el canje sintético no demuestran
  una autorización OAuth exitosa.
- Las variantes del 12/11 se informaron con el mismo mensaje visible; no se
  capturaron sus códigos internos individualmente. No reutilizar el incidente
  `AXPNOTUHLWK4` como si identificara todos los intentos.
- La fecha de apertura del ticket, sus mensajes originales y otras fechas de
  respuesta no están disponibles en los archivos consultados. No reconstruir
  actividad diaria de soporte a partir de la ausencia de respuesta.

Fuentes internas: [diagnóstico](../../DIAGNOSTICO_OAUTH.md),
[investigación histórica](mercadopago-oauth-errores.md),
[evidencia del 04/11](mercadopago-oauth-evidencia.json),
[revisión técnica del 12/11](mercadopago-revision-tecnica-20261112.md),
y confirmaciones del usuario en la conversación del 12/11.
