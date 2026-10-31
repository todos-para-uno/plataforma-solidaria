# Mercado Pago: preparación y pruebas

La integración usa **Checkout Pro con Orders API y OAuth**. Cada creador autoriza su cuenta, y los cobros se crean con su token. No se pide ni guarda su contraseña. No se configura comisión para todospara.uno.

El código incluye vinculación, renovación de tokens, inicio de aportes, retorno, confirmación por webhook y consulta de historial. La creación de campañas sigue siendo un recorrido separado todavía pendiente; para probar pagos hay que contar con una campaña monetaria existente.

## 1. Preparar la base

En una base existente, hacer un respaldo e importar `database/migrations/20261031_mercadopago.sql` seleccionando esa base. Crea `mercadopago_cuentas` y `mercadopago_pagos` sin borrar datos.

Para una base nueva, `database/schema.sql` ya incluye esas tablas. El usuario habitual de la aplicación necesita permisos de consulta y modificación; la importación debe hacerla un usuario que pueda crear tablas.

## 2. Crear la aplicación de Mercado Pago

En Developers, crear una aplicación de **Pagos online → Checkout Pro → Marketplace**, usando Orders API. Ubicar el Client ID (número de aplicación) y el Client Secret. No usar un Access Token fijo de la plataforma para cobrar por otros usuarios.

Habilitar **PKCE** en la configuración de OAuth: nuestro código envía `code_challenge_method=S256`.

## 3. Darle una URL HTTPS al servidor local

Iniciar la aplicación normalmente:

```bash
php -S 127.0.0.1:8000 -t public_php
```

Usar un túnel HTTPS de desarrollo o un servidor de pruebas que apunte a ese puerto. El túnel expone la aplicación mientras está abierto: no iniciar otro servidor con la raíz del repositorio como directorio público.

Ejemplo de URL asignada por el túnel: `https://tu-subdominio-de-prueba.example`. Es un ejemplo, no una dirección para pegar literalmente. Usar la URL real que proporcione el túnel.

Abrir la aplicación e iniciar sesión **desde la URL HTTPS**, no desde localhost. Así la cookie de sesión estará presente cuando Mercado Pago regrese de OAuth. Si se abre desde otro dominio, el estado de autorización no podrá validarse.

Configurar en la aplicación de Mercado Pago:

| Configuración | Dirección |
| --- | --- |
| Redirect URL de OAuth | `https://TU-DOMINIO/index.php?ruta=mp_retorno` |
| Webhook de Orders | `https://TU-DOMINIO/index.php?ruta=mp_webhook` |

En Webhooks, seleccionar el evento **Order (Mercado Pago)** y guardar la firma secreta. Si la aplicación está bajo una subcarpeta, incluirla en ambas URL y en `url_publica`.

Si el túnel cambia de dirección, actualizar la configuración privada y el panel de Mercado Pago. La Redirect URL debe coincidir exactamente, incluidos parámetros y subcarpeta.

## 4. Configuración privada

Copiar `config/mercadopago.ejemplo.php` a `config/mercadopago.local.php` y completar sus campos localmente. El segundo archivo está excluido de Git y queda fuera de `public_php`.

- `habilitado`: pasar a `true` cuando estén completas la configuración y la migración.
- `client_id`: número de la aplicación nueva.
- `client_secret`: secreto de OAuth de esa aplicación.
- `url_publica`: URL HTTPS que sirve `public_php`, sin `/index.php` ni barra final.
- `clave_cifrado`: clave para cifrar las autorizaciones en la base.
- `webhook_secret`: firma secreta del webhook de esa misma aplicación.
- `produccion`: dejar en `false` durante las pruebas.

Para generar la clave de cifrado, ejecutar localmente:

```bash
php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'
```

Copiar el resultado en el archivo privado. No compartirlo por chat ni subirlo al repositorio. Conservar una copia segura junto con los respaldos: perderlo impide recuperar las autorizaciones cifradas. Cambiarlo sin migrar los tokens exige volver a vincular las cuentas.

También se admiten las variables `MP_HABILITADO`, `MP_CLIENT_ID`, `MP_CLIENT_SECRET`, `MP_URL_PUBLICA`, `MP_CLAVE_CIFRADO`, `MP_WEBHOOK_SECRET` y `MP_PRODUCCION`. El archivo local tiene prioridad.

Requiere PHP con `curl`, `sodium`, `pdo_mysql` y las extensiones habituales del proyecto. Se usa cURL directamente, sin dependencia del SDK o de Composer.

## 5. Probar la vinculación

1. Preparar usuarios de prueba de Argentina en Mercado Pago: creador/vendedor y donante/comprador; configurar la aplicación de integración según el entorno indicado por Developers.
2. Iniciar sesión como creador en todospara.uno desde la URL HTTPS.
3. Abrir **Mi cuenta → Gestionar Mercado Pago**, o el enlace de conexión en Crear campaña. El enlace de Crear campaña abre otra pestaña para conservar los datos del formulario.
4. Tocar **Conectar Mercado Pago** y autorizar con el vendedor de prueba. Si el navegador ya tiene una cuenta real abierta, cerrar esa sesión o usar un perfil de navegador separado, manteniendo en ese perfil también la sesión HTTPS de todospara.uno.
5. Volver al sitio y comprobar el estado conectado.

En pruebas se verifica que el receptor tenga la etiqueta `test_user` mediante `/users/me`. El prefijo `APP_USR` o el campo OAuth `live_mode` por sí solos no distinguen cuentas reales de prueba. El entorno de prueba rechaza receptores reales.

Una cuenta externa solo se vincula a un usuario local. La renovación debe mantener esa identidad y el entorno. No se ofrece reemplazarla por otra cuenta con pagos anteriores pendientes.

## 6. Probar un aporte

1. Tener una campaña monetaria activa en la base cuyo `owner_id` sea el creador vinculado.
2. Iniciar sesión como otro usuario de todospara.uno y abrir el detalle de esa campaña.
3. Tocar **Donar a esta campaña**, ingresar un monto y continuar al checkout.
4. Pagar con el comprador y los medios de pago de prueba adecuados al entorno de Mercado Pago.
5. Volver a la pantalla de resultado. No se acredita a partir de `status=approved` en la URL: el estado se verifica consultando Orders con la autorización del receptor.
6. Comprobar la notificación en el panel de Mercado Pago. También se puede usar **Consultar estado**, que exige sesión y CSRF.
7. Revisar **Mis donaciones**, el progreso y el listado de campañas del creador.

Los intentos conservan su referencia e idempotencia al reintentar el mismo envío. Si Mercado Pago tarda o falla, reintentar el formulario mostrado; no abrir otro aporte para sustituirlo sin revisar antes su estado.

El historial conserva el monto original y el resultado. El progreso usa el importe acreditado. Los reembolsos completos y contracargos restan lo acreditado; un reembolso parcial requiere que Orders informe sus importes en `transactions.refunds`. Si falta información, la operación no se actualiza y se solicita reintento.

El webhook se autentica con HMAC sobre el ID del recurso, request-id y timestamp. Solo ese ID selecciona el pago; no se confía en importes, estados o destinatarios enviados en el cuerpo. Luego se consulta la order y se verifica receptor, moneda, monto y referencia. Si llega antes de guardar la order, responde 503 para que Mercado Pago reintente.

## 7. Ejecutar pruebas automáticas

```bash
php tests/run_tests.php modelos MercadoPagoSeguridad
php tests/run_tests.php controladores MercadoPagoController
php tests/run_tests.php
```

Las pruebas de integración usan una base local con el esquema actualizado y revierten los registros al terminar. Si falta la migración, intentan crear tablas temporales: eso requiere el permiso correspondiente en la base de pruebas. No conceder permisos administrativos al usuario habitual solo para ejecutar pruebas.

La API se sustituye en las pruebas: no envían correos de pago ni llaman a Mercado Pago. Cubren state, PKCE, expiración, separación de usuarios, cifrado, renovación, colisiones de cuentas, idempotencia, acreditación única, estados fuera de orden, reembolsos, firma y privacidad del historial.

## Límites y paso a producción

- La prueba externa con OAuth, Orders y webhook queda pendiente hasta disponer del dominio HTTPS y las credenciales privadas. Las pruebas simuladas no certifican la habilitación de marketplace de una aplicación concreta.
- No se guardan datos de tarjetas o contraseñas de Mercado Pago; sí autorizaciones cifradas que requieren proteger servidor, claves y respaldos.
- No hay botón de reembolso en todospara.uno. Las devoluciones se gestionan desde Mercado Pago y se reflejan mediante notificaciones/consulta.
- Mantener las cuentas vinculadas para poder conciliar pagos anteriores. La desvinculación se puede revocar desde Mercado Pago; si se revoca, se necesita reautorizar esa misma cuenta para consultar sus operaciones.
- No hay tarea programada de conciliación: si se pierde un webhook, revisar el panel del proveedor y usar Consultar estado. Para producción se debe programar la conciliación y revisar límites de solicitudes y operación del servidor.
- No habilitar `produccion` como forma de sortear errores de pruebas. Preparar la configuración productiva, revisar los requisitos de Mercado Pago y realizar una verificación completa antes de aceptar pagos reales. No mezclar registros y vinculaciones de ambos entornos en la misma base.

## Documentación oficial

- [OAuth y PKCE](https://www.mercadopago.com.ar/developers/es/docs/security/oauth/creation)
- [Crear una order de Checkout Pro](https://www.mercadopago.com.ar/developers/es/docs/checkout-pro-orders/create-order)
- [URLs de retorno de Orders](https://www.mercadopago.com.ar/developers/es/docs/checkout-pro-orders/web-integration/configure-back-urls)
- [Notificaciones de Orders](https://www.mercadopago.com.ar/developers/es/docs/checkout-pro-orders/notifications)
- [Integración marketplace](https://www.mercadopago.com.ar/developers/es/docs/split-payments/split-1-1/integration-configuration/integrate-marketplace)
