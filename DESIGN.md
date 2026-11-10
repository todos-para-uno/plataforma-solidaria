# Identidad visual de todospara.uno

## Dirección

Una comunidad cercana: fotografía del atardecer, superficies de papel, títulos serif y una trama geométrica que representa conexiones. Se conserva la paleta del logo: azul `#153a6b`, verde `#2f6b3f`, terracota `#b8472b` y amarillo `#f4b223`.

La fotografía original `sol.webp` tiene prioridad en la portada y sigue presente como fondo compartido de las pantallas interiores. También aparece en el listado de campañas del inicio. Las secciones de cómo donar y cómo crear campaña contrastan con fondos lisos azul y crema, respectivamente, y franjas de trama, sin repetir la fotografía. Ambas mantienen tarjetas claras; donar usa círculos terracota oscuro con cifras doradas en negrita y crear usa círculos azules con cifras doradas. Los formularios y encabezados usan superficies opacas para mantener la lectura. En **Cómo funciona**, la portada conserva la fotografía original; la trama se usa en el cuerpo de preguntas, con el trazado original nítido detrás de tarjetas opacas.

## Sistema compartido

`public_php/assets/css/base.css` centraliza colores, tipografía, espacios, controles, radios y sombras. Georgia aporta cercanía a los títulos y Trebuchet MS (con Segoe UI como alternativa) mantiene una lectura cómoda. No se descargan fuentes ni se agregan dependencias.

- `.portada-cuenta` y `.portada-cuenta-tarjeta`: encabezados compartidos de Mis campañas y Mis donaciones, con fondo exterior de trama y tarjeta interior terracota suave.
- `.encabezado-trama`: encabezado de pantalla con margen decorativo y texto sobre papel.
- `.etiqueta-seccion`: contexto breve sobre el título.
- `.estado-vacio`: explicación y siguiente acción cuando todavía no hay contenido.
- `.tarjeta`, `.boton`, `.campo`, `.alerta` e `.insignia`: componentes existentes compartidos.
- `--patron-solidario`: referencia al SVG original aportado por el usuario, conservando su tamaño de 152 px, trazado y opacidad. La base amarilla se aplica desde CSS.

Los ejemplos se mantienen en `app/vistas/guia-estilos.php`. Las campañas utilizan datos e imágenes existentes; los huecos sin foto reciben la trama decorativa.

## Comportamiento y adaptación

Los cambios de hover son cromáticos o de sombra, sin zoom ni desplazamiento. Se conserva foco visible, deshabilitado, error por campo, confirmaciones y validación del servidor. Los estados de carga locales existentes (búsqueda del mapa, administración y previsualización de imágenes) continúan funcionando. No se agregan skeletons a respuestas PHP que llegan renderizadas completas.

Las grillas se ajustan al ancho disponible; los pasos y la invitación a crear campañas pasan a una columna. Los encabezados reducen su margen decorativo en móvil. La portada mantiene su tamaño destacado; las fotos secundarias se muestran en miniaturas de hasta 720 × 540 px, conservando la imagen completa y el enlace al original. Se respeta `prefers-reduced-motion`.

## Validación

Las comprobaciones del refactor se registran en `docs/refactor-ui.md`. No se modifican rutas, autorización ni persistencia para fines visuales.

## Creación de campañas

El editor conserva la cabecera amplia con trama y tarjeta terracota suave. En escritorio (desde 1024 px), dispone de navegación libre por secciones, un área de edición y una vista previa lateral. En móvil y tablet, todas las secciones aplicables quedan en un flujo vertical; la vista previa es desplegable. La revisión final resume los datos y ofrece enlaces a las secciones pendientes. Los errores de validación revelan la sección correspondiente antes de enfocar el campo. No se guardan borradores en el navegador ni se agregan dependencias.
