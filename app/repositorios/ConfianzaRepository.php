<?php
require_once __DIR__ . '/../modelos/Usuarios.php';
final class ConfianzaRepository
{
    public function __construct(private PDO $pdo) {}

    /** Recalcula también para usuarios con donaciones anteriores a esta funcionalidad. */
    public function actualizar(int $usuario): array
    {
        $stmt = $this->pdo->prepare('SELECT u.tipo_documento, u.documento,
            (SELECT COUNT(*) FROM donacion d WHERE d.id_usuario = u.id) AS realizadas,
            (SELECT COUNT(*) FROM donacion d JOIN camp c ON c.id = d.id_camp WHERE c.owner_id = u.id AND d.id_usuario <> u.id) AS recibidas
            FROM usuarios u WHERE u.id = ?');
        $stmt->execute([$usuario]); $datos = $stmt->fetch();
        if (!$datos) throw new InvalidArgumentException('Usuario no disponible.');
        $tieneDocumento = in_array($datos['tipo_documento'], ['DNI', 'Pasaporte'], true) && trim($datos['documento'] ?? '') !== '';
        $total = (int) $datos['realizadas'] + (int) $datos['recibidas'];
        $nivel = !$tieneDocumento ? 1 : ($total >= 3 ? 4 : ($total >= 1 ? 3 : 2));
        $stmt = $this->pdo->prepare('SELECT id, nivel, nombre, descripcion FROM validaciones WHERE nivel = ?');
        $stmt->execute([$nivel]); $estado = $stmt->fetch();
        if (!$estado) throw new RuntimeException('No está configurado el nivel de confianza.');
        $stmt = $this->pdo->prepare('UPDATE usuarios SET id_validacion = ? WHERE id = ? AND id_validacion <> ?');
        $stmt->execute([$estado['id'], $usuario, $estado['id']]);
        return $estado + ['tiene_documento' => $tieneDocumento, 'realizadas' => (int) $datos['realizadas'], 'recibidas' => (int) $datos['recibidas'], 'total' => $total];
    }

    public function guardarDocumento(int $usuario, mixed $tipo, mixed $numero): void
    {
        if (!is_string($tipo) || !in_array($tipo, ['DNI', 'Pasaporte'], true) || !is_string($numero)) throw new InvalidArgumentException('Seleccioná DNI o Pasaporte e ingresá su número.');
        $numero = mb_strtoupper(trim($numero), 'UTF-8');
        if ($tipo === 'DNI' && !preg_match('/^[0-9]{7,8}$/D', $numero)) throw new InvalidArgumentException('El DNI debe contener 7 u 8 números, sin puntos ni espacios.');
        if ($tipo === 'Pasaporte' && !preg_match('/^[A-Z0-9]{5,30}$/D', $numero)) throw new InvalidArgumentException('El pasaporte debe tener entre 5 y 30 letras o números, sin espacios.');
        $externa = $this->pdo->inTransaction();
        if ($externa) $this->pdo->exec('SAVEPOINT documento_confianza'); else $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT documento FROM usuarios WHERE id = ? FOR UPDATE'); $stmt->execute([$usuario]);
            $fila = $stmt->fetch();
            if (!$fila) throw new InvalidArgumentException('Usuario no disponible.');
            if (trim($fila['documento'] ?? '') !== '') throw new InvalidArgumentException('Ya tenés un documento cargado.');
            $stmt = $this->pdo->prepare('UPDATE usuarios SET tipo_documento = ?, documento = ? WHERE id = ?'); $stmt->execute([$tipo, $numero, $usuario]);
            $this->actualizar($usuario);
            if ($externa) $this->pdo->exec('RELEASE SAVEPOINT documento_confianza'); else $this->pdo->commit();
        } catch (Throwable $e) {
            if ($externa) $this->pdo->exec('ROLLBACK TO SAVEPOINT documento_confianza'); else $this->pdo->rollBack();
            throw $e;
        }
    }
}
