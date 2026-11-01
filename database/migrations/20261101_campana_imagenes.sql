-- Seleccionar la base de la aplicación antes de importar este archivo.
-- Crea la tabla de imágenes subidas por los creadores de campañas. Se puede volver a ejecutar.
-- Compatible con MySQL 8.0.16+ y MariaDB 10.2+ (ambos aplican la restricción CHECK).
--
-- Por qué una tabla aparte y no un JSON o texto separado por comas en `camp`:
--   * Cada imagen es un registro propio: se puede borrar o reordenar una sin reescribir las demás.
--   * La base garantiza el límite: `orden` solo admite 0 a 3 y no se repite en la misma campaña,
--     así que nunca puede haber más de 4 imágenes aunque falle la validación de PHP.
--   * ON DELETE CASCADE elimina las filas al borrar la campaña; con un JSON quedarían rutas sueltas.
--   * Las consultas son SQL simple (JOIN, ORDER BY orden) sin parsear texto en PHP.
-- La columna `camp.imagen` se mantiene: sigue sirviendo para las imágenes ilustrativas
-- de assets/imagenes/imagenes-camp. Las fotos subidas por los usuarios van en esta tabla.

CREATE TABLE IF NOT EXISTS campana_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_camp INT NOT NULL,
    -- Ruta relativa a public_php, por ejemplo: subidas/campanas/3f9a...c1.webp
    ruta VARCHAR(255) NOT NULL,
    -- Posición en la galería. La imagen con orden 0 es la portada.
    orden TINYINT UNSIGNED NOT NULL,
    fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_campana_imagenes_orden UNIQUE (id_camp, orden),
    CONSTRAINT ck_campana_imagenes_orden CHECK (orden BETWEEN 0 AND 3),
    CONSTRAINT fk_campana_imagenes_camp
        FOREIGN KEY (id_camp) REFERENCES camp(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
