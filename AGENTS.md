# Continuidad de trabajo

## Mercado Pago: leer antes de diagnosticar

Antes de proponer pruebas o pedir datos al usuario sobre Mercado Pago/OAuth,
leer `DIAGNOSTICO_OAUTH.md`, empezando por el resumen del 12 de noviembre de
2026. Consultar también `.mp-integrate-progress.md` para distinguir el checkout
local de la prueba independiente del hosting.

El usuario pidió expresamente conservar las pruebas para que otras sesiones no
le pidan repetirlas. No volver a solicitar las pruebas marcadas como realizadas
sin evidencia nueva que justifique repetirlas; explicar qué cambió y qué dato
nuevo se espera obtener. Las propuestas no realizadas no deben presentarse como
pruebas completadas ni como soluciones confirmadas.

No pedir otra cuenta real: el usuario no dispone de una. Su cuenta habitual creó
la aplicación actual. No pedirle crear cuentas como requisito para continuar.
No volver a pedir el Client ID, el redirect_uri ni los campos del error ya
registrados para este intento. No confundir los parámetros que el usuario
ocultó al compartir un enlace con parámetros ausentes en la solicitud real.

El bloqueo observado ocurre en OAuth, antes del callback, no en la creación de
orders. La coincidencia exacta entre redirect_uri y callback_urls ya fue
verificada; no demuestra que el autorizador lo acepte. La causa sigue sin
resolverse. No atribuirla a campos internos, al hosting, a cuentas de prueba o
a Orders sin evidencia.

Actualizar el diagnóstico con cada prueba nueva, diferenciando observación
directa, resultado informado por el usuario e hipótesis. No registrar secretos,
cookies, códigos OAuth ni valores de state/verifier/challenge.
