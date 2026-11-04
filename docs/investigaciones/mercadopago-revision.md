# Mercado Pago Integration Review

**Fecha:** 2026-11-04. **Scope:** marketplace + quality.
**API detected:** Preferences API para crear Checkout Pro; Payments API para consultar pagos.
**Products detected:** Marketplace con OAuth y Checkout Pro.
**Files analyzed:** app/servicios/MercadoPago{Servicio,Cliente,Seguridad}.php, app/repositorios/MercadoPagoRepository.php, app/controladores/MercadoPagoController.php, vistas de conexión/donación/resultado, migración 20261104 y worker CLI.

## CRITICAL

- La migración `database/migrations/20261104_mercadopago_preferences.sql` no pudo aplicarse en la base habitual: el usuario `solidaria_app` no tiene ALTER ni permisos para crear tablas temporales. La base aislada sí verificó el esquema. Aplicar con un administrador antes de usar el flujo nuevo.
- No hay URL HTTPS de prueba activa, autorización real del vendedor ni prueba de entrega firmada. La importación de Client ID/Client Secret no demuestra que Mercado Pago haya habilitado OAuth Marketplace.
- No se procesó un pago externo de prueba ni se presentó homologación. No activar producción.

## WARNINGS

- Debe ejecutarse `utilidades/procesar_mercadopago.php` por cron o con --watch: responder 200 solo garantiza la persistencia del evento, no que ya esté acreditado.
- La cola reintenta fallos; no hay monitoreo ni conciliación periódica global por búsqueda de pagos.
- Los registros anteriores de Orders quedan preservados; no se convierten a Preferences. Revisar operaciones anteriores por separado.
- No se presupone que la API de Preferences deduplique solicitudes por X-Idempotency-Key. Los intentos se bloquean y reutilizan localmente y la acreditación se deduplica por payment_id.
- El validador genérico de Marketplace del plugin no aprueba este repositorio PHP: busca patrones de objetos/variables JavaScript y nombres de configuración que no usa el proyecto. Falló además por el checkpoint todavía presente. La inspección y las pruebas cubren state aleatorio persistente, cifrado, renovación, token del creador, Preferences y CTAs, pero no se declara que ese validador pasó.
- No se instaló SDK: se conservó el cliente REST PHP existente. El checklist oficial lo recomienda y figura como Missing.

## PASS

- Creación en /checkout/preferences e init_point; sin creación de Orders en el flujo nuevo.
- Token OAuth del creador resuelto desde la campaña; sin token de plataforma de respaldo.
- PKCE, state aleatorio persistente de un solo uso y retorno fijo.
- Tokens cifrados con sodium; renovación de ambos tokens bajo bloqueo.
- Validación de referencia, receptor, moneda, importe y fecha antes de acreditar.
- Cola durable con HMAC, respuesta inmediata sin llamadas remotas y reintentos con espera.
- Cada ID de pago conserva su estado; varios pagos/reembolsos de una preferencia no se confunden.
- Retorno verificado por API, CSRF y privacidad de cuentas/visitantes.
- 127 comprobaciones de pagos pasaron en MariaDB aislada. La suite completa también pasó en una copia del proyecto con esa base.
- Validador específico validate-checkout-pro-server.mjs: passed. JavaScript válido y diff sin errores de espacios.

## Quality Standards

Checklist recuperado mediante `quality_checklist(application_id=6921561117397304)`. Cada fila refleja inspección del código, no una certificación ni medición de un pago real. Algunas recomendaciones no corresponden al producto de donaciones; se mantienen visibles y se explica su ausencia.

### Required fields

| # | Field | Description | Status | Evidence |
| --- | --- | --- | --- | --- |
| 1 | `item_quantity` | Does it send the item quantity? | Implemented | `MercadoPagoServicio.php:212`: quantity=1. |
| 2 | `item_unit_price` | Does it send the unit price? | Implemented | `MercadoPagoServicio.php:212`: monto persistido convertido a número JSON. |
| 3 | `statement_descriptor_/_soft_descriptor` | Does the integration use a soft descriptor or statement descriptor? | Implemented | `MercadoPagoServicio.php:215`: TODOSPARAUNO. |
| 4 | `back_urls` | Does the seller send back urls in the request? | Implemented | `MercadoPagoServicio.php:216`: éxito, fallo y pendiente. |
| 5 | `webhooks_ipn` | Does the integration has notifications enabled? | Partial | `MercadoPagoServicio.php:218`, `MercadoPagoController.php:176`: URL y receptor implementados; registro público y entrega real pendientes. |
| 6 | `external_reference` | Does it send an external reference for conciliation? | Implemented | `MercadoPagoServicio.php:214`: referencia del intento. |
| 7 | `email` | Does it send the payer’s email address? | Implemented | `MercadoPagoServicio.php:213`: correo validado del donante. |
| 8 | `payer_first_name` | Does it send the payer’s first name? | Missing | El payload de `MercadoPagoServicio.php:213` solo envía email; no inventar nombres de visitantes. |
| 9 | `payer_last_name` | Does it send the payer’s last name? | Missing | `MercadoPagoServicio.php:213`: apellido no enviado. |
| 10 | `item_category_id` | Sends category id? | Missing | `MercadoPagoServicio.php:210`: no se envía una categoría remota no verificada. |
| 11 | `item_description` | Sends item description? | Implemented | `MercadoPagoServicio.php:211`: descripción de la donación. |
| 12 | `item_id` | Sends item id? | Implemented | `MercadoPagoServicio.php:210`: ID de campaña resuelto en servidor. |
| 13 | `item_title` | Does it send the item title? | Implemented | `MercadoPagoServicio.php:210`: título de la campaña. |
| 14 | `back_end_sdk` | Does the integration use any backend SDK? | Missing | `MercadoPagoCliente.php:19`: cliente REST existente; no hay SDK ni dependencias nuevas. |

### Best practices

| # | Practice | Description | Status | Evidence |
| --- | --- | --- | --- | --- |
| 1 | `binary_mode` | Is binary mode configurable for seller? | Missing | No se exige aprobación instantánea; se admiten estados pendientes. |
| 2 | `date_of_expiration` | Is date of expiration configurable for seller? | Missing | No hay configuración de vencimiento de pagos offline. |
| 3 | `marketing_information` | Uses Facebook Pixel or Google Ads services? | Missing | No se agregaron servicios de publicidad al flujo de donaciones. |
| 4 | `expiration` | Sets expiration date? | Missing | No se definió una ventana comercial de vencimiento de preferencias. |
| 5 | `max_installments` | Sends max installments? | Missing | No se impuso una política de cuotas del creador. |
| 6 | `modal` | Does the integration open Checkout in modal form? | Missing | Se usa redirección alojada, no modal (`MercadoPagoController.php:126`). |
| 7 | `logos` | Shows Mercado Pago logo properly in Payment Methods in own website? | Missing | El botón usa texto, sin un logo oficial nuevo (`app/vistas/donar.php`). |
| 8 | `response_messages` | Shows feedback to the payer for rejections or error messages from the API? | Implemented | `app/vistas/parciales/estados_pago.php`, `app/vistas/donar.php`: estados y errores accionables. |
| 9 | `excluded_payment_methods` | Exclusión de Medios de Pago | Missing | No se excluyen medios sin una regla comercial solicitada. |
| 10 | `excluded_payment_types` | Exclusión de Tipos de Medios de Pago | Missing | No se excluyen tipos sin una regla comercial solicitada. |
| 11 | `shipment_amount` | Monto del envío | Missing | No aplica al aporte monetario; no hay envío de mercadería. |
| 12 | `payment_get_or_search_api` | Does the seller get the order or payment after notification? | Implemented | `MercadoPagoServicio.php:247`, `:306`: GET de pagos con receptor, moneda, referencia e importe comprobados. |
| 13 | `chargebacks_api` | Does the integration use chargebacks API to upload documentation in order to dispute it? | Missing | Se reflejan contracargos; no se sube documentación de disputa por API. |
| 14 | `cancellation_api` | Does the integration cancel payments through the payments API? | Missing | No hay acción de cancelación por API en la plataforma. |
| 15 | `refunds_api` | Does the integration use refunds API? | Missing | Se concilian reembolsos; no se ordenan devoluciones desde la plataforma. |
| 16 | `settlement` | Does the integration have a settlement report? | Missing | No hay reporte de liquidaciones. |
| 17 | `release` | Does the integration have a release report? | Missing | No hay reporte de liberaciones. |
| 18 | `address` | Sends payer's address? | Missing | No se recopila domicilio para una donación monetaria. |
| 19 | `payer_identification` | Does the integration send the payer’s identification type and number? | Missing | No se agregan documentos al payload del donante. |
| 20 | `payer_phone` | Sends payer's phone number? | Missing | No se solicita teléfono en el checkout local. |
| 21 | `payer_identification_mlm` | Does the integration send the payer’s identification type and number? | Missing | No aplica: integración argentina. |
| 22 | `front_end_sdk_pro` | sdk .js para checkout pro | Missing | Redirección por init_point; sin SDK JavaScript en este flujo. |

### Security checklist (cross-cutting)

| # | Check | Status | Evidence |
| --- | --- | --- | --- |
| 1 | Tokens fuera de código público | Implemented | Configuración privada ignorada y `MercadoPagoSeguridad::cifrar` para tokens OAuth. |
| 2 | Secretos excluidos de Git | Implemented | `.gitignore` cubre configuración local, copia anterior y archivo del comprador. |
| 3 | HMAC del webhook | Partial | `MercadoPagoController.php:176`, comparación constante en `MercadoPagoSeguridad.php`; falta confirmar firma con entrega real. |
| 4 | HTTPS en retornos/notificaciones | Implemented | `MercadoPagoServicio.php:14`: exige HTTPS y rechaza localhost. |
| 5 | Pago verificado después del retorno | Implemented | `MercadoPagoController.php:94`, `MercadoPagoServicio.php:247`. |
| 6 | Protección frente a duplicados | Implemented | Header en `MercadoPagoCliente.php:19`, bloqueo del intento y clave única payment_id. No presupone idempotencia remota de Preferences. |
| 7 | Referencia externa propia | Implemented | `MercadoPagoServicio.php:214`. |
| 8 | Separación de cuentas de prueba | Implemented | Configuración privada con produccion=false; comprobación de test_user y MLA al conectar. |
| 9 | Redirección correcta | Implemented | init_point en `MercadoPagoServicio.php:222`; no se usa la URL de checkout obsoleta. |

### Recommendations

1. Aplicar la migración con un usuario administrador y configurar una URL HTTPS real, retorno OAuth y secreto del webhook.
2. Vincular al vendedor de prueba, donar con el comprador y verificar la entrega con el plugin.
3. Ejecutar la cola, evaluar el primer payment_id y revisar la habilitación de Marketplace/homologación.
4. Definir monitoreo, conciliación periódica y política de datos de pagador antes de producción.

**Summary:** 9/14 campos implementados, 1/14 parcial; 2/22 prácticas implementadas. La cuenta incluye recomendaciones ajenas al alcance de donaciones y no equivale a un umbral de aprobación. 8/9 controles de seguridad comprobados localmente; 1 pendiente de entrega real.
**quality_evaluation:** skipped — no payment_id externo disponible.
**Homologation form:** not submitted — pendiente antes de producción.

---

## Implementation Report

### Verified

- [x] Credenciales OAuth importadas por el plugin a la configuración privada, sin habilitar producción.
- [x] Checkout Pro corregido a Preferences con token del creador y comisión de plataforma cero.
- [x] Retorno, conciliación por payment_id, reembolsos y cola firmada implementados.
- [x] Comprador de prueba recuperado mediante mp-test-setup; saldo ficticio de ARS 1.000 agregado.
- [x] Pruebas de pagos y suite completa aprobadas en base aislada.

### Needs attention

- [ ] Registrar URL HTTPS y callback mediante mp-webhooks cuando exista una dirección activa.
- [ ] Mantener ejecutándose el worker de `utilidades/procesar_mercadopago.php`.
- [ ] Revisar el rechazo OAuth anterior con un consentimiento nuevo; no se confirmó su causa raíz.
- [ ] Preparar monitoreo y conciliación periódica.

### Blockers (must fix before production)

- [ ] Aplicar `database/migrations/20261104_mercadopago_preferences.sql` en la base habitual con permisos de administración.
- [ ] Completar consentimiento del vendedor y prueba externa de donación/webhook.
- [ ] Evaluar el primer pago real de prueba y completar la homologación que corresponda.

### Next steps

1. Importar la migración desde phpMyAdmin con un administrador.
2. Proveer HTTPS, registrar el retorno en Developers y configurar el webhook con el plugin.
3. Re-run mp-review después de la primera donación de prueba.

### Resources used

- Local: inspección, pruebas PHP/MariaDB y controles de seguridad.
- MCP: get_credentials, create_test_user (recuperó comprador existente), add_money_test_user.
- MCP: quality_checklist y notifications_history, 2026-11-04. Este último no encontró notificaciones.
- MCP: quality_evaluation y form_homologation no ejecutados, sin pago externo.
- Skills: mp-integrate, mp-webhooks, mp-test-setup y mp-review v1.0.0.

**Scores:** 9/14 required y 2/22 best practices, 8/9 seguridad con verificación externa pendiente. **Verdict:** Blocked para producción; implementación preparada para continuar pruebas al resolver el entorno.


## Actualización del entorno posterior a la revisión

El usuario aplicó la migración y las 127 comprobaciones de pagos pasaron sobre la base habitual. Se inició HTTPS en `https://mean-councils-camcorders-equity.trycloudflare.com` y el plugin registró el webhook de pruebas. Servidor PHP, túnel y worker quedaron activos. Sigue pendiente guardar el retorno OAuth en Developers, actualizar el secreto local del webhook (su prefijo no coincide con el informado por el plugin), autorizar al vendedor y realizar una donación externa de prueba. Los bloqueos de migración y ausencia de HTTPS de la revisión anterior quedan resueltos.

### Primera recepción externa del simulador

2026-11-04 12:39:06 ART: llegó POST con data.id=123456 y type=topic_claims_integration_wh. El receptor respondió 400. La implementación comprueba HMAC antes de leer y validar el cuerpo, y devuelve 401 si falla la firma: esta petición pasó la validación de firma con el secreto local. Queda resuelta la sospecha sobre el secreto basada en el prefijo del plugin. Falta repetir con el evento payment para validar el encolado (y después con un pago de prueba existente para conciliar dinero). El historial MCP todavía no mostró esta simulación.

### Simulación payment aceptada

2026-11-04 12:40:38 ART: POST externo con data.id=123456 y type=payment respondió HTTP 200. Confirma llegada HTTPS, validación HMAC, cuerpo admitido y persistencia en la cola. El worker informó una notificación pendiente de reintento: el ID ficticio no corresponde a una donación verificable. Esta prueba no confirma acreditación ni reemplaza una donación externa con un pago de prueba existente.
