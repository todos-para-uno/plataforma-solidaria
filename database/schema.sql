CREATE DATABASE IF NOT EXISTS todosparauno;
USE todosparauno;

-- validaciones de qué tan confiable es el usuario, si tiene DNI publicado, si donó, si recibió con éxito una donación.

CREATE TABLE validaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nivel INT NOT NULL UNIQUE,
    nombre VARCHAR(30) NOT NULL,
    descripcion VARCHAR(255));

INSERT INTO validaciones (nivel, nombre, descripcion)
    VALUES (1, 'Blanco', 'Usuario sin DNI ingresado'),
    (2, 'Celeste', 'Usuario con DNI ingresado'),
    (3, 'Azul', 'Usuario con una donacion o campaña exitosa'),
    (4, 'Violeta', 'Usuario con varias donaciones');

-- Reglas de negocio de programación:
    -- De blanco a celeste pasa con el ingreso del DNI/Pasaporte
    -- De celeste a azul pasa si el usuario donó una vez, o una campaña suya realizada fue exitosa (llegó al 100% de lo recaudado)
    -- De azul a violeta pasa si el usuario donó x cantidad de veces (3? 5?)

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
