-- Seleccionar la base de la aplicación antes de importar este archivo.
-- Agrega solo las columnas que faltan. Se puede volver a ejecutar.
-- Conserva los datos y las columnas existentes. Compatible con MySQL y MariaDB.

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
