-- Agrega cuatro campañas a una base que ya importó los datos de demostración.
-- Para una instalación nueva basta database/schema.sql; este archivo se puede repetir.
USE todosparauno;
START TRANSACTION;

INSERT IGNORE INTO cat_camp (nombre) VALUES ('Emergencias');
INSERT IGNORE INTO categoria_insumos (nombre) VALUES
    ('Ayuda para animales'), ('Material sanitario'), ('Equipamiento comunitario');

SET @animales_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Ayuda para animales');
SET @salud_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Material sanitario');
SET @comunidad_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Equipamiento comunitario');
INSERT IGNORE INTO sub_categoria_insumos (id_categoria, nombre) VALUES
    (@animales_insumos, 'Alimento balanceado (kg)'), (@animales_insumos, 'Mantas para mascotas (unidades)'),
    (@salud_insumos, 'Gasas (paquetes)'), (@salud_insumos, 'Alcohol (litros)'),
    (@comunidad_insumos, 'Mesas plegables (unidades)'), (@comunidad_insumos, 'Sillas (unidades)');

INSERT INTO ubicaciones (provincia, localidad, latitud, longitud, referencia)
SELECT 'Buenos Aires', 'La Plata', -34.926000, -57.967000, 'Refugio de animales, todos los días de 9 a 18'
WHERE NOT EXISTS (SELECT 1 FROM ubicaciones WHERE referencia = 'Refugio de animales, todos los días de 9 a 18');
INSERT INTO ubicaciones (provincia, localidad, latitud, longitud, referencia)
SELECT 'Buenos Aires', 'La Plata', -34.918000, -57.944000, 'Salita barrial, lunes a viernes de 8 a 16'
WHERE NOT EXISTS (SELECT 1 FROM ubicaciones WHERE referencia = 'Salita barrial, lunes a viernes de 8 a 16');
INSERT INTO ubicaciones (provincia, localidad, latitud, longitud, referencia)
SELECT 'Buenos Aires', 'La Plata', -34.930000, -57.959000, 'Club del barrio, sábados de 9 a 13'
WHERE NOT EXISTS (SELECT 1 FROM ubicaciones WHERE referencia = 'Club del barrio, sábados de 9 a 13');
SET @lugar_animales = (SELECT id FROM ubicaciones WHERE referencia = 'Refugio de animales, todos los días de 9 a 18' ORDER BY id LIMIT 1);
SET @lugar_salud = (SELECT id FROM ubicaciones WHERE referencia = 'Salita barrial, lunes a viernes de 8 a 16' ORDER BY id LIMIT 1);
SET @lugar_comunidad = (SELECT id FROM ubicaciones WHERE referencia = 'Club del barrio, sábados de 9 a 13' ORDER BY id LIMIT 1);

SET @pepe = (SELECT id FROM usuarios WHERE mail = 'pepe@todosparauno.test');
SET @admin_celeste = (SELECT id FROM usuarios WHERE mail = 'admin.celeste@todosparauno.test');
SET @ana = (SELECT id FROM usuarios WHERE mail = 'ana@todosparauno.test');
SET @lola = (SELECT id FROM usuarios WHERE mail = 'lola@todosparauno.test');

INSERT INTO camp (owner_id, nombre, monetaria, categoria, logrado, descripcion, imagen, cbu, estado, id_ubicacion)
SELECT @pepe, 'Alimento para el refugio', 0, (SELECT id FROM cat_camp WHERE nombre = 'Animales'), 0,
       'Buscamos alimento y mantas para los animales del refugio.', 'imagenes-camp/animales/animales-01.webp', NULL, 'activa', @lugar_animales
WHERE NOT EXISTS (SELECT 1 FROM camp WHERE owner_id = @pepe AND nombre = 'Alimento para el refugio');
INSERT INTO camp (owner_id, nombre, monetaria, categoria, logrado, descripcion, imagen, cbu, estado, id_ubicacion)
SELECT @admin_celeste, 'Insumos para la salita', 0, (SELECT id FROM cat_camp WHERE nombre = 'Salud'), 0,
       'Reunimos materiales de cuidado para la salita del barrio.', 'imagenes-camp/salud/salud-01.webp', NULL, 'activa', @lugar_salud
WHERE NOT EXISTS (SELECT 1 FROM camp WHERE owner_id = @admin_celeste AND nombre = 'Insumos para la salita');
INSERT INTO camp (owner_id, nombre, monetaria, categoria, logrado, descripcion, imagen, cbu, estado, id_ubicacion)
SELECT @ana, 'Equipamiento para el club', 0, (SELECT id FROM cat_camp WHERE nombre = 'Comunidad'), 0,
       'Necesitamos mesas y sillas para las actividades comunitarias.', 'imagenes-camp/comunidad/comunidad-01.webp', NULL, 'activa', @lugar_comunidad
WHERE NOT EXISTS (SELECT 1 FROM camp WHERE owner_id = @ana AND nombre = 'Equipamiento para el club');
INSERT INTO camp (owner_id, nombre, monetaria, categoria, logrado, descripcion, imagen, cbu, estado, id_ubicacion)
SELECT @lola, 'Fondo para emergencias', 1, (SELECT id FROM cat_camp WHERE nombre = 'Emergencias'), 0,
       'Reunimos aportes para asistir necesidades urgentes de la comunidad.', 'imagenes-camp/emergencias/emergencias-01.webp', '0000000000000000000002', 'activa', NULL
WHERE NOT EXISTS (SELECT 1 FROM camp WHERE owner_id = @lola AND nombre = 'Fondo para emergencias');

SET @camp_animales = (SELECT id FROM camp WHERE owner_id = @pepe AND nombre = 'Alimento para el refugio' ORDER BY id LIMIT 1);
SET @camp_salud = (SELECT id FROM camp WHERE owner_id = @admin_celeste AND nombre = 'Insumos para la salita' ORDER BY id LIMIT 1);
SET @camp_comunidad = (SELECT id FROM camp WHERE owner_id = @ana AND nombre = 'Equipamiento para el club' ORDER BY id LIMIT 1);
SET @camp_emergencias = (SELECT id FROM camp WHERE owner_id = @lola AND nombre = 'Fondo para emergencias' ORDER BY id LIMIT 1);

INSERT INTO campana_imagenes (id_camp, ruta, orden)
SELECT @camp_animales, 'assets/imagenes/imagenes-camp/animales/animales-01.webp', 0
WHERE NOT EXISTS (SELECT 1 FROM campana_imagenes WHERE id_camp = @camp_animales AND orden = 0);
INSERT INTO campana_imagenes (id_camp, ruta, orden)
SELECT @camp_animales, 'assets/imagenes/imagenes-camp/animales/animales-02.webp', 1
WHERE NOT EXISTS (SELECT 1 FROM campana_imagenes WHERE id_camp = @camp_animales AND orden = 1);
INSERT INTO campana_imagenes (id_camp, ruta, orden)
SELECT @camp_salud, 'assets/imagenes/imagenes-camp/salud/salud-01.webp', 0
WHERE NOT EXISTS (SELECT 1 FROM campana_imagenes WHERE id_camp = @camp_salud AND orden = 0);
INSERT INTO campana_imagenes (id_camp, ruta, orden)
SELECT @camp_salud, 'assets/imagenes/imagenes-camp/salud/salud-02.webp', 1
WHERE NOT EXISTS (SELECT 1 FROM campana_imagenes WHERE id_camp = @camp_salud AND orden = 1);
INSERT INTO campana_imagenes (id_camp, ruta, orden)
SELECT @camp_comunidad, 'assets/imagenes/imagenes-camp/comunidad/comunidad-01.webp', 0
WHERE NOT EXISTS (SELECT 1 FROM campana_imagenes WHERE id_camp = @camp_comunidad AND orden = 0);
INSERT INTO campana_imagenes (id_camp, ruta, orden)
SELECT @camp_emergencias, 'assets/imagenes/imagenes-camp/emergencias/emergencias-01.webp', 0
WHERE NOT EXISTS (SELECT 1 FROM campana_imagenes WHERE id_camp = @camp_emergencias AND orden = 0);

SET @balanceado = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @animales_insumos AND nombre = 'Alimento balanceado (kg)');
SET @mantas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @animales_insumos AND nombre = 'Mantas para mascotas (unidades)');
SET @gasas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @salud_insumos AND nombre = 'Gasas (paquetes)');
SET @alcohol = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @salud_insumos AND nombre = 'Alcohol (litros)');
SET @mesas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @comunidad_insumos AND nombre = 'Mesas plegables (unidades)');
SET @sillas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @comunidad_insumos AND nombre = 'Sillas (unidades)');

INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT @camp_animales, @animales_insumos, @balanceado, 0, 120
WHERE NOT EXISTS (SELECT 1 FROM objetivos WHERE id_camp = @camp_animales AND id_sub_categoria_insumos = @balanceado);
INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT @camp_animales, @animales_insumos, @mantas, 0, 25
WHERE NOT EXISTS (SELECT 1 FROM objetivos WHERE id_camp = @camp_animales AND id_sub_categoria_insumos = @mantas);
INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT @camp_salud, @salud_insumos, @gasas, 0, 60
WHERE NOT EXISTS (SELECT 1 FROM objetivos WHERE id_camp = @camp_salud AND id_sub_categoria_insumos = @gasas);
INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT @camp_salud, @salud_insumos, @alcohol, 0, 30
WHERE NOT EXISTS (SELECT 1 FROM objetivos WHERE id_camp = @camp_salud AND id_sub_categoria_insumos = @alcohol);
INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT @camp_comunidad, @comunidad_insumos, @mesas, 0, 10
WHERE NOT EXISTS (SELECT 1 FROM objetivos WHERE id_camp = @camp_comunidad AND id_sub_categoria_insumos = @mesas);
INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT @camp_comunidad, @comunidad_insumos, @sillas, 0, 40
WHERE NOT EXISTS (SELECT 1 FROM objetivos WHERE id_camp = @camp_comunidad AND id_sub_categoria_insumos = @sillas);
INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo)
SELECT @camp_emergencias, NULL, NULL, 0, 150000
WHERE NOT EXISTS (SELECT 1 FROM objetivos WHERE id_camp = @camp_emergencias);

COMMIT;
