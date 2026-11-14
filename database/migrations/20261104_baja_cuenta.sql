-- Ejecutar una sola vez sobre una instalación existente (hacer respaldo antes).
ALTER TABLE usuarios ADD COLUMN estado_cuenta ENUM('activa','desactivada','eliminada') NOT NULL DEFAULT 'activa';
ALTER TABLE camp MODIFY COLUMN estado ENUM('activa','finalizada','suspendida','cerrada') NOT NULL DEFAULT 'activa';
ALTER TABLE camp ADD COLUMN motivo_cierre VARCHAR(80) NULL;
