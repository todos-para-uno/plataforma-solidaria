-- TodosPara.Uno - Fixture de actividad administrativa (SOLO DESARROLLO)
-- Requiere ejecutar antes tests/fixtures/usuarios_prueba.sql.
-- No modifica el esquema ni elimina registros existentes.
-- Identificadores por correo; seguro ante IDs autoincrementales distintos.
-- Los registros llevan prefijo [TEST ADMIN] para distinguirlos.
-- Ejecutable más de una vez: no duplica campañas, objetivos ni donaciones.

START TRANSACTION;

-- Catálogos exclusivos de esta fixture (sin interferir con los existentes).
INSERT INTO cat_camp (nombre)
SELECT '[TEST ADMIN] Solidaridad'
WHERE NOT EXISTS (SELECT 1 FROM cat_camp WHERE nombre = '[TEST ADMIN] Solidaridad');

INSERT INTO categoria_insumos (nombre)
SELECT '[TEST ADMIN] Útiles escolares'
WHERE NOT EXISTS (SELECT 1 FROM categoria_insumos WHERE nombre = '[TEST ADMIN] Útiles escolares');

INSERT INTO sub_categoria_insumos (id_categoria, nombre)
SELECT ci.id, '[TEST ADMIN] Cuadernos'
FROM categoria_insumos ci
WHERE ci.nombre = '[TEST ADMIN] Útiles escolares'
  AND NOT EXISTS (
      SELECT 1 FROM sub_categoria_insumos s
      WHERE s.id_categoria = ci.id AND s.nombre = '[TEST ADMIN] Cuadernos'
  );

-- Campañas monetarias de lucas y sofia.
INSERT INTO camp (owner_id, nombre, monetaria, categoria, logrado, descripcion, cbu, estado)
SELECT u.id, '[TEST ADMIN] Fondo de emergencia', 1, cat.id, 2500.00,
       'Campaña monetaria para validar el panel administrativo.', '0000000000000000000000', 'activa'
FROM usuarios u CROSS JOIN cat_camp cat
WHERE u.mail = 'lucas@ejemplo.com' AND cat.nombre = '[TEST ADMIN] Solidaridad'
  AND NOT EXISTS (SELECT 1 FROM camp c WHERE c.owner_id = u.id AND c.nombre = '[TEST ADMIN] Fondo de emergencia');

INSERT INTO camp (owner_id, nombre, monetaria, categoria, logrado, descripcion, cbu, estado)
SELECT u.id, '[TEST ADMIN] Apoyo comunitario', 1, cat.id, 1200.00,
       'Campaña monetaria finalizada para validar estados.', '0000000000000000000000', 'finalizada'
FROM usuarios u CROSS JOIN cat_camp cat
WHERE u.mail = 'sofia@ejemplo.com' AND cat.nombre = '[TEST ADMIN] Solidaridad'
  AND NOT EXISTS (SELECT 1 FROM camp c WHERE c.owner_id = u.id AND c.nombre = '[TEST ADMIN] Apoyo comunitario');

-- Campaña de insumos. Ubicación de prueba con coordenadas ficticias válidas.
-- La ubicación se crea solamente si todavía no existe la campaña.
INSERT INTO ubicaciones (provincia, localidad, latitud, longitud, referencia)
SELECT 'Buenos Aires', 'La Matanza', -34.700000, -58.600000, '[TEST ADMIN] Punto ficticio'
WHERE EXISTS (SELECT 1 FROM usuarios WHERE mail = 'valentina@ejemplo.com')
  AND NOT EXISTS (
      SELECT 1 FROM camp c INNER JOIN usuarios u ON u.id = c.owner_id
      WHERE u.mail = 'valentina@ejemplo.com' AND c.nombre = '[TEST ADMIN] Mochilas y cuadernos'
  );

INSERT INTO camp (owner_id, nombre, monetaria, categoria, logrado, descripcion, estado, id_ubicacion)
SELECT u.id, '[TEST ADMIN] Mochilas y cuadernos', 0, cat.id, 0.00,
       'Campaña de insumos para validar unidades y progreso.', 'activa', ub.id
FROM usuarios u CROSS JOIN cat_camp cat
INNER JOIN ubicaciones ub ON ub.referencia = '[TEST ADMIN] Punto ficticio'
WHERE u.mail = 'valentina@ejemplo.com' AND cat.nombre = '[TEST ADMIN] Solidaridad'
  AND NOT EXISTS (SELECT 1 FROM camp c WHERE c.owner_id = u.id AND c.nombre = '[TEST ADMIN] Mochilas y cuadernos')
  AND NOT EXISTS (SELECT 1 FROM camp c2 WHERE c2.id_ubicacion = ub.id);

-- Objetivos: un objetivo monetario por campaña y uno de cuadernos para insumos.
INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT c.id, NULL, NULL, 0.00, 10000.00
FROM camp c INNER JOIN usuarios u ON u.id = c.owner_id
WHERE u.mail = 'lucas@ejemplo.com' AND c.nombre = '[TEST ADMIN] Fondo de emergencia'
  AND NOT EXISTS (SELECT 1 FROM objetivos o WHERE o.id_camp = c.id);

INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT c.id, NULL, NULL, 0.00, 1200.00
FROM camp c INNER JOIN usuarios u ON u.id = c.owner_id
WHERE u.mail = 'sofia@ejemplo.com' AND c.nombre = '[TEST ADMIN] Apoyo comunitario'
  AND NOT EXISTS (SELECT 1 FROM objetivos o WHERE o.id_camp = c.id);

INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT c.id, ci.id, sci.id, 8.00, 40.00
FROM camp c INNER JOIN usuarios u ON u.id = c.owner_id
CROSS JOIN categoria_insumos ci
INNER JOIN sub_categoria_insumos sci ON sci.id_categoria = ci.id
WHERE u.mail = 'valentina@ejemplo.com' AND c.nombre = '[TEST ADMIN] Mochilas y cuadernos'
  AND ci.nombre = '[TEST ADMIN] Útiles escolares' AND sci.nombre = '[TEST ADMIN] Cuadernos'
  AND NOT EXISTS (SELECT 1 FROM objetivos o WHERE o.id_camp = c.id);

-- Donaciones monetarias: camila y mateo -> campaña de lucas.
INSERT INTO donacion (id_usuario, id_camp, cantidad, fecha_hora)
SELECT d.id, c.id, 1500.00, '2026-10-20 10:00:00'
FROM usuarios d CROSS JOIN camp c INNER JOIN usuarios o ON o.id = c.owner_id
WHERE d.mail = 'camila@ejemplo.com' AND o.mail = 'lucas@ejemplo.com'
  AND c.nombre = '[TEST ADMIN] Fondo de emergencia'
  AND NOT EXISTS (SELECT 1 FROM donacion x WHERE x.id_usuario = d.id AND x.id_camp = c.id
                  AND x.cantidad = 1500.00 AND x.fecha_hora = '2026-10-20 10:00:00');

INSERT INTO donacion (id_usuario, id_camp, cantidad, fecha_hora)
SELECT d.id, c.id, 1000.00, '2026-10-21 11:00:00'
FROM usuarios d CROSS JOIN camp c INNER JOIN usuarios o ON o.id = c.owner_id
WHERE d.mail = 'mateo@ejemplo.com' AND o.mail = 'lucas@ejemplo.com'
  AND c.nombre = '[TEST ADMIN] Fondo de emergencia'
  AND NOT EXISTS (SELECT 1 FROM donacion x WHERE x.id_usuario = d.id AND x.id_camp = c.id
                  AND x.cantidad = 1000.00 AND x.fecha_hora = '2026-10-21 11:00:00');

-- Donación de insumos: camila -> campaña de valentina.
INSERT INTO donacion (id_usuario, id_camp, id_categoria_insumos, id_sub_categoria_insumos, cantidad, fecha_hora)
SELECT d.id, c.id, ci.id, sci.id, 8.00, '2026-10-22 12:00:00'
FROM usuarios d CROSS JOIN camp c INNER JOIN usuarios o ON o.id = c.owner_id
CROSS JOIN categoria_insumos ci INNER JOIN sub_categoria_insumos sci ON sci.id_categoria = ci.id
WHERE d.mail = 'camila@ejemplo.com' AND o.mail = 'valentina@ejemplo.com'
  AND c.nombre = '[TEST ADMIN] Mochilas y cuadernos'
  AND ci.nombre = '[TEST ADMIN] Útiles escolares' AND sci.nombre = '[TEST ADMIN] Cuadernos'
  AND NOT EXISTS (SELECT 1 FROM donacion x WHERE x.id_usuario = d.id AND x.id_camp = c.id
                  AND x.cantidad = 8.00 AND x.fecha_hora = '2026-10-22 12:00:00');

COMMIT;

-- Verificación: lucas (1 campaña), sofia (1), valentina (1),
-- camila (2 donaciones), mateo (1), agustin (sin actividad).
SELECT u.nombre, u.mail,
       (SELECT COUNT(*) FROM camp c WHERE c.owner_id = u.id AND c.nombre LIKE '[TEST ADMIN]%') AS campanas_fixture,
       (SELECT COUNT(*) FROM donacion d INNER JOIN camp c ON c.id = d.id_camp
        WHERE d.id_usuario = u.id AND c.nombre LIKE '[TEST ADMIN]%') AS donaciones_fixture
FROM usuarios u
WHERE u.mail IN ('lucas@ejemplo.com','sofia@ejemplo.com','valentina@ejemplo.com',
                 'camila@ejemplo.com','mateo@ejemplo.com','agustin@ejemplo.com')
ORDER BY u.nombre;
