# Refactor visual — noviembre de 2026

Se aplicaron los lineamientos de `DESIGN_GUIDELINES.md` conservando la arquitectura PHP y la identidad de todospara.uno. Las decisiones reutilizables están en `DESIGN.md` y sus ejemplos en la guía de estilos.

## Cambios

- Sistema compartido: papel cálido, bordes regulares, sombras suaves, tipografía local, foco visible y estados de campos y botones.
- Inicio: invitaciones a ayudar y crear campaña con composiciones espejadas. Ambas usan tres tarjetas claras independientes, con números dorados sobre azul. Ayudar muestra las tarjetas a la izquierda y el mensaje a la derecha; crear conserva el mensaje a la izquierda y las tarjetas a la derecha. La trama queda en los márgenes exteriores. En móvil el mensaje precede a los pasos. Se conservan el enlace a todas las campañas y el estado vacío con acción útil.
- Fotografía original con mayor presencia en la portada, en el listado de campañas del inicio y como fondo compartido de las pantallas interiores.
- Encabezados de Escribinos y Buscar campañas con el mismo ancho horizontal, definido por el contenedor compartido.
- Trama suministrada como SVG reutilizable en detalles, encabezados interiores y tarjetas sin foto.
- Cómo funciona: foto original en portada y trama original sin velo sobre sus líneas en el cuerpo, detrás de las preguntas.
- Formularios, contacto y pantallas de cuenta: encabezados consistentes y contenido sobre superficies legibles.
- Galería de campañas: portada destacada y fotos secundarias limitadas a miniaturas de 720 × 540 px, sin deformar ni recortar, con enlace al original.
- Eliminados los desplazamientos y el zoom de hover en formularios, carga de fotos y tarjetas administrativas.

## Comprobaciones realizadas

- Sintaxis PHP de las vistas modificadas: correcta.
- `git diff --check`: sin errores.
- `node tests/js/PuntoEntregaTest.js`: correcto.
- `php tests/run_tests.php controladores`: numerosas comprobaciones correctas, pero ejecución incompleta al llegar a BajaCuenta: el usuario local `solidaria_app` carece de permisos sobre `todosparauno_test`. No se alteraron permisos ni configuración de la base.
- Firefox headless: siete pantallas públicas (inicio, contacto, registro, ingreso, Cómo funciona, búsqueda y resultados) a 320, 390, 768, 1024, 1100 y 1440 px de viewport de contenido: 42 comprobaciones, sin desbordamientos horizontales ni imágenes rotas.
- Menú móvil: apertura, cierre con Escape y sincronización de `aria-expanded`, correctos.
- Contacto: validación nativa de campos obligatorios y asociación de etiquetas, correctas; no se enviaron mensajes desde la revisión visual.
- Inspección de capturas de inicio, contacto y Cómo funciona en escritorio y de inicio en móvil. Capturas temporales en `/tmp/solidaria-ui-*.png`.
- No se verificaron visualmente en esta revisión las pantallas que requieren sesión, ni otros motores de navegador o dispositivos físicos.
- Ajustes finales pedidos: 24 comprobaciones adicionales en los mismos seis anchos. Se confirmó por medición que los encabezados de Contacto y Buscador tienen idéntico ancho; la foto aparece en las cuatro secciones del inicio; Cómo funciona conserva la foto en portada y usa la trama original sin velo en el cuerpo. Sin fallos. Se reservó el espacio de la barra de desplazamiento con `scrollbar-gutter: stable` para evitar diferencias entre páginas largas y cortas.

- Ajuste posterior: las secciones de cómo donar y cómo crear campaña usan fondo cálido liso, sin fotografía; conservan las tarjetas y las franjas de trama.

- Contraste entre las secciones del inicio: Cómo donar usa fondo azul y círculos terracota oscuro con cifras doradas en negrita; Cómo crear campaña conserva el fondo crema y círculos azules. Ambas mantienen tarjetas claras y franjas de trama.

- Mis campañas y Mis donaciones: encabezados en tarjetas terracota suave sobre una franja exterior con la trama compartida. Pie compartido: separador de 4 px, sin trama, con degradado dorado y brillo champagne estático.

- Administración: fotografía limitada a la cabecera con título, bajada y tres estadísticas; el resto del panel usa la trama compartida. Se conservan el pie y el separador champagne de 4 px.
- Campañas con una sola foto: la galería secundaria ya se omite desde PHP; se retiró el padding superior del cuerpo cuando sigue directamente a la portada para eliminar la franja blanca vacía.
- Administración: velo terracota suave que cubre progresivamente solo la foto hacia el borde inferior. El patrón queda sin velo ni degradado. El footer conserva su separador champagne.

## Editor de creación de campañas

El encabezado usa el ancho compartido de la plataforma. A partir de 1024 px, el contenido se divide en navegación, sección editable y vista previa lateral. Historia, Fotos, Objetivos, Entrega y Revisión se pueden abrir en cualquier orden; cambiar de sección mantiene los campos y las fotos elegidas. En móvil y tablet, el formulario conserva su recorrido vertical y la vista previa se puede desplegar al final.

Las campañas de dinero ocultan y deshabilitan los datos de insumos y entrega. La revisión resume los campos actuales e indica las secciones incompletas. Al intentar publicar, se abre la sección del primer campo inválido para permitir corregirlo. Se mantienen el envío multipart, CSRF, la clave de un solo uso y las validaciones del servidor. La vista previa usa texto seguro y la portada seleccionada en el manejador de imágenes existente. No agrega guardado de borradores.

Archivos principales: `app/vistas/crear_campana.php`, `public_php/assets/css/crear_campana.css` y `public_php/assets/js/crear-campana-editor.js`. Los manejadores existentes de insumos, imágenes y mapa emiten eventos para actualizar la vista previa.

Comprobaciones:

- Sintaxis PHP y JavaScript y `git diff --check`: correctos.
- `php tests/run_tests.php controladores CrearCampanaController`: correcto, incluyendo publicación, datos inválidos, conservación de campos y rechazo de envíos duplicados.
- `php tests/run_tests.php controladores CrearCampanaImagenes`: correcto, incluyendo subida, portada y limpieza ante errores.
- `node tests/js/PuntoEntregaTest.js`: correcto.
- `python3 tests/ui/crear_campana_editor_check.py`: revisión con Firefox y la vista real renderizada con datos locales de prueba; navegación libre, conservación de campos, texto seguro en la vista previa, objetivos dinámicos, cambio dinero/insumos, foco de errores, revisión y portada de imágenes. El envío se intercepta y no se publican campañas. El mapa externo no se carga en esta prueba; su comportamiento se comprueba por separado con la prueba existente.
- Última ejecución del navegador: sin fallos en escritorio de 1024, 1280 y 1440 px, tablet de 768 px y viewport móvil real de 320 px. Se verificó también el contenido de `FormData` para confirmar que los campos de la modalidad inactiva no se envían. Capturas de escritorio y móvil inspeccionadas; no se probaron dispositivos físicos ni otros motores de navegador.
