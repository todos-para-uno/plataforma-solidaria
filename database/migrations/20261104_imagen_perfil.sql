-- Seleccionar la base de la aplicación. Conserva las cuentas existentes.
-- Compatible con MariaDB y MySQL; se puede volver a ejecutar.
SET @falta_apellido = (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'apellido');
SET @sql_perfil = IF(@falta_apellido, 'ALTER TABLE usuarios ADD COLUMN apellido VARCHAR(100) NULL', 'SELECT 1');
PREPARE stmt_perfil FROM @sql_perfil;
EXECUTE stmt_perfil;
DEALLOCATE PREPARE stmt_perfil;

SET @falta_avatar = (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'avatar');
SET @sql_perfil = IF(@falta_avatar, 'ALTER TABLE usuarios ADD COLUMN avatar VARCHAR(80) NULL', 'SELECT 1');
PREPARE stmt_perfil FROM @sql_perfil;
EXECUTE stmt_perfil;
DEALLOCATE PREPARE stmt_perfil;
