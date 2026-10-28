<?php
require_once __DIR__ . '/../repositorios/BaneoRepository.php';
require_once __DIR__ . '/../repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../seguridad/Csrf.php';

class BaneoController
{
    public function __construct(
        private UsuariosRepository $usuarios,
        private BaneoRepository $baneos
    ) {}

    public function procesarBaneo(): void
    {
        $admin = $this->autorizarAdministrador();
        if ($admin === null) {
            return;
        }

        $idUsuario = $this->idPositivo($_POST['id_usuario'] ?? null);
        $motivo = $_POST['motivo'] ?? '';
        $fechaFin = $_POST['fecha_fin'] ?? '';
        if ($idUsuario === null || !is_string($motivo) || !is_string($fechaFin)) {
            $this->responder(400, 'Los datos enviados no son válidos.');
            return;
        }

        if ($idUsuario === $admin->getId()) {
            $this->responder(403, 'No podés banearte a vos mismo.');
            return;
        }

        $usuario = $this->usuarios->findById($idUsuario);
        if (!$usuario instanceof Usuario) {
            $this->responder(404, 'No existe el usuario indicado.');
            return;
        }
        if ($usuario->getAdmin()) {
            $this->responder(403, 'No se puede banear a otros administradores.');
            return;
        }
        if ($this->baneos->estaBaneado($idUsuario)) {
            $this->responder(409, 'El usuario ya tiene un baneo vigente.');
            return;
        }

        // Usar el reloj de la base, igual que estaBaneado(), para comparar las fechas.
        $ahora = $this->baneos->fechaActual();
        $fechaFin = trim($fechaFin);
        if ($fechaFin !== '') {
            $fecha = $this->normalizarFecha($fechaFin);
            if ($fecha === null || $fecha <= $ahora) {
                $this->responder(422, 'La fecha de fin debe ser válida y posterior al inicio del baneo.');
                return;
            }
            $fechaFin = $fecha;
        }

        try {
            // El administrador sale de la sesión, nunca de un ID enviado en el formulario.
            $baneo = new Baneo($idUsuario, $admin->getId(), $motivo, $ahora, $fechaFin === '' ? null : $fechaFin);
        } catch (InvalidArgumentException $excepcion) {
            $this->responder(422, $excepcion->getMessage());
            return;
        }

        $id = $this->baneos->insertarBaneo($baneo);
        $this->responder(201, 'Baneo registrado correctamente.', (int) $id);
    }

    public function finalizarBaneo(): void
    {
        if ($this->autorizarAdministrador() === null) {
            return;
        }

        $idBaneo = $this->idPositivo($_POST['id_baneo'] ?? null);
        if ($idBaneo === null) {
            $this->responder(400, 'El ID del baneo debe ser un entero positivo.');
            return;
        }
        if ($this->baneos->findById($idBaneo) === null) {
            $this->responder(404, 'No existe el baneo indicado.');
            return;
        }
        if (!$this->baneos->finalizarBaneo($idBaneo)) {
            $this->responder(409, 'El baneo no está vigente.');
            return;
        }

        $this->responder(200, 'Baneo finalizado correctamente.', $idBaneo);
    }

    private function autorizarAdministrador(): ?Usuario
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->responder(405, 'Método no permitido.');
            return null;
        }

        $id = $_SESSION['usuario_id'] ?? null;
        $usuario = is_int($id) ? $this->usuarios->findById($id) : null;
        if (!$usuario instanceof Usuario) {
            $this->responder(401, 'Debés iniciar sesión.');
            return null;
        }
        if (!$usuario->getAdmin() || $this->baneos->estaBaneado($id)) {
            $this->responder(403, 'Solo un administrador puede gestionar baneos.');
            return null;
        }
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            $this->responder(403, 'La solicitud no es válida. Recargá el formulario e intentá nuevamente.');
            return null;
        }

        return $usuario;
    }

    private function idPositivo(mixed $valor): ?int
    {
        if (!is_string($valor) && !is_int($valor)) {
            return null;
        }
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function normalizarFecha(string $valor): ?string
    {
        if (str_contains($valor, "\0")) {
            return null;
        }
        // Aceptar el formato de DATETIME y el de un campo HTML datetime-local.
        foreach (['Y-m-d H:i:s', 'Y-m-d\TH:i'] as $formato) {
            $fecha = DateTimeImmutable::createFromFormat('!' . $formato, $valor);
            if ($fecha !== false && $fecha->format($formato) === $valor) {
                return $fecha->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function responder(int $codigo, string $mensaje, ?int $idBaneo = null): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        $respuesta = ['mensaje' => $mensaje];
        if ($idBaneo !== null) {
            $respuesta['id_baneo'] = $idBaneo;
        }
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }
}
