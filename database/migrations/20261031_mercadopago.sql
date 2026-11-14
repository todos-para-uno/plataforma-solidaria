-- Seleccionar la base existente antes de importar. No modifica ni borra datos anteriores.
CREATE TABLE IF NOT EXISTS mercadopago_cuentas (
    id_usuario INT PRIMARY KEY,
    mp_usuario VARCHAR(32) NOT NULL UNIQUE,
    access_cifrado TEXT NOT NULL,
    refresh_cifrado TEXT NOT NULL,
    vence_en BIGINT NOT NULL,
    produccion BOOLEAN NOT NULL DEFAULT FALSE,
    actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS mercadopago_pagos (
    referencia CHAR(36) PRIMARY KEY,
    id_usuario INT NULL,
    correo_donante VARCHAR(254) NULL,
    invitado_hash CHAR(64) NULL,
    id_camp INT NOT NULL,
    id_creador INT NOT NULL,
    mp_receptor VARCHAR(32) NOT NULL,
    produccion BOOLEAN NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    acreditado DECIMAL(12,2) NOT NULL DEFAULT 0,
    order_id VARCHAR(64) NULL UNIQUE,
    checkout_url TEXT NULL,
    estado VARCHAR(40) NOT NULL DEFAULT 'created',
    detalle VARCHAR(80) NOT NULL DEFAULT 'created',
    version_mp BIGINT NOT NULL DEFAULT 0,
    id_donacion INT NULL UNIQUE,
    creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_camp) REFERENCES camp(id),
    FOREIGN KEY (id_creador) REFERENCES usuarios(id),
    FOREIGN KEY (id_donacion) REFERENCES donacion(id)
);
