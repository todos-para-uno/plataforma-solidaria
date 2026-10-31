<?php
require_once __DIR__ . '/../servicios/MercadoPagoSeguridad.php';
final class MercadoPagoRepository
{
    public function __construct(private PDO $pdo) {}

    public function transaccion(Closure $accion): mixed
    {
        $anidada = $this->pdo->inTransaction();
        $marca = 'mp_' . bin2hex(random_bytes(6));
        if ($anidada) $this->pdo->exec('SAVEPOINT ' . $marca);
        else $this->pdo->beginTransaction();
        try {
            $resultado = $accion();
            if ($anidada) $this->pdo->exec('RELEASE SAVEPOINT ' . $marca);
            else $this->pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($anidada) $this->pdo->exec('ROLLBACK TO SAVEPOINT ' . $marca);
            elseif ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function cuenta(int $usuarioId, bool $bloquear = false): ?array
    {
        $q = $this->pdo->prepare('SELECT * FROM mercadopago_cuentas WHERE id_usuario = ?' . ($bloquear ? ' FOR UPDATE' : ''));
        $q->execute([$usuarioId]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function guardarCuenta(int $id, array $datos): void
    {
        // No usar ON DUPLICATE KEY: una colisión en mp_usuario nunca debe actualizar otra cuenta.
        $actual = $this->cuenta($id, true);
        $valores = [$datos['access_cifrado'], $datos['refresh_cifrado'], $datos['vence_en']];
        if ($actual !== null) {
            if ($actual['mp_usuario'] !== $datos['mp_usuario'] || (bool) $actual['produccion'] !== $datos['produccion']) {
                throw new InvalidArgumentException('La autorización pertenece a otra cuenta.');
            }
            $q = $this->pdo->prepare('UPDATE mercadopago_cuentas SET access_cifrado=?,refresh_cifrado=?,vence_en=?,actualizado=CURRENT_TIMESTAMP WHERE id_usuario=?');
            $q->execute([...$valores, $id]);
        } else {
            $q = $this->pdo->prepare('INSERT INTO mercadopago_cuentas (access_cifrado,refresh_cifrado,vence_en,id_usuario,mp_usuario,produccion) VALUES (?,?,?,?,?,?)');
            $q->execute([...$valores, $id, $datos['mp_usuario'], (int) $datos['produccion']]);
        }
    }

    public function campana(int $id): ?array
    {
        $q = $this->pdo->prepare('SELECT c.*, u.mail AS creador_mail FROM camp c JOIN usuarios u ON u.id=c.owner_id WHERE c.id=?');
        $q->execute([$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function pago(string $referencia, bool $bloquear = false): ?array
    {
        $q = $this->pdo->prepare('SELECT * FROM mercadopago_pagos WHERE referencia=?' . ($bloquear ? ' FOR UPDATE' : ''));
        $q->execute([$referencia]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function pagoPorOrder(string $order): ?array
    {
        $q = $this->pdo->prepare('SELECT * FROM mercadopago_pagos WHERE order_id=?');
        $q->execute([$order]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function crearIntento(string $ref, int $usuario, array $campana, array $cuenta, string $monto): array
    {
        // INSERT IGNORE resuelve dos POST simultáneos del mismo formulario. Se compara el contenido después.
        $q = $this->pdo->prepare('INSERT IGNORE INTO mercadopago_pagos
            (referencia,id_usuario,id_camp,id_creador,mp_receptor,produccion,monto)
            VALUES (?,?,?,?,?,?,?)');
        $q->execute([$ref, $usuario, $campana['id'], $campana['owner_id'], $cuenta['mp_usuario'], (int) $cuenta['produccion'], $monto]);
        $pago = $this->pago($ref);
        if ($pago === null || (int) $pago['id_usuario'] !== $usuario || (int) $pago['id_camp'] !== (int) $campana['id']
            || $pago['monto'] !== $monto || $pago['mp_receptor'] !== $cuenta['mp_usuario']) {
            throw new InvalidArgumentException('Ese intento ya corresponde a otro aporte. Abrí nuevamente el formulario.');
        }
        return $pago;
    }

    public function guardarOrder(string $ref, string $id, string $url): void
    {
        $q = $this->pdo->prepare('UPDATE mercadopago_pagos SET order_id=?,checkout_url=?,actualizado=CURRENT_TIMESTAMP WHERE referencia=?');
        $q->execute([$id, $url, $ref]);
    }

    /** Llamar con el intento bloqueado dentro de una transacción. */
    public function acreditar(array $pago, int $centavos, string $estado, string $detalle, int $version): void
    {
        $idDonacion = $pago['id_donacion'];
        if ($centavos > 0 && $idDonacion === null) {
            $q = $this->pdo->prepare('INSERT INTO donacion (id_usuario,id_camp,cantidad) VALUES (?,?,?)');
            $q->execute([$pago['id_usuario'], $pago['id_camp'], $pago['monto']]);
            $idDonacion = (int) $this->pdo->lastInsertId();
        }
        $delta = $centavos - MercadoPagoSeguridad::centavos($pago['acreditado']);
        if ($delta !== 0) {
            $q = $this->pdo->prepare('UPDATE camp SET logrado=logrado + ? WHERE id=?');
            $q->execute([($delta < 0 ? '-' : '') . MercadoPagoSeguridad::pesos(abs($delta)), $pago['id_camp']]);
        }
        $q = $this->pdo->prepare('UPDATE mercadopago_pagos SET acreditado=?,estado=?,detalle=?,version_mp=?,id_donacion=?,actualizado=CURRENT_TIMESTAMP WHERE referencia=?');
        $q->execute([MercadoPagoSeguridad::pesos($centavos), $estado, $detalle, $version, $idDonacion, $pago['referencia']]);
    }

    public function historial(int $usuario): array
    {
        $q = $this->pdo->prepare('SELECT p.referencia,p.monto AS cantidad,p.acreditado,p.estado,p.detalle,p.creado AS fecha_hora,
            c.id AS id_camp,c.nombre AS campaña_nombre,c.estado AS campana_estado,1 AS monetaria
            FROM mercadopago_pagos p JOIN camp c ON c.id=p.id_camp WHERE p.id_usuario=?
            UNION ALL SELECT NULL,d.cantidad,d.cantidad,\'processed\',\'accredited\',d.fecha_hora,c.id,c.nombre,c.estado,c.monetaria
            FROM donacion d JOIN camp c ON c.id=d.id_camp LEFT JOIN mercadopago_pagos p ON p.id_donacion=d.id
            WHERE d.id_usuario=? AND p.referencia IS NULL ORDER BY fecha_hora DESC');
        $q->execute([$usuario, $usuario]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
