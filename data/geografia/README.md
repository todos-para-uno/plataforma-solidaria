# Catálogo geográfico de Argentina

`localidades-por-provincia.json` contiene los nombres de provincias y sus localidades
para validar `Ubicaciones`. Los nombres se conservan tal como aparecen en Georef;
la comparación distingue mayúsculas y tildes. En el formulario se deberán ofrecer
estas mismas opciones.

Fuente: [Georef, descarga de la base completa](https://www.argentina.gob.ar/georef/descarga-de-la-base-completa).
Descargado el 22 de octubre de 2026 desde:

- https://apis.datos.gob.ar/georef/api/provincias.json
- https://apis.datos.gob.ar/georef/api/localidades.json

Para preparar el archivo se agruparon las localidades por el nombre de su provincia,
se conservaron las 24 jurisdicciones del catálogo (incluida CABA), se quitaron nombres
repetidos dentro de cada provincia y se ordenaron alfabéticamente. Un mismo nombre
puede existir en varias provincias. Este archivo valida nombres y pertenencia a la
provincia; no identifica localidades homónimas dentro de una misma provincia.

La aplicación lee el archivo local; no consulta la API al crear una ubicación.
Para actualizarlo, repetir la descarga y la agrupación de ambas fuentes oficiales.
Las coordenadas del punto de recepción se obtienen del mapa, no de este catálogo.
