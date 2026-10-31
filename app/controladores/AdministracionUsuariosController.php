<?php
require_once __DIR__ . '/../repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../repositorios/EstadoAutenticacionRepository.php';
require_once __DIR__ . '/../seguridad/Csrf.php';
require_once __DIR__ . '/../repositorios/AdministracionActividadRepository.php';

/** Endpoints administrativos. Los permisos se verifican en cada petición. */
final class AdministracionUsuariosController
{
    public function __construct(private PDO $pdo, private UsuariosRepository $usuarios,
        private EstadoAutenticacionRepository $estados) {}

    private function autorizar(bool $escritura): bool
    {
        if (!isset($_SESSION['usuario_id']) || !is_int($_SESSION['usuario_id'])) {
            $this->respuesta(401, ['error' => 'Necesitás iniciar sesión.']);
            return false;
        }
        $actual = $this->usuarios->findById($_SESSION['usuario_id']);
        if (!$actual instanceof Usuario || !$actual->getAdmin()) {
            $this->respuesta(403, ['error' => 'Acceso reservado a administradores.']);
            return false;
        }
        if ($escritura && !Csrf::validar($_POST['csrf_token'] ?? null)) {
            $this->respuesta(403, ['error' => 'Token CSRF inválido.']);
            return false;
        }
        return true;
    }

    public function buscar(): void
    {
        if (!$this->autorizar(false)) return;
        $termino = $_GET['q'] ?? '';
        $limite = filter_var($_GET['limite'] ?? 15, FILTER_VALIDATE_INT);
        $pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
        if (!is_string($termino) || mb_strlen($termino, 'UTF-8') > 150 ||
            !in_array($limite, [10,15,25,50], true) || $pagina === false || $pagina < 1 || $pagina > 100000) {
            $this->respuesta(422, ['error' => 'Parámetros de búsqueda inválidos.']);
            return;
        }
        $total = $this->usuarios->contarAdministracion($termino);
        $this->respuesta(200, [
            'usuarios' => $this->usuarios->buscarAdministracion($termino, $limite, ($pagina - 1) * $limite),
            'total' => $total,
        ]);
    }

    /** Actividad de un usuario, consultada solo cuando el administrador la solicita. */
    public function actividad(): void
    {
        if (!$this->autorizar(false)) return;
        $id = filter_var($_GET['id_usuario'] ?? null, FILTER_VALIDATE_INT);
        $tipo = $_GET['tipo'] ?? null;
        if ($id === false || $id === null || $id < 1 || !is_string($tipo) ||
            !in_array($tipo, ['resumen', 'campanas', 'donaciones'], true)) {
            $this->respuesta(422, ['error' => 'Parámetros de actividad inválidos.']);
            return;
        }
        if ($this->usuarios->findById($id) === null) {
            $this->respuesta(404, ['error' => 'Usuario inexistente.']);
            return;
        }
        try {
            $repositorio = new AdministracionActividadRepository($this->pdo);
            $datos = match ($tipo) {
                'resumen' => $repositorio->resumen($id),
                'campanas' => $repositorio->campanas($id),
                'donaciones' => $repositorio->donaciones($id),
            };
            $this->respuesta(200, ['datos' => $datos]);
        } catch (Throwable $e) {
            error_log('Error al consultar actividad administrativa: ' . $e->getMessage());
            $this->respuesta(500, ['error' => 'No se pudo consultar la actividad.']);
        }
    }

    public function password(bool $desbloquear): void
    {
        if (!$this->autorizar(true)) return;
        $id = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);
        $password = $_POST['password'] ?? null;
        $confirmacion = $_POST['confirmacion'] ?? null;
        if ($id === false || $id === null || $id < 1 || !is_string($password) ||
            !is_string($confirmacion) || $password !== $confirmacion) {
            $this->respuesta(422, ['error' => 'Datos inválidos o contraseñas diferentes.']);
            return;
        }
        $transaccionExterna = $this->pdo->inTransaction();
        $puntoGuardado = 'admin_password_operacion';
        try {
            if ($transaccionExterna) {
                $this->pdo->exec('SAVEPOINT ' . $puntoGuardado);
            } else {
                $this->pdo->beginTransaction();
            }
            // El bloqueo de fila impide que cambie la condición admin mientras se opera.
            $stmt = $this->pdo->prepare('SELECT admin FROM usuarios WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$fila) {
                $this->revertirOperacion($transaccionExterna, $puntoGuardado);
                $this->respuesta(404, ['error' => 'Usuario inexistente.']);
                return;
            }
            if ((bool) $fila['admin']) {
                $this->revertirOperacion($transaccionExterna, $puntoGuardado);
                $this->respuesta(403, ['error' => 'No se permite cambiar contraseñas de administradores.']);
                return;
            }
            // Reutilizar la política de contraseñas de la entidad.
            $usuario = $this->usuarios->findById($id);
            $usuario->setPassword($password);
            $this->usuarios->actualizarHashPassword($id, $usuario->getPassword());
            if ($desbloquear) $this->estados->limpiar($id);
            if ($transaccionExterna) {
                $this->pdo->exec('RELEASE SAVEPOINT ' . $puntoGuardado);
            } else {
                $this->pdo->commit();
            }
            $this->respuesta(200, ['mensaje' => $desbloquear ? 'Contraseña cambiada y cuenta desbloqueada.' : 'Contraseña actualizada.']);
        } catch (InvalidArgumentException $e) {
            $this->revertirOperacion($transaccionExterna, $puntoGuardado);
            $this->respuesta(422, ['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->revertirOperacion($transaccionExterna, $puntoGuardado);
            error_log('Error en administración de contraseñas: ' . $e->getMessage());
            $this->respuesta(500, ['error' => 'No se pudo completar la operación.']);
        }
    }

    /** Respeta la transacción del llamador, sin confirmar ni revertir cambios ajenos. */
    private function revertirOperacion(bool $externa, string $puntoGuardado): void
    {
        if (!$this->pdo->inTransaction()) return;
        if ($externa) {
            $this->pdo->exec('ROLLBACK TO SAVEPOINT ' . $puntoGuardado);
            $this->pdo->exec('RELEASE SAVEPOINT ' . $puntoGuardado);
        } else {
            $this->pdo->rollBack();
        }
    }

    private function respuesta(int $codigo, array $datos): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
