-- Conserva las orders anteriores. Los nuevos aportes usan Checkout Pro Preferences.
ALTER TABLE mercadopago_pagos
    ADD COLUMN IF NOT EXISTS preference_id VARCHAR(100) NULL UNIQUE,
    ADD COLUMN IF NOT EXISTS payment_id VARCHAR(32) NULL UNIQUE;

CREATE TABLE IF NOT EXISTS mercadopago_oauth_estados (
    state_hash CHAR(64) PRIMARY KEY,
    id_usuario INT NOT NULL,
    verifier_cifrado TEXT NOT NULL,
    volver VARCHAR(30) NOT NULL,
    vence_en BIGINT NOT NULL,
    consumido BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS mercadopago_transacciones (
    payment_id VARCHAR(32) PRIMARY KEY,
    referencia CHAR(36) NOT NULL,
    acreditado DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado VARCHAR(40) NOT NULL,
    detalle VARCHAR(80) NOT NULL,
    version_mp BIGINT NOT NULL,
    id_donacion INT NULL UNIQUE,
    FOREIGN KEY (referencia) REFERENCES mercadopago_pagos(referencia),
    FOREIGN KEY (id_donacion) REFERENCES donacion(id)
);

CREATE TABLE IF NOT EXISTS mercadopago_notificaciones (
    payment_id VARCHAR(32) PRIMARY KEY,
    mp_usuario VARCHAR(32) NOT NULL,
    pendiente BOOLEAN NOT NULL DEFAULT TRUE,
    intentos INT NOT NULL DEFAULT 0,
    proximo_intento BIGINT NOT NULL DEFAULT 0,
    actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
