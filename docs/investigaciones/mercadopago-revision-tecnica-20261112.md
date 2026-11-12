# Revisión técnica de OAuth — 12 de noviembre de 2026

## Resultado

No se encontró una corrección demostrada. El rechazo observado con vendedor de
prueba está identificado como `invalid_redirect_uri`. La API autenticada devuelve
el retorno registrado, pero eso no explica la regla del autorizador que falla.
No se sostiene como hecho que ambos servicios lean configuraciones diferentes.

La revisión se hizo sin nuevas pruebas manuales del usuario, sin ingresar a sus
cuentas, sin conceder permisos y sin modificar la configuración de Mercado Pago.

## Comprobación nueva con navegador

Firefox headless con perfil de diagnóstico independiente, sin sesiones del
usuario. Se abrió el hosting y se envió su formulario real de preparar intento.
La inspección del enlace generado mostró:

- Client ID `6921561117397304`.
- Un único `redirect_uri`, exactamente
  `https://todospara.uno/prueba-mp/retorno.php`.
- Ningún parámetro duplicado.
- `response_type=code`, state presente, challenge de 43 caracteres y método S256.
- Llegada a `www.mercadopago.com.ar/login/identification`, con título de ingreso,
  sin el mensaje de aplicación no preparada. Se terminó sin ingresar credenciales.

Se hicieron dos controles negativos exclusivamente en solicitudes anónimas;
no se registraron esas variantes en la aplicación:

| Solicitud | Resultado antes de ingresar |
|---|---|
| Enlace original generado por el hosting | Pantalla de login |
| Mismo enlace con retorno deliberadamente no registrado | Pantalla de login |
| Mismo enlace con Client ID deliberadamente inexistente (`0`) | Pantalla de login |

**Interpretación limitada:** llegar al login no distingue una configuración
válida de estos controles inválidos. No prueba que Mercado Pago ya haya
validado el Client ID o el retorno. El hecho de que el usuario vea el rechazo
después del login no basta para atribuírselo a su cuenta, a sus cookies o a los
usuarios de prueba. Los controles anónimos no reproducen la validación posterior
al ingreso ni aportan su código interno.

## Qué se revisó y qué no puede afirmarse

- La salida del generador y los parámetros básicos corresponden al flujo
  documentado. No se encontró un defecto en esa salida. **Actualización:** en la
  revisión posterior del backend se encontró una copia del paquete del hosting
  en `.respaldos-locales/prueba-mp-hosting/publico/`, excluida de Git. Se revisó
  ese fuente y se ejecutó con un vector público de PKCE; ver el apartado de
  backend. No se descargó el fuente desplegado ni se accedió al verifier de una
  sesión real del usuario.
- Las lecturas autenticadas anteriores confirmaron ambos retornos en
  `callback_urls`, aplicación activa y PKCE habilitado. No es necesario repetir
  esa consulta sin un nuevo cambio de configuración.
- `callback_url` singular vacío: no se encontró documentación oficial que
  explique qué prioridad tiene frente al plural en este autorizador. No cambiar
  ese campo ni atribuirle causalidad. El intento histórico de escritura ya fue
  rechazado con 403 y no debe repetirse.
- No se encontró una regla oficial que explique este error específico por usar
  Orders o por ser vendedor de prueba. Cambiar la API de cobros no está justificado
  por el fallo actual, anterior a crear una order.
- Las descripciones de Marketplace no prueban qué habilitación interna tiene
  esta aplicación. La ausencia de una opción en el panel ya fue informada por el
  usuario; no volver a pedirle buscarla como si fuera una comprobación nueva.
- Los errores internos de la prueba con cuenta real y del retorno alternativo
  no fueron capturados. Mismo mensaje visible no demuestra mismo error interno.
- Los fragmentos de `session/replicator` aportados por el usuario eran de la fila
  200. No completan la comparación de parámetros de la fila 400 actual. Esta
  limitación no es evidencia de un fallo de encoding.

## Siguiente evidencia útil, preparada sin pedir repetir el recorrido

Una traza de red de un intento fallido que ya exista en Firefox permitiría
comparar automáticamente la solicitud final de autorización con las URLs de
continuación del login. Es una investigación pendiente; no requiere por sí
misma crear otra cuenta ni cambiar retornos o PKCE.

Se preparó `utilidades/diagnosticar_oauth_har.py` para leer un HAR **localmente**:

```sh
python3 utilidades/diagnosticar_oauth_har.py /ruta/al/archivo.har
```

El analizador no tiene acceso a red. Solo devuelve estados HTTP y campos
permitidos: coincidencia de Client ID/retornos conocidos, presencia de state y
challenge, longitudes, método S256 y duplicados. Omite identificadores de sesión,
cookies, cuerpos, cabeceras, valores de state/challenge, tokens y códigos OAuth.
No copia la URL completa ni valores desconocidos. Las continuaciones anidadas
se distinguen de las solicitudes efectivamente observadas.

**El HAR original puede contener secretos y no debe compartirse ni versionarse.**
El analizador todavía no se ejecutó sobre una traza autenticada del usuario; no
presentar sus resultados sintéticos como evidencia del intento real. El siguiente
paso solo se solicitará si hay una traza disponible y explicando el dato nuevo
que se busca. Si los parámetros finales coinciden, tampoco eso demostrará qué
regla interna aplica Mercado Pago: solo cerrará una incertidumbre concreta.

## Revisión posterior del backend a pedido del usuario

Se revisaron `MercadoPagoServicio.php`, `MercadoPagoCliente.php`, la persistencia
del estado, el controlador y el punto de retorno de la plataforma local.
También se descubrieron las dos copias locales idénticas de `index.php` del
paquete del hosting en `.respaldos-locales/prueba-mp-hosting/`. La afirmación
anterior de que no había fuente disponible quedó corregida.

El paquete del hosting tiene configuración de datos públicos, inicia una sesión
con cookie Secure/HttpOnly/SameSite=Lax, regenera su ID al preparar el intento,
guarda state/verifier y calcula SHA-256 binario + Base64URL sin padding.
Construye el retorno con la URL base sin barra final + `/retorno.php` y escapa
el enlace para HTML. El callback valida sesión/state/vencimiento; no canjea
el código. La estructura coincide con la página que se observó en el navegador,
pero no se ha comparado el archivo desplegado byte a byte con esta copia.

Verificaciones ejecutadas:

- Sintaxis PHP de index y retorno del paquete: correcta.
- Ejecución del index con el vector público del apéndice B de RFC 7636:
  challenge esperado correcto, Client ID preservado, redirect_uri exacto tras
  una decodificación, state igual al de la sesión, siete parámetros válidos
  tras deshacer el escape HTML y verifier ausente del HTML. Seis verificaciones
  pasaron. Se usaron sesiones temporales de prueba, sin red ni base de datos.
- Pruebas existentes `modelos MercadoPagoSeguridad`: 26 comprobaciones pasaron.
- Prueba existente `controladores MercadoPagoController`: **no completada**.
  El fallback de preparación de tablas se detuvo con SQLSTATE 42000/1044 por
  permisos del usuario de la base local. No atribuirle un éxito ni relacionar
  este error local con el rechazo de la prueba remota independiente. No se
  alteraron permisos ni se aplicaron correcciones de esquema.

Resultado: no se identificó en la generación del enlace o de PKCE de la copia
del hosting un defecto que explique el rechazo actual. No es una garantía de
ausencia de errores en todo el backend. Los errores de sesión, canje del código
o persistencia posteriores al callback no explican por sí solos el rechazo que
ocurre en el autorizador antes de regresar. La implementación local de cobros
continúa usando Preferences; ese desajuste respecto de Orders se mantiene
registrado y no se migró durante esta revisión.

## Fuentes primarias consultadas

- [Flujo de autorización y PKCE](https://www.mercadopago.com.ar/developers/es/docs/security/oauth/creation).
- [Configuración de Split de Pagos](https://www.mercadopago.com.ar/developers/es/docs/split-payments/split-1-1/integration-configuration/create-configuration).
- [Datos de integración](https://www.mercadopago.com.ar/developers/es/docs/checkout-pro-preferences/additional-content/your-integrations/application-details).
- [Consulta de usuarios y aplicaciones](https://developers.mercadolibre.com.ar/usuarios-y-aplicaciones).
- Buscador de documentación del MCP de Mercado Pago: no aportó una explicación
  específica del rechazo actual ni de la prioridad singular/plural.

No se envió información a soporte ni se realizó una evaluación de calidad de pagos.
