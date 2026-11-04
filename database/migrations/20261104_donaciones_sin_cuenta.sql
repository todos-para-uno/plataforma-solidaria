-- Seleccionar la base de la aplicación antes de importar este archivo.
-- Permite registrar donaciones sin una cuenta asociada; conserva los datos
-- y la clave foránea a usuarios. Se puede volver a ejecutar.
ALTER TABLE donacion MODIFY COLUMN id_usuario INT NULL;
