-- Para bases que importaron los datos de demostración antes de agregar el CBU.
-- Se puede ejecutar más de una vez; no modifica campañas de otros usuarios.
USE todosparauno;

UPDATE camp AS c
JOIN usuarios AS u ON u.id = c.owner_id
SET c.cbu = '0000000000000000000001'
WHERE u.mail = 'ana@todosparauno.test'
  AND c.nombre = 'Mochilas y útiles para estudiar'
  AND c.monetaria = 1
  AND c.cbu IS NULL;
