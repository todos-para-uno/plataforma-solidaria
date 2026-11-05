<?php
require_once __DIR__ . '/ImagenPerfil.php';
require_once __DIR__ . '/ImagenesCampana.php';

/** Operaciones de cuenta. Nunca borra claves foráneas ni historial de donaciones. */
final class BajaCuenta
{
    public function __construct(
        private PDO $pdo,
        private ?ImagenPerfil $imagenes = null,
        private ?ImagenesCampana $imagenesCampana = null
    ) {
        $this->imagenes ??= new ImagenPerfil();
        $this->imagenesCampana ??= new ImagenesCampana(
            __DIR__ . '/../../public_php/subidas/campanas'
        );
    }
    public function desactivar(int $id, bool $eliminarDatos): void
    {
        $avatar = null;
        $archivosCampana = [];
        $this->pdo->beginTransaction();
        try {
            $q = $this->pdo->prepare('SELECT id, estado_cuenta, avatar FROM usuarios WHERE id = ? FOR UPDATE');
            $q->execute([$id]);
            $cuenta = $q->fetch(PDO::FETCH_ASSOC);
            if (!$cuenta || $cuenta['estado_cuenta'] !== 'activa') {
                throw new DomainException('La cuenta no está activa.');
            }
            if (!$eliminarDatos) {
                $q = $this->pdo->prepare("UPDATE usuarios SET estado_cuenta = 'desactivada' WHERE id = ?");
                $q->execute([$id]);
            } else {
                $avatar = $cuenta['avatar'];
                // Las campañas con historial se conservan, pero dejan de recibir donaciones.
                $q = $this->pdo->prepare("UPDATE camp SET estado = 'cerrada', motivo_cierre = 'usuario_eliminado' WHERE owner_id = ? AND estado = 'activa'");
                $q->execute([$id]);
                // Desvincular datos de contacto y pago del creador. Mantener objetivos y progreso.
                $q = $this->pdo->prepare("UPDATE camp SET cbu = NULL, descripcion = 'Campaña de usuario eliminado', imagen = NULL, id_ubicacion = NULL WHERE owner_id = ?");
                $q->execute([$id]);
                $q = $this->pdo->prepare('SELECT ci.ruta FROM campana_imagenes ci INNER JOIN camp c ON c.id = ci.id_camp WHERE c.owner_id = ?');
                $q->execute([$id]);
                $archivosCampana = $q->fetchAll(PDO::FETCH_COLUMN);
                $q = $this->pdo->prepare('DELETE ci FROM campana_imagenes ci INNER JOIN camp c ON c.id = ci.id_camp WHERE c.owner_id = ?');
                $q->execute([$id]);
                // Los mensajes y contactos pueden contener información personal.
                $q = $this->pdo->prepare("UPDATE mensajes SET contenido = '[Contenido retirado por eliminación de cuenta]' WHERE id_emisor = ? OR id_receptor = ?");
                $q->execute([$id, $id]);
                $q = $this->pdo->prepare("UPDATE contactos SET nombre = 'Usuario eliminado', mail = 'eliminado@example.invalid', contenido = '[Contenido retirado por eliminación de cuenta]' WHERE id_usuario = ?");
                $q->execute([$id]);
                // No derivar el identificador anónimo del mail original.
                for ($intento = 0; $intento < 8; $intento++) {
                    $correo = 'eliminado-' . bin2hex(random_bytes(16)) . '@example.invalid';
                    $q = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE mail = ? LIMIT 1');
                    $q->execute([$correo]);
                    if (!$q->fetchColumn()) break;
                }
                if ($intento === 8) throw new RuntimeException('No se pudo generar un identificador único.');
                $q = $this->pdo->prepare("UPDATE usuarios SET nombre = 'Usuario eliminado', apellido = NULL, avatar = NULL, mail = ?, telefono = NULL, tipo_documento = NULL, documento = NULL, pass = ?, verificado = 0, admin = 0, id_validacion = (SELECT id FROM validaciones WHERE nivel = 1 LIMIT 1), estado_cuenta = 'eliminada' WHERE id = ?");
                $q->execute([$correo, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $id]);
                $q = $this->pdo->prepare('DELETE FROM estado_autenticacion WHERE id_usuario = ?');
                $q->execute([$id]);
            }
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
        // El sistema de archivos no participa de la transacción SQL.
        if ($eliminarDatos) {
            if ($avatar !== null) $this->imagenes->eliminar([$avatar]);
            $this->imagenesCampana->eliminar($archivosCampana);
        }
    }

    public function reactivar(int $id): bool
    {
        $q = $this->pdo->prepare("UPDATE usuarios SET estado_cuenta = 'activa' WHERE id = ? AND estado_cuenta = 'desactivada'");
        $q->execute([$id]);
        return $q->rowCount() === 1;
    }
}
