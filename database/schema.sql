CREATE DATABASE IF NOT EXISTS todosparauno;
USE todosparauno;

-- Niveles de confianza según documento cargado y donaciones realizadas o recibidas.

CREATE TABLE validaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nivel INT NOT NULL UNIQUE,
    nombre VARCHAR(30) NOT NULL,
    descripcion VARCHAR(255));

INSERT INTO validaciones (nivel, nombre, descripcion)
    VALUES (1, 'Blanco', 'Usuario sin DNI o pasaporte cargado'),
    (2, 'Celeste', 'Usuario con documento y sin donaciones'),
    (3, 'Azul', 'Usuario con documento y una o dos donaciones'),
    (4, 'Violeta', 'Usuario con documento y tres o más donaciones');

-- Se cuentan donaciones realizadas y recibidas; sin documento permanece Blanco.

-- Usuarios

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    mail VARCHAR(150) NOT NULL UNIQUE, -- la cuenta estará asociada al mail pero no será la ID obviamente!
    telefono VARCHAR(30),
    -- documento es varchar porque puede ser un pasaporte. 
    -- Ejemplos: Tipo DNI, Numero 35123456 
    -- Tipo Pasaporte, Numero AAB123456
    tipo_documento ENUM('DNI', 'Pasaporte') NULL,
    documento VARCHAR(30) NULL,
    pass VARCHAR(255) NOT NULL,
    verificado BOOLEAN DEFAULT FALSE,
    admin BOOLEAN DEFAULT FALSE,
    id_validacion INT NOT NULL,
    FOREIGN KEY (id_validacion) REFERENCES validaciones(id));

-- Estado de autenticación por usuario
CREATE TABLE estado_autenticacion (
    id_usuario INT NOT NULL,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_intento DATETIME(6) NULL,
    bloqueada_hasta DATETIME(6) NULL,

    PRIMARY KEY (id_usuario),
    CONSTRAINT fk_estado_autenticacion_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- categorias de campañas
CREATE TABLE cat_camp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);


-- manejo de insumos: categorias de insumos y subcategorías

CREATE TABLE categoria_insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE -- unique para evitar que haya 2 categorías con el mismo nombre
);

CREATE TABLE sub_categoria_insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
     -- permite la misma subcategoría en DISTINTAS categorías, pero no en la misma categoría. Lo mismo con el ID
    FOREIGN KEY (id_categoria) REFERENCES categoria_insumos(id), UNIQUE (id_categoria, nombre), UNIQUE (id, id_categoria)
);


-- REGLAS DE PROGRAMACION
-- Si la campaña tiene id_ubicacion NULL -> se buscará por "virtual"
-- Una campaña monetaria no necesariamente recibe una zona
-- Para las campañas de insumos, PHP debe exigir una ubicacion y por lo tanto no será virtual
-- Al crear una campaña de insumos, PHP guarda primero la ubicación y luego utiliza su ID para guardar la campaña.
-- Conviene realizar ambas operaciones dentro de una transacción: si alguna falla, se deshacen ambas.

-- campañas - y sus ubicaciones si tiene campo de ubicacion
CREATE TABLE ubicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provincia VARCHAR(100) NOT NULL,
    localidad VARCHAR(100) NOT NULL,
    latitud DECIMAL(9,6) NOT NULL,
    longitud DECIMAL(9,6) NOT NULL,
    referencia VARCHAR(150) NULL
);

CREATE TABLE camp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    monetaria BOOLEAN NOT NULL,
    categoria INT NOT NULL,
    logrado DECIMAL(12,2) DEFAULT 0,
    descripcion TEXT,
    -- Ruta relativa a assets/imagenes/imagenes-camp, por ejemplo: alimentos/alimentos-01.webp.
    imagen VARCHAR(255) NULL,
    cbu VARCHAR(22),
    estado ENUM('activa', 'finalizada', 'suspendida') NOT NULL DEFAULT 'activa',
    id_ubicacion INT NULL UNIQUE,
    FOREIGN KEY (owner_id) REFERENCES usuarios(id),
    FOREIGN KEY (categoria) REFERENCES cat_camp(id),
    FOREIGN KEY (id_ubicacion) REFERENCES ubicaciones(id)
);

CREATE TABLE IF NOT EXISTS campana_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_camp INT NOT NULL,
    -- Ruta relativa a public_php, por ejemplo: subidas/campanas/3f9a...c1.webp
    ruta VARCHAR(255) NOT NULL,
    -- Posición en la galería. La imagen con orden 0 es la portada.
    orden TINYINT UNSIGNED NOT NULL,
    fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_campana_imagenes_orden UNIQUE (id_camp, orden),
    CONSTRAINT ck_campana_imagenes_orden CHECK (orden BETWEEN 0 AND 3),
    CONSTRAINT fk_campana_imagenes_camp
        FOREIGN KEY (id_camp) REFERENCES camp(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- objetivos
CREATE TABLE objetivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_camp INT NOT NULL,
    id_categoria_insumos INT,
    id_sub_categoria_insumos INT,
    parcial DECIMAL(12,2) DEFAULT 0,
    objetivo DECIMAL(12,2) NOT NULL,

    FOREIGN KEY (id_camp) REFERENCES camp(id),

    FOREIGN KEY ( id_sub_categoria_insumos, id_categoria_insumos)
    REFERENCES sub_categoria_insumos(id, id_categoria)
);

-- donaciones
CREATE TABLE donacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_camp INT NOT NULL,
    id_categoria_insumos INT,
    id_sub_categoria_insumos INT,
    cantidad DECIMAL(12,2) NOT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),

    FOREIGN KEY (id_camp) REFERENCES camp(id),

    FOREIGN KEY (id_sub_categoria_insumos, id_categoria_insumos)
    REFERENCES sub_categoria_insumos(id, id_categoria)
);

-- mensajes
CREATE TABLE mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_emisor INT NOT NULL,
    id_receptor INT NOT NULL,
    contenido VARCHAR(255) NOT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    leido BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_emisor) REFERENCES usuarios(id),
    FOREIGN KEY (id_receptor) REFERENCES usuarios(id)
);

-- Consultas al equipo; también se permite escribir sin tener una cuenta.
CREATE TABLE contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    nombre VARCHAR(100) NOT NULL,
    mail VARCHAR(150) NOT NULL,
    asunto VARCHAR(30) NOT NULL,
    contenido VARCHAR(1000) NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    correo_enviado BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- baneados
CREATE TABLE baneados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_admin INT NOT NULL,
    motivo VARCHAR(500) NOT NULL,
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME,
    -- Levantamiento manual del baneo; fecha_fin conserva el vencimiento previsto.
    fecha_unban DATETIME NULL DEFAULT NULL,
    motivo_unban VARCHAR(500) NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_admin) REFERENCES usuarios(id)
);

-- Datos de demostración para una instalación nueva. Todas las cuentas usan
-- la contraseña Demo2026! y direcciones .test, que no reciben correo real.
-- Las fotos ya vienen en public_php/assets/imagenes/imagenes-camp/;
-- SQL no necesita descomprimir ni copiar archivos.
START TRANSACTION;

INSERT INTO cat_camp (nombre) VALUES
    ('Alimentos'), ('Abrigo'), ('Educación'), ('Salud'), ('Animales'), ('Comunidad'), ('Emergencias');

INSERT INTO categoria_insumos (nombre) VALUES
    ('Alimentos no perecederos'), ('Ropa y abrigo'), ('Útiles escolares'), ('Higiene'),
    ('Ayuda para animales'), ('Material sanitario'), ('Equipamiento comunitario');

SET @alimentos_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Alimentos no perecederos');
SET @abrigo_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Ropa y abrigo');
SET @utiles_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Útiles escolares');
SET @higiene_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Higiene');
SET @animales_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Ayuda para animales');
SET @salud_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Material sanitario');
SET @comunidad_insumos = (SELECT id FROM categoria_insumos WHERE nombre = 'Equipamiento comunitario');
INSERT INTO sub_categoria_insumos (id_categoria, nombre) VALUES
    (@alimentos_insumos, 'Arroz (kg)'), (@alimentos_insumos, 'Leche larga vida (litros)'),
    (@alimentos_insumos, 'Fideos (paquetes)'), (@abrigo_insumos, 'Camperas (unidades)'),
    (@abrigo_insumos, 'Frazadas (unidades)'), (@utiles_insumos, 'Cuadernos (unidades)'),
    (@utiles_insumos, 'Lápices (cajas)'), (@higiene_insumos, 'Jabón (unidades)'),
    (@animales_insumos, 'Alimento balanceado (kg)'), (@animales_insumos, 'Mantas para mascotas (unidades)'),
    (@salud_insumos, 'Gasas (paquetes)'), (@salud_insumos, 'Alcohol (litros)'),
    (@comunidad_insumos, 'Mesas plegables (unidades)'), (@comunidad_insumos, 'Sillas (unidades)');

-- El nivel Blanco no tiene documento. Azul tiene una donación y Violeta tiene tres.
-- El nivel se recalcula al navegar: estas donaciones mantienen los niveles coherentes.
SET @clave_demo = '$2y$12$lixaxw6J4DUb6pes9pMnc.NVUZmoz67CzYb1in4jZcVYPfzn7Lq0W';
INSERT INTO usuarios (nombre, mail, tipo_documento, documento, pass, verificado, admin, id_validacion) VALUES
    ('Admin Blanco', 'admin.blanco@todosparauno.test', NULL, NULL, @clave_demo, 0, 1, (SELECT id FROM validaciones WHERE nivel = 1)),
    ('Admin Celeste', 'admin.celeste@todosparauno.test', 'Pasaporte', 'DEMOA12345', @clave_demo, 0, 1, (SELECT id FROM validaciones WHERE nivel = 2)),
    ('Ana Sin Documento', 'ana@todosparauno.test', NULL, NULL, @clave_demo, 0, 0, (SELECT id FROM validaciones WHERE nivel = 1)),
    ('Bruno Azul', 'bruno@todosparauno.test', 'DNI', '30123456', @clave_demo, 0, 0, (SELECT id FROM validaciones WHERE nivel = 3)),
    ('Carla Violeta', 'carla@todosparauno.test', 'Pasaporte', 'DEMOC54321', @clave_demo, 0, 0, (SELECT id FROM validaciones WHERE nivel = 4)),
    ('Pepe', 'pepe@todosparauno.test', 'DNI', '40987654', @clave_demo, 0, 0, (SELECT id FROM validaciones WHERE nivel = 2)),
    ('Lola', 'lola@todosparauno.test', 'Pasaporte', 'DEMOL12345', @clave_demo, 0, 0, (SELECT id FROM validaciones WHERE nivel = 4));

SET @ana = (SELECT id FROM usuarios WHERE mail = 'ana@todosparauno.test');
SET @bruno = (SELECT id FROM usuarios WHERE mail = 'bruno@todosparauno.test');
SET @carla = (SELECT id FROM usuarios WHERE mail = 'carla@todosparauno.test');
SET @lola = (SELECT id FROM usuarios WHERE mail = 'lola@todosparauno.test');
SET @pepe = (SELECT id FROM usuarios WHERE mail = 'pepe@todosparauno.test');
SET @admin_celeste = (SELECT id FROM usuarios WHERE mail = 'admin.celeste@todosparauno.test');
SET @cat_alimentos = (SELECT id FROM cat_camp WHERE nombre = 'Alimentos');
SET @cat_abrigo = (SELECT id FROM cat_camp WHERE nombre = 'Abrigo');
SET @cat_educacion = (SELECT id FROM cat_camp WHERE nombre = 'Educación');
SET @cat_salud = (SELECT id FROM cat_camp WHERE nombre = 'Salud');
SET @cat_animales = (SELECT id FROM cat_camp WHERE nombre = 'Animales');
SET @cat_comunidad = (SELECT id FROM cat_camp WHERE nombre = 'Comunidad');
SET @cat_emergencias = (SELECT id FROM cat_camp WHERE nombre = 'Emergencias');

INSERT INTO ubicaciones (provincia, localidad, latitud, longitud, referencia) VALUES
    ('Buenos Aires', 'La Plata', -34.921450, -57.954530, 'Centro comunitario, lunes a viernes de 9 a 17'),
    ('Buenos Aires', 'La Plata', -34.915800, -57.950100, 'Biblioteca barrial, sábados de 10 a 13'),
    ('Buenos Aires', 'La Plata', -34.926000, -57.967000, 'Refugio de animales, todos los días de 9 a 18'),
    ('Buenos Aires', 'La Plata', -34.918000, -57.944000, 'Salita barrial, lunes a viernes de 8 a 16'),
    ('Buenos Aires', 'La Plata', -34.930000, -57.959000, 'Club del barrio, sábados de 9 a 13');
SET @lugar_alimentos = (SELECT id FROM ubicaciones WHERE referencia = 'Centro comunitario, lunes a viernes de 9 a 17');
SET @lugar_abrigo = (SELECT id FROM ubicaciones WHERE referencia = 'Biblioteca barrial, sábados de 10 a 13');
SET @lugar_animales = (SELECT id FROM ubicaciones WHERE referencia = 'Refugio de animales, todos los días de 9 a 18');
SET @lugar_salud = (SELECT id FROM ubicaciones WHERE referencia = 'Salita barrial, lunes a viernes de 8 a 16');
SET @lugar_comunidad = (SELECT id FROM ubicaciones WHERE referencia = 'Club del barrio, sábados de 9 a 13');

INSERT INTO camp (owner_id, nombre, monetaria, categoria, logrado, descripcion, imagen, cbu, estado, id_ubicacion) VALUES
    (@ana, 'Alimentos para el barrio', 0, @cat_alimentos, 0, 'Recolectamos alimentos no perecederos para familias de La Plata.', 'imagenes-camp/alimentos/alimentos-01.webp', NULL, 'activa', @lugar_alimentos),
    (@ana, 'Abrigo para el invierno', 0, @cat_abrigo, 0, 'Necesitamos camperas y frazadas en buen estado.', 'imagenes-camp/abrigo/abrigo-01.webp', NULL, 'activa', @lugar_abrigo),
    (@ana, 'Mochilas y útiles para estudiar', 1, @cat_educacion, 25000, 'Juntamos fondos para comprar mochilas y útiles escolares.', 'imagenes-camp/educacion/educacion-01.webp', '0000000000000000000001', 'activa', NULL),
    (@pepe, 'Alimento para el refugio', 0, @cat_animales, 0, 'Buscamos alimento y mantas para los animales del refugio.', 'imagenes-camp/animales/animales-01.webp', NULL, 'activa', @lugar_animales),
    (@admin_celeste, 'Insumos para la salita', 0, @cat_salud, 0, 'Reunimos materiales de cuidado para la salita del barrio.', 'imagenes-camp/salud/salud-01.webp', NULL, 'activa', @lugar_salud),
    (@ana, 'Equipamiento para el club', 0, @cat_comunidad, 0, 'Necesitamos mesas y sillas para las actividades comunitarias.', 'imagenes-camp/comunidad/comunidad-01.webp', NULL, 'activa', @lugar_comunidad),
    (@lola, 'Fondo para emergencias', 1, @cat_emergencias, 0, 'Reunimos aportes para asistir necesidades urgentes de la comunidad.', 'imagenes-camp/emergencias/emergencias-01.webp', '0000000000000000000002', 'activa', NULL);
SET @camp_alimentos = (SELECT id FROM camp WHERE nombre = 'Alimentos para el barrio' AND owner_id = @ana);
SET @camp_abrigo = (SELECT id FROM camp WHERE nombre = 'Abrigo para el invierno' AND owner_id = @ana);
SET @camp_educacion = (SELECT id FROM camp WHERE nombre = 'Mochilas y útiles para estudiar' AND owner_id = @ana);
SET @camp_animales = (SELECT id FROM camp WHERE nombre = 'Alimento para el refugio' AND owner_id = @pepe);
SET @camp_salud = (SELECT id FROM camp WHERE nombre = 'Insumos para la salita' AND owner_id = @admin_celeste);
SET @camp_comunidad = (SELECT id FROM camp WHERE nombre = 'Equipamiento para el club' AND owner_id = @ana);
SET @camp_emergencias = (SELECT id FROM camp WHERE nombre = 'Fondo para emergencias' AND owner_id = @lola);

INSERT INTO campana_imagenes (id_camp, ruta, orden) VALUES
    (@camp_alimentos, 'assets/imagenes/imagenes-camp/alimentos/alimentos-01.webp', 0),
    (@camp_alimentos, 'assets/imagenes/imagenes-camp/alimentos/alimentos-02.webp', 1),
    (@camp_abrigo, 'assets/imagenes/imagenes-camp/abrigo/abrigo-01.webp', 0),
    (@camp_abrigo, 'assets/imagenes/imagenes-camp/abrigo/abrigo-02.webp', 1),
    (@camp_educacion, 'assets/imagenes/imagenes-camp/educacion/educacion-01.webp', 0),
    (@camp_animales, 'assets/imagenes/imagenes-camp/animales/animales-01.webp', 0),
    (@camp_animales, 'assets/imagenes/imagenes-camp/animales/animales-02.webp', 1),
    (@camp_salud, 'assets/imagenes/imagenes-camp/salud/salud-01.webp', 0),
    (@camp_salud, 'assets/imagenes/imagenes-camp/salud/salud-02.webp', 1),
    (@camp_comunidad, 'assets/imagenes/imagenes-camp/comunidad/comunidad-01.webp', 0),
    (@camp_emergencias, 'assets/imagenes/imagenes-camp/emergencias/emergencias-01.webp', 0);

SET @arroz = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @alimentos_insumos AND nombre = 'Arroz (kg)');
SET @leche = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @alimentos_insumos AND nombre = 'Leche larga vida (litros)');
SET @camperas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @abrigo_insumos AND nombre = 'Camperas (unidades)');
SET @frazadas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @abrigo_insumos AND nombre = 'Frazadas (unidades)');
SET @balanceado = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @animales_insumos AND nombre = 'Alimento balanceado (kg)');
SET @mantas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @animales_insumos AND nombre = 'Mantas para mascotas (unidades)');
SET @gasas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @salud_insumos AND nombre = 'Gasas (paquetes)');
SET @alcohol = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @salud_insumos AND nombre = 'Alcohol (litros)');
SET @mesas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @comunidad_insumos AND nombre = 'Mesas plegables (unidades)');
SET @sillas = (SELECT id FROM sub_categoria_insumos WHERE id_categoria = @comunidad_insumos AND nombre = 'Sillas (unidades)');
INSERT INTO objetivos (id_camp, id_categoria_insumos, id_sub_categoria_insumos, parcial, objetivo) VALUES
    (@camp_alimentos, @alimentos_insumos, @arroz, 0, 100),
    (@camp_alimentos, @alimentos_insumos, @leche, 0, 80),
    (@camp_abrigo, @abrigo_insumos, @camperas, 0, 40),
    (@camp_abrigo, @abrigo_insumos, @frazadas, 0, 30),
    (@camp_educacion, NULL, NULL, 0, 100000),
    (@camp_animales, @animales_insumos, @balanceado, 0, 120),
    (@camp_animales, @animales_insumos, @mantas, 0, 25),
    (@camp_salud, @salud_insumos, @gasas, 0, 60),
    (@camp_salud, @salud_insumos, @alcohol, 0, 30),
    (@camp_comunidad, @comunidad_insumos, @mesas, 0, 10),
    (@camp_comunidad, @comunidad_insumos, @sillas, 0, 40),
    (@camp_emergencias, NULL, NULL, 0, 150000);

INSERT INTO donacion (id_usuario, id_camp, cantidad) VALUES
    (@bruno, @camp_educacion, 5000),
    (@carla, @camp_educacion, 7000),
    (@carla, @camp_educacion, 4000),
    (@carla, @camp_educacion, 6000),
    (@lola, @camp_educacion, 1000),
    (@lola, @camp_educacion, 1000),
    (@lola, @camp_educacion, 1000);

COMMIT;
