-- Seleccionar la base de la aplicación antes de importar este archivo.
-- Agrega solo las columnas y tablas que faltan. Se puede volver a ejecutar.
-- Conserva los datos y las columnas existentes. Compatible con MySQL y MariaDB.

CREATE TABLE IF NOT EXISTS estado_autenticacion (
    id_usuario INT NOT NULL,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_intento DATETIME(6) NULL,
    bloqueada_hasta DATETIME(6) NULL,

    PRIMARY KEY (id_usuario),
    CONSTRAINT fk_estado_autenticacion_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

SET @solidaria_migracion_sql = IF(
    EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'camp'
          AND COLUMN_NAME = 'imagen'
    ),
    'SELECT 1',
    'ALTER TABLE `camp` ADD COLUMN `imagen` VARCHAR(255) NULL AFTER descripcion'
);
PREPARE solidaria_migracion FROM @solidaria_migracion_sql;
EXECUTE solidaria_migracion;
DEALLOCATE PREPARE solidaria_migracion;

SET @solidaria_migracion_sql = IF(
    EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'baneados'
          AND COLUMN_NAME = 'fecha_unban'
    ),
    'SELECT 1',
    'ALTER TABLE `baneados` ADD COLUMN `fecha_unban` DATETIME NULL DEFAULT NULL AFTER fecha_fin'
);
PREPARE solidaria_migracion FROM @solidaria_migracion_sql;
EXECUTE solidaria_migracion;
DEALLOCATE PREPARE solidaria_migracion;

SET @solidaria_migracion_sql = IF(
    EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'baneados'
          AND COLUMN_NAME = 'motivo_unban'
    ),
    'SELECT 1',
    'ALTER TABLE `baneados` ADD COLUMN `motivo_unban` VARCHAR(500) NULL AFTER fecha_unban'
);
PREPARE solidaria_migracion FROM @solidaria_migracion_sql;
EXECUTE solidaria_migracion;
DEALLOCATE PREPARE solidaria_migracion;
