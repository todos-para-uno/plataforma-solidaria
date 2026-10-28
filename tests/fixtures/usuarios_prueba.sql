
-- TodosPara.Uno - Usuarios de prueba
-- 23 usuarios: 5 administradores y 18 usuarios comunes.
-- Contraseña de todos: Prueba2026!
-- Ejecutar sobre la base de datos del proyecto.

START TRANSACTION;

CREATE TEMPORARY TABLE IF NOT EXISTS tmp_nombres_prueba (
    posicion INT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    es_admin TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TEMPORARY TABLE IF NOT EXISTS tmp_mails_prueba (
    posicion INT PRIMARY KEY,
    mail VARCHAR(150) NOT NULL
);

DELETE FROM tmp_nombres_prueba;
DELETE FROM tmp_mails_prueba;

-- Array de nombres
INSERT INTO tmp_nombres_prueba (posicion, nombre, es_admin)
VALUES
    (1,  'cristian', 1),
    (2,  'fede',     1),
    (3,  'maxi',     1),
    (4,  'silvina',  1),
    (5,  'martina',  1),
    (6,  'lucas',    0),
    (7,  'sofia',    0),
    (8,  'valentina',0),
    (9,  'mateo',    0),
    (10, 'camila',   0),
    (11, 'agustin',  0),
    (12, 'florencia',0),
    (13, 'nicolas',  0),
    (14, 'julieta',  0),
    (15, 'tomas',    0),
    (16, 'milagros', 0),
    (17, 'santiago', 0),
    (18, 'rocio',    0),
    (19, 'franco',   0),
    (20, 'carolina', 0),
    (21, 'bruno',    0),
    (22, 'maria',    0),
    (23, 'facundo',  0);

-- Array de correos generado a partir de los nombres
INSERT INTO tmp_mails_prueba (posicion, mail)
SELECT posicion, CONCAT(nombre, '@ejemplo.com')
FROM tmp_nombres_prueba;

-- Insertar solamente usuarios que todavía no existan.
-- Todos comienzan en nivel de validación 1 (Blanco).
INSERT INTO usuarios (
    nombre,
    mail,
    pass,
    verificado,
    admin,
    id_validacion
)
SELECT
    n.nombre,
    m.mail,
    '$2y$12$MqqzIMljMNL/uLKRAk7kbO0.ng37ylSSurDRaqP807RvAw1ze3xve',
    0,
    n.es_admin,
    v.id
FROM tmp_nombres_prueba n
INNER JOIN tmp_mails_prueba m
    ON m.posicion = n.posicion
INNER JOIN validaciones v
    ON v.nivel = 1
WHERE NOT EXISTS (
    SELECT 1
    FROM usuarios u
    WHERE u.mail = m.mail
);

COMMIT;

-- Verificar los usuarios del conjunto de prueba.
SELECT
    u.id,
    u.nombre,
    u.mail,
    u.admin,
    v.nombre AS validacion
FROM usuarios u
INNER JOIN validaciones v
    ON v.id = u.id_validacion
INNER JOIN tmp_mails_prueba m
    ON m.mail = u.mail
ORDER BY u.admin DESC, u.nombre ASC;

DROP TEMPORARY TABLE IF EXISTS tmp_mails_prueba;
DROP TEMPORARY TABLE IF EXISTS tmp_nombres_prueba;
