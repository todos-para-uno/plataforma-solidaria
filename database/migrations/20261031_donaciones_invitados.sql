-- MariaDB: aplicar después de 20261031_mercadopago.sql sobre una base existente.
-- Conserva usuarios y donaciones anteriores. No crea cuentas ficticias para visitantes.
ALTER TABLE donacion MODIFY id_usuario INT NULL;
ALTER TABLE mercadopago_pagos MODIFY id_usuario INT NULL;
ALTER TABLE mercadopago_pagos ADD COLUMN IF NOT EXISTS correo_donante VARCHAR(254) NULL AFTER id_usuario;
ALTER TABLE mercadopago_pagos ADD COLUMN IF NOT EXISTS invitado_hash CHAR(64) NULL AFTER correo_donante;
-- Los intentos registrados anteriores se conservan con el correo conocido del usuario.
UPDATE mercadopago_pagos p JOIN usuarios u ON u.id=p.id_usuario
SET p.correo_donante=u.mail WHERE p.correo_donante IS NULL;
