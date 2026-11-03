-- Ejecutar una vez en instalaciones que ya tengan mercadopago_cuentas.
-- No elimina cuentas ni operaciones. Las autorizaciones anteriores se validan al renovar.
ALTER TABLE mercadopago_cuentas
    ADD COLUMN scope TEXT NULL,
    ADD COLUMN requiere_reautorizar BOOLEAN NOT NULL DEFAULT FALSE;
