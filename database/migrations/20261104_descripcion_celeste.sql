-- Seleccionar la base de la aplicación antes de importar este archivo.
-- Actualiza la descripción del nivel Celeste. Se puede volver a ejecutar.
UPDATE validaciones
SET descripcion = 'Usuario con documento y sin donaciones de usuarios registrados'
WHERE nivel = 2;
