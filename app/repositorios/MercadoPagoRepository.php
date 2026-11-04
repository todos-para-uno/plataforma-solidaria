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

    public function guardarEstado(string $state, int $usuario, string $verifier, string $volver): void
    {
        $q = $this->pdo->prepare('INSERT INTO mercadopago_oauth_estados (state_hash,id_usuario,verifier_cifrado,volver,vence_en) VALUES (?,?,?,?,?)');
        $q->execute([hash('sha256', $state), $usuario, $verifier, $volver, time() + 600]);
    }

    public function consumirEstado(string $state, int $usuario): array
    {
        return $this->transaccion(function () use ($state, $usuario): array {
            $hash = hash('sha256', $state);
            $q = $this->pdo->prepare('SELECT * FROM mercadopago_oauth_estados WHERE state_hash=? AND id_usuario=? FOR UPDATE');
            $q->execute([$hash, $usuario]);
            $fila = $q->fetch(PDO::FETCH_ASSOC);
            if (!$fila || $fila['consumido'] || (int) $fila['vence_en'] < time()) {
                throw new InvalidArgumentException('La vinculación venció o ya fue utilizada. Iniciala nuevamente.');
            }
            $this->pdo->prepare('UPDATE mercadopago_oauth_estados SET consumido=TRUE WHERE state_hash=?')->execute([$hash]);
            return $fila;
        });
    }

    public function cuentaPorMp(string $mpUsuario): ?array
    {
        $q = $this->pdo->prepare('SELECT * FROM mercadopago_cuentas WHERE mp_usuario=?');
        $q->execute([$mpUsuario]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function encolarNotificacion(string $paymentId, string $mpUsuario): void
    {
        $q = $this->pdo->prepare('INSERT INTO mercadopago_notificaciones (payment_id,mp_usuario) VALUES (?,?)
            ON DUPLICATE KEY UPDATE mp_usuario=VALUES(mp_usuario),pendiente=TRUE,proximo_intento=0,actualizado=CURRENT_TIMESTAMP');
        $q->execute([$paymentId, $mpUsuario]);
    }

    /** El bloqueo vive hasta terminar el procesamiento, incluso con varios workers. */
    public function procesarSiguienteNotificacion(Closure $procesar): ?bool
    {
        return $this->transaccion(function () use ($procesar): ?bool {
            $q = $this->pdo->prepare('SELECT * FROM mercadopago_notificaciones WHERE pendiente=TRUE AND proximo_intento<=? ORDER BY proximo_intento,payment_id LIMIT 1 FOR UPDATE');
            $q->execute([time()]);
            $evento = $q->fetch(PDO::FETCH_ASSOC);
            if (!$evento) return null;
            try {
                $procesar($evento);
                $this->pdo->prepare('UPDATE mercadopago_notificaciones SET pendiente=FALSE,intentos=0 WHERE payment_id=?')->execute([$evento['payment_id']]);
                return true;
            } catch (Throwable $e) {
                // No almacenar respuestas ni secretos; conservar el evento para reintentar.
                $this->pdo->prepare('UPDATE mercadopago_notificaciones SET intentos=intentos+1,proximo_intento=? WHERE payment_id=?')
                    ->execute([time() + min(3600, 30 * (2 ** min(7, (int) $evento['intentos']))), $evento['payment_id']]);
                return false;
            }
        });
    }

    public function guardarCuenta(int $id, array $datos): void
    {
        // No usar ON DUPLICATE KEY: una colisión en mp_usuario nunca debe actualizar otra cuenta.
        $actual = $this->cuenta($id, true);
        $valores = [$datos['access_cifrado'], $datos['refresh_cifrado'], $datos['vence_en'], $datos['scope']];
        if ($actual !== null) {
            if ($actual['mp_usuario'] !== $datos['mp_usuario'] || (bool) $actual['produccion'] !== $datos['produccion']) {
                throw new InvalidArgumentException('La autorización pertenece a otra cuenta.');
            }
            $q = $this->pdo->prepare('UPDATE mercadopago_cuentas SET access_cifrado=?,refresh_cifrado=?,vence_en=?,scope=?,requiere_reautorizar=FALSE,actualizado=CURRENT_TIMESTAMP WHERE id_usuario=?');
            $q->execute([...$valores, $id]);
        } else {
            $q = $this->pdo->prepare('INSERT INTO mercadopago_cuentas (access_cifrado,refresh_cifrado,vence_en,scope,id_usuario,mp_usuario,produccion) VALUES (?,?,?,?,?,?,?)');
            $q->execute([...$valores, $id, $datos['mp_usuario'], (int) $datos['produccion']]);
        }
    }

    public function marcarReautorizacion(int $usuario): void
    {
        $q = $this->pdo->prepare('UPDATE mercadopago_cuentas SET requiere_reautorizar=TRUE,actualizado=CURRENT_TIMESTAMP WHERE id_usuario=?');
        $q->execute([$usuario]);
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

    public function crearIntento(string $ref, ?int $usuario, array $campana, array $cuenta, string $monto, string $mail, ?string $invitadoHash = null): array
    {
        // INSERT IGNORE resuelve dos POST simultáneos del mismo formulario. Se compara el contenido después.
        $q = $this->pdo->prepare('INSERT IGNORE INTO mercadopago_pagos
            (referencia,id_usuario,id_camp,id_creador,mp_receptor,produccion,monto,correo_donante,invitado_hash)
            VALUES (?,?,?,?,?,?,?,?,?)');
        $q->execute([$ref, $usuario, $campana['id'], $campana['owner_id'], $cuenta['mp_usuario'], (int) $cuenta['produccion'], $monto, $mail, $invitadoHash]);
        $pago = $this->pago($ref);
        if ($pago === null || ($pago['id_usuario'] === null ? null : (int) $pago['id_usuario']) !== $usuario || (int) $pago['id_camp'] !== (int) $campana['id']
            || $pago['invitado_hash'] !== $invitadoHash || $pago['correo_donante'] !== $mail || $pago['monto'] !== $monto || $pago['mp_receptor'] !== $cuenta['mp_usuario']) {
            throw new InvalidArgumentException('Ese intento ya corresponde a otro aporte. Abrí nuevamente el formulario.');
        }
        return $pago;
    }

    public function guardarPreferencia(string $ref, string $id, string $url): void
    {
        $q = $this->pdo->prepare('UPDATE mercadopago_pagos SET preference_id=?,checkout_url=?,actualizado=CURRENT_TIMESTAMP WHERE referencia=?');
        $q->execute([$id, $url, $ref]);
    }

    /** El intento ya está bloqueado. Cada pago tiene su propio estado y donación. */
    public function acreditarTransaccion(array $pago, string $id, int $centavos, string $estado, string $detalle, int $version): void
    {
        $q = $this->pdo->prepare('SELECT * FROM mercadopago_transacciones WHERE payment_id=? FOR UPDATE');
        $q->execute([$id]);
        $anterior = $q->fetch(PDO::FETCH_ASSOC);
        if ($anterior && $anterior['referencia'] !== $pago['referencia']) throw new RuntimeException('El pago pertenece a otro aporte.');
        if ($anterior && $version < (int) $anterior['version_mp']) return;
        $previo = $anterior ? MercadoPagoSeguridad::centavos($anterior['acreditado']) : 0;
        if ($anterior && (in_array($anterior['estado'], ['refunded', 'charged_back'], true)
            || ($anterior['detalle'] === 'partially_refunded' && $centavos > $previo))) return;
        if ($previo > 0 && !in_array($estado, ['approved', 'in_mediation', 'refunded', 'charged_back'], true)) return;
        $idDonacion = $anterior['id_donacion'] ?? null;
        if ($centavos > 0 && $idDonacion === null) {
            $q = $this->pdo->prepare('INSERT INTO donacion (id_usuario,id_camp,cantidad) VALUES (?,?,?)');
            $q->execute([$pago['id_usuario'], $pago['id_camp'], $pago['monto']]);
            $idDonacion = (int) $this->pdo->lastInsertId();
        }
        $q = $this->pdo->prepare('INSERT INTO mercadopago_transacciones (payment_id,referencia,acreditado,estado,detalle,version_mp,id_donacion) VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE acreditado=VALUES(acreditado),estado=VALUES(estado),detalle=VALUES(detalle),version_mp=VALUES(version_mp),id_donacion=VALUES(id_donacion)');
        $q->execute([$id, $pago['referencia'], MercadoPagoSeguridad::pesos($centavos), $estado, $detalle, $version, $idDonacion]);
        $q = $this->pdo->prepare('SELECT SUM(acreditado) FROM mercadopago_transacciones WHERE referencia=?');
        $q->execute([$pago['referencia']]);
        $total = MercadoPagoSeguridad::centavos($q->fetchColumn());
        $delta = $total - MercadoPagoSeguridad::centavos($pago['acreditado']);
        if ($delta !== 0) {
            $q = $this->pdo->prepare('UPDATE camp SET logrado=logrado + ? WHERE id=?');
            $q->execute([($delta < 0 ? '-' : '') . MercadoPagoSeguridad::pesos(abs($delta)), $pago['id_camp']]);
        }
        // Un intento rechazado no oculta otro pago aprobado de la misma preferencia.
        if ($total > 0) {
            $estado = 'approved';
            $q = $this->pdo->prepare("SELECT COUNT(*) FROM mercadopago_transacciones WHERE referencia=? AND detalle='partially_refunded'");
            $q->execute([$pago['referencia']]);
            $detalle = $q->fetchColumn() > 0 ? 'partially_refunded' : 'accredited';
        }
        $q = $this->pdo->prepare('UPDATE mercadopago_pagos SET acreditado=?,estado=?,detalle=?,version_mp=GREATEST(version_mp,?),payment_id=?,id_donacion=COALESCE(id_donacion,?),actualizado=CURRENT_TIMESTAMP WHERE referencia=?');
        $q->execute([MercadoPagoSeguridad::pesos($total), $estado, $detalle, $version, $id, $idDonacion, $pago['referencia']]);
    }

    public function historial(int $usuario): array
    {
        $q = $this->pdo->prepare('SELECT p.referencia,p.monto AS cantidad,p.acreditado,p.estado,p.detalle,p.creado AS fecha_hora,
            c.id AS id_camp,c.nombre AS campaña_nombre,c.estado AS campana_estado,1 AS monetaria
            FROM mercadopago_pagos p JOIN camp c ON c.id=p.id_camp WHERE p.id_usuario=?
            UNION ALL SELECT NULL,d.cantidad,d.cantidad,\'processed\',\'accredited\',d.fecha_hora,c.id,c.nombre,c.estado,c.monetaria
            FROM donacion d JOIN camp c ON c.id=d.id_camp LEFT JOIN mercadopago_pagos p ON p.id_donacion=d.id
            LEFT JOIN mercadopago_transacciones t ON t.id_donacion=d.id
            WHERE d.id_usuario=? AND p.referencia IS NULL AND t.payment_id IS NULL ORDER BY fecha_hora DESC');
        $q->execute([$usuario, $usuario]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
