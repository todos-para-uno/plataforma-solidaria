<?php
require_once __DIR__ . '/../repositorios/AdministracionMensajesRepository.php';
require_once __DIR__ . '/../repositorios/UsuariosRepository.php';

final class AdministracionMensajesController
{
    public function __construct(private AdministracionMensajesRepository $mensajes,
        private UsuariosRepository $usuarios) {}

    public function atender(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = $_SESSION['usuario_id'] ?? null;
        $usuario = is_int($id) ? $this->usuarios->findById($id) : null;
        if ($usuario === null || !$usuario->getAdmin()) {
            $this->responder($usuario === null ? 401 : 403, ['error'=>'Acceso reservado a administradores.']);
            return;
        }
        $mail = $_GET['mail'] ?? null;
        if ($mail !== null) {
            if (!is_string($mail) || strlen($mail) > 150 || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $this->responder(422, ['error'=>'Correo inválido.']); return;
            }
            $this->responder(200, ['mensajes'=>$this->mensajes->mensajes($mail)]); return;
        }
        $q = $_GET['q'] ?? '';
        $pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
        if (!is_string($q) || mb_strlen($q, 'UTF-8') > 150 ||
            $pagina === false || $pagina < 1 || $pagina > 100000) {
            $this->responder(422, ['error'=>'Búsqueda inválida.']); return;
        }
        $orden = $_GET['orden'] ?? 'recientes';
        $ordenesPermitidos = ['recientes', 'nombre_asc', 'nombre_desc', 'correo_asc', 'correo_desc'];
        if (!is_string($orden) || !in_array($orden, $ordenesPermitidos, true)) {
            $this->responder(422, ['error'=>'Ordenamiento inválido.']); return;
        }
        $limite = 15;
        $total = $this->mensajes->contarRemitentes($q);
        $this->responder(200, [
            'total'=>$total, 'pagina'=>$pagina,
            'remitentes'=>$this->mensajes->remitentes($q, $limite, ($pagina-1)*$limite, $orden)
        ]);
    }

    private function responder(int $codigo, array $datos): void
    {
        http_response_code($codigo);
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
