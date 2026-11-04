<?php
require_once __DIR__ . '/ImagenPerfil.php';
require_once __DIR__ . '/../repositorios/UsuariosRepository.php';

final class PerfilUsuario
{
    public function __construct(private PDO $pdo, private ImagenPerfil $imagenes) {}

    /** Toda la identidad proviene de la sesión. Una subida fallida no modifica el perfil. */
    public function guardar(Usuario $cuenta, array $datos, mixed $archivo): void
    {
        if (!is_string($datos['nombre'] ?? null) || !is_string($datos['apellido'] ?? null)
            || !in_array($datos['eliminar_avatar'] ?? null, [null, '1'], true)) {
            throw new InvalidArgumentException('Los datos del perfil no son válidos.');
        }
        $editado = clone $cuenta;
        $editado->setNombre($datos['nombre']);
        $editado->setApellido($datos['apellido']);
        $resultado = $this->imagenes->validar($archivo);
        if ($resultado['errores']) throw new InvalidArgumentException(implode(' ', $resultado['errores']));
        $eliminar = ($datos['eliminar_avatar'] ?? null) === '1';
        if ($eliminar && $resultado['archivos']) {
            throw new InvalidArgumentException('Elegí reemplazar o quitar la foto, no ambas acciones a la vez.');
        }
        $nuevas = $this->imagenes->guardar($resultado['archivos']);
        $externa = $this->pdo->inTransaction();
        $iniciada = false;
        try {
            if ($externa) $this->pdo->exec('SAVEPOINT perfil_usuario');
            else $this->pdo->beginTransaction();
            $iniciada = true;
            $stmt = $this->pdo->prepare('SELECT avatar FROM usuarios WHERE id = ? FOR UPDATE');
            $stmt->execute([$cuenta->getId()]);
            $anterior = $stmt->fetch();
            if (!$anterior) throw new InvalidArgumentException('La cuenta ya no está disponible.');
            $avatar = $nuevas[0] ?? ($eliminar ? null : $anterior['avatar']);
            (new UsuariosRepository($this->pdo))->save([
                'id' => $cuenta->getId(), 'nombre' => $editado->getNombre(),
                'apellido' => $editado->getApellido(), 'avatar' => $avatar,
            ]);
            if ($externa) $this->pdo->exec('RELEASE SAVEPOINT perfil_usuario');
            else $this->pdo->commit();
        } catch (Throwable $e) {
            if ($iniciada) {
                if ($externa) $this->pdo->exec('ROLLBACK TO SAVEPOINT perfil_usuario');
                else $this->pdo->rollBack();
            }
            $this->imagenes->eliminar($nuevas);
            throw $e;
        }
        // La foto anterior se borra solo después de confirmar el cambio en la base.
        if ($avatar !== $anterior['avatar']) $this->imagenes->eliminar([$anterior['avatar']]);
    }
}
