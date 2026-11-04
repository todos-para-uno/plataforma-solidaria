# Mercado Pago — Marketplace con Checkout Pro · Argentina · preferences · PHP REST

Cada creador vincula su propia cuenta mediante OAuth. Las donaciones se crean con el token de ese creador, en ARS y sin comisión de la plataforma (`marketplace_fee: 0`). El checkout se abre en Mercado Pago mediante `init_point`. Las comisiones propias de Mercado Pago siguen aplicándose.

## 1. Instalación y migración

Se conserva el cliente REST con cURL del proyecto PHP. No se agregaron dependencias de Composer ni npm. Requiere PHP con `curl`, `sodium` y `pdo_mysql`.

En una base existente, respaldar los datos y aplicar **solo las migraciones pendientes**, con un usuario de administración:

1. `database/migrations/20261031_mercadopago.sql`.
2. `database/migrations/20261031_donaciones_invitados.sql`.
3. `database/migrations/20261103_mercadopago_autorizacion.sql`.
4. `database/migrations/20261104_mercadopago_preferences.sql`.

Para esta actualización, si ya se usaba la rama `feat/mercadopago`, corresponde importar la última migración desde phpMyAdmin. Agrega identificadores de preferencia/pago, estados OAuth persistentes, transacciones individuales y una cola de notificaciones. Conserva las columnas y registros anteriores de Orders. El esquema completo `database/schema.sql` incluye la actualización para instalaciones nuevas; no volver a importarlo en una base con datos.

**Estado de esta máquina:** el usuario aplicó la migración con permisos de administración. Se verificó después con las 127 comprobaciones de pagos sobre la base habitual; todas pasaron. No se ampliaron los permisos de `solidaria_app`.

## 2. Credenciales y direcciones

La aplicación seleccionada es `todosparauno` (`6921561117397304`). Se importaron Client ID y Client Secret mediante el plugin al archivo privado `config/mercadopago.local.php`, excluido de Git. Se conservó su clave de cifrado y `produccion=false`. La copia anterior está en `config/.mercadopago.local.php.before-plugin`, también privada. No se usa un Access Token fijo de la plataforma para cobrar por los creadores.

Para otra instalación, copiar `config/mercadopago.ejemplo.php` al archivo local y completar sus campos allí. `MP_CLIENT_ID`, `MP_CLIENT_SECRET`, `MP_URL_PUBLICA`, `MP_CLAVE_CIFRADO`, `MP_WEBHOOK_SECRET`, `MP_HABILITADO` y `MP_PRODUCCION` también se admiten como variables de entorno; el archivo local tiene prioridad. Nunca compartir ni versionar secretos o tokens de vendedores.

Con una URL HTTPS activa que sirva **únicamente `public_php/`**, configurar:

| Campo | Valor |
| --- | --- |
| `url_publica` | `https://TU-DOMINIO` (incluir subcarpeta si corresponde) |
| Retorno OAuth registrado en Developers | `https://TU-DOMINIO/mercadopago-retorno.php` |
| Notificaciones | `https://TU-DOMINIO/index.php?ruta=mp_webhook` |
| Evento en Developers | **Pagos** (`payment`) |
| `webhook_secret` | Firma secreta de Webhooks de esa aplicación |

Túnel temporal activo: `https://mean-councils-camcorders-equity.trycloudflare.com`. Ya está guardado en la configuración privada y registrado por el plugin como webhook de pruebas. Al reiniciar el túnel cambia la dirección y deben actualizarse los callbacks. El secreto local pasó la validación HMAC de una solicitud externa del simulador el 2026-11-04 a las 12:39 ART. La discrepancia previa con el prefijo mostrado por el plugin no debe usarse como prueba de secreto incorrecto. Esa solicitud era de Reclamos y se rechazó por tipo de evento; falta repetir con Pagos (`payment`). El plugin permite registrar webhooks, pero las herramientas disponibles no permiten editar la Redirect URL de OAuth ni la habilitación Marketplace de una aplicación existente. Esas opciones deben comprobarse en Developers. La URL de retorno debe coincidir exactamente y PKCE debe estar habilitado.

La importación de credenciales no demuestra que la aplicación esté habilitada para OAuth de Marketplace. El rechazo anterior «La aplicación no está preparada para conectarse» sigue requiriendo una autorización externa para verificar su causa.

## 3. Código del servidor

- `app/servicios/MercadoPagoCliente.php`: API HTTPS, OAuth con formulario codificado, creación en `/checkout/preferences`, consultas en `/v1/payments/{id}` y búsqueda por referencia. Nunca registra respuestas o secretos.
- `app/servicios/MercadoPagoServicio.php`: valida la campaña y el monto elegido por el donante, persiste el intento, resuelve el creador desde la base y usa exclusivamente su token OAuth. Crea una preferencia con moneda ARS, referencia propia, URLs HTTPS y comisión cero.
- `app/repositorios/MercadoPagoRepository.php`: estados OAuth de un solo uso, tokens cifrados y renovación atómica, bloqueo de intentos y acreditación transaccional por ID de pago.
- `app/controladores/MercadoPagoController.php`: sesión, CSRF, privacidad del resultado y retorno con consulta real a la API. Ignora el estado de pago recibido en la URL.

Un reenvío del mismo formulario reutiliza la preferencia guardada. Se envía `X-Idempotency-Key`, pero no se presupone que Preferences garantice idempotencia remota: un timeout después de crearla puede producir otra preferencia. Crear una preferencia no cobra dinero. Cada pago confirmado tiene su propia fila y se acredita una sola vez, incluso si se notifican varios pagos para la misma referencia.

## 4. Cliente y recorrido

1. El creador inicia sesión desde la URL HTTPS y abre **Mi cuenta → Mercado Pago**.
2. Pulsa **Conectar Mercado Pago** y autoriza con su vendedor de prueba. La vinculación exige consentimiento en Mercado Pago; el plugin no puede prestarlo por el vendedor.
3. El donante abre una campaña monetaria activa y pulsa **Donar a esta campaña**.
4. Ingresa el monto en ARS. Un visitante también puede donar sin registrarse, indicando su correo.
5. **Donar con Mercado Pago** envía el formulario real de `app/vistas/donar.php`; se muestra el estado de preparación y se impiden reenvíos durante la redirección.
6. Al volver, el servidor busca los pagos por su referencia y comprueba ID, receptor, moneda e importe antes de actualizar el progreso. **Consultar estado** permite reintentar si el proveedor todavía no confirmó el pago.

Los tokens, códigos OAuth y datos de tarjeta no se entregan al navegador de la aplicación. El visitante solo puede consultar su aporte desde su propia sesión.

## 5. Webhook y worker

Se aplicó la skill `mp-webhooks`: el receptor valida la firma HMAC-SHA256 de `data.id`, request-id y timestamp, comprueba que el ID del cuerpo coincida, guarda el evento y responde 200 sin llamar a Mercado Pago. Si no puede guardarlo, responde 503 para que se reintente.

El worker consulta el pago con el token del receptor, verifica su referencia y datos reales, y acredita o descuenta reembolsos/contracargos en una transacción. El `user_id` del cuerpo solo enruta la consulta; no acredita dinero por sí mismo. Las notificaciones duplicadas son seguras y un fallo conserva el evento con espera progresiva para reintentar.

Procesar una pasada:

```bash
php utilidades/procesar_mercadopago.php
```

En desarrollo, mantenerlo abierto en otra terminal:

```bash
php utilidades/procesar_mercadopago.php --watch
```

En un servidor, ejecutar por cron cada minuto. No publicar `utilidades/` como directorio web. La renovación de tokens y la cola requieren las migraciones de esta versión.

## 6. Pruebas

El plugin recuperó un comprador de prueba ya existente y agregó **ARS 1.000 de saldo ficticio**. Sus datos de acceso están en `config/.mercadopago.local.php.comprador.md`, privado y excluido de Git. Son para iniciar sesión en el checkout; no deben usarse como credenciales de la aplicación. Se conserva el vendedor de prueba que ya tiene el usuario.

Con la base actualizada:

```bash
php tests/run_tests.php modelos MercadoPagoSeguridad
php tests/run_tests.php controladores MercadoPagoController
php tests/run_tests.php
```

La API se sustituye en las pruebas automáticas: no realizan cobros. La suite de pagos pasó en una base aislada y verifica Preferences, receptor, importe, moneda, OAuth/PKCE, replay, renovación, privacidad, CSRF, múltiples pagos por preferencia, acreditación única, reembolsos, cola y recuperación de fallos.

La prueba externa queda pendiente de la migración en la base habitual, una URL HTTPS activa, el retorno OAuth registrado, la firma secreta correcta y el consentimiento del vendedor. Después, pagar con el comprador de prueba, ejecutar el worker y comprobar la entrega con `notifications_history`. No se hizo ningún cobro real ni se activó producción.

## 7. Documentación

- [Marketplace y Checkout Pro](https://www.mercadopago.com.ar/developers/es/docs/split-payments/split-1-1/integration-configuration/integrate-marketplace).
- [Obtener información del pago](https://www.mercadopago.com.ar/developers/es/reference/online-payments/checkout-pro-preferences/get-payment/get).
- [Webhooks y firma](https://www.mercadopago.com.ar/developers/es/docs/your-integrations/notifications/webhooks).
- [OAuth y PKCE](https://www.mercadopago.com.ar/developers/es/docs/security/oauth/creation).

## 8. Límites y verificaciones pendientes

- Checkout Pro usa Preferences. Se eliminó la creación incorrecta de Orders del flujo nuevo. Las filas anteriores se conservan para revisión y no se convierten automáticamente en pagos de Preferences.
- Las pruebas simuladas no certifican el consentimiento OAuth, la habilitación de Marketplace ni la entrega remota de notificaciones.
- El validador específico de Checkout Pro del plugin pasó. El validador general de Marketplace reporta varias ausencias porque busca patrones JavaScript y nombres de configuración distintos de este proyecto PHP; esas salidas se documentan en la revisión y no se presentan como una aprobación.
- No se implementó una tarea periódica de búsqueda de todos los pagos: la cola procesa notificaciones y el retorno/consulta manual concilia por referencia. Antes de producción, definir monitoreo y conciliación periódica.
- El historial de donaciones conserva los montos originales y muestra el total acreditado para la referencia. Cada pago remoto se concilia por separado, incluso ante reembolsos.
- No se agregan formularios de tarjetas, SDKs ni nombres, documentos o domicilios ficticios para satisfacer un puntaje de calidad.

## 9. Próximos pasos

1. Webhooks (`mp-webhooks`): aplicar la migración, disponer de HTTPS, registrar el callback con el plugin y comprobar la firma con una notificación real de prueba.
2. Pruebas (`mp-test-setup`): vincular al vendedor existente y donar con el comprador recuperado y su saldo ficticio.
3. Revisión (`mp-review`): consultar `docs/investigaciones/mercadopago-revision.md`, evaluar el ID del primer pago de prueba y completar la homologación que corresponda antes de producción.
