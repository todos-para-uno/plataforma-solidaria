-- Ejecutar una vez sobre la base existente, antes de usar el código actualizado.
-- Si una columna ya existe, omitir su ADD COLUMN. No modifica registros existentes.
ALTER TABLE camp
    ADD COLUMN imagen VARCHAR(255) NULL AFTER descripcion;

ALTER TABLE baneados
    ADD COLUMN unban BOOLEAN NOT NULL DEFAULT FALSE AFTER fecha_fin,
    ADD COLUMN motivo_unban VARCHAR(500) NULL AFTER unban;
