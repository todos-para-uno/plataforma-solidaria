# Respuesta al ticket — contexto actual del hosting

Borrador para envío manual. No enviado por Codex. Completar únicamente el enlace
del botón «Continuar a Mercado Pago» con clic derecho → Copiar enlace. No hace
falta abrirlo ni volver a ingresar credenciales. El pedido de soporte admite sus
parámetros públicos; no adjuntar cookies, HAR completo, códigos ni credenciales.
No guardar state/challenge en el diagnóstico del repositorio.

---

Hola. Confirmamos el contexto actual de la aplicación 6921561117397304:

- Sitio de prueba: https://todospara.uno/prueba-mp/
- redirect_uri actual: https://todospara.uno/prueba-mp/retorno.php
- Coincide exactamente con la dirección que ustedes confirman registrada.
- PKCE está habilitado y el enlace envía code_challenge_method=S256.

Las URLs de los túneles corresponden a pruebas históricas. El rechazo también
se reproduce con el dominio y el retorno actuales. Volvimos a desplegar los tres
archivos del paquete revisado y el mensaje persiste.

URL de autorización actual, copiada del botón sin iniciar otra autorización:

[PEGAR AQUÍ EL ENLACE COMPLETO EN UNA SOLA LÍNEA]

En un intento del 12/11/2026 con este retorno, la respuesta HTTP 400 de
/authorization identificó type=invalid_redirect_uri, contextNumber=16 y
uniqueErrorCode=AXPNOTUHLWK4. La preparación de ese intento fue a las 14:18:44
UTC-03:00; no disponemos de la hora exacta del error. Este identificador es del
intento anterior, no del enlace recién preparado que adjuntamos.

¿Pueden revisar qué condición concreta provoca invalid_redirect_uri con la URL
actual, y qué retorno está leyendo su validador para esta aplicación? Necesitamos
contrastar el valor efectivamente validado, porque la coincidencia con el retorno
registrado ya fue comprobada y el rechazo persiste.

Gracias.
