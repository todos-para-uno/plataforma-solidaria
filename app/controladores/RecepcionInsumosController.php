<?php
require_once __DIR__ . '/../repositorios/DonacionRepository.php';
require_once __DIR__ . '/../repositorios/CampRepository.php';
require_once __DIR__ . '/../repositorios/ObjetivosRepository.php';
require_once __DIR__ . '/../seguridad/Csrf.php';
final class RecepcionInsumosController
{
    public function __construct(private PDO $pdo) {}
    public function atender(?Usuario $usuario, string $rutaBase = '.', string $rutaAssets = './assets'): void
    {
        if (!$usuario) { header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303); return; }
        header('Cache-Control: no-store');
        $id = is_string($_GET['id'] ?? null) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if ($id === false) { http_response_code(400); echo 'Campaña no válida.'; return; }
        $campana = (new CampRepository($this->pdo))->findById($id);
        if (!$campana || $campana->getOwnerId() !== $usuario->getId() || $campana->getMonetaria()) {
            http_response_code(403); echo 'No podés registrar recepciones para esta campaña.'; return;
        }
        $repo = new DonacionRepository($this->pdo);
        $error = null;
        $aviso = $_SESSION['recepcion_aviso'] ?? null; unset($_SESSION['recepcion_aviso']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validar($_POST['csrf_token'] ?? null)) { http_response_code(403); echo 'El formulario venció. Recargá la página.'; return; }
            try {
                $claveEnviada = $_POST['clave'] ?? null;
                if (!is_string($claveEnviada) || !isset($_SESSION['recepcion_claves'][$claveEnviada]) || $_SESSION['recepcion_claves'][$claveEnviada] !== $id) {
                    throw new InvalidArgumentException('Este formulario ya se procesó o venció. Revisá las recepciones antes de registrar otra.');
                }
                if (($_POST['confirmado'] ?? null) !== '1') throw new InvalidArgumentException('Confirmá que los insumos ya fueron recibidos.');
                $objetivo = is_string($_POST['id_objetivo'] ?? null) ? filter_var($_POST['id_objetivo'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
                if (!$objetivo || !is_string($_POST['correo'] ?? null)) throw new InvalidArgumentException('Completá el donante y el insumo recibido.');
                $repo->registrarRecepcion($usuario->getId(), $id, $objetivo, trim($_POST['correo']), $_POST['cantidad'] ?? null);
                unset($_SESSION['recepcion_claves'][$claveEnviada]);
                $_SESSION['recepcion_aviso'] = 'Recepción registrada. El progreso de la campaña ya está actualizado.';
                header('Location: ' . $rutaBase . '/index.php?ruta=recepcion_insumos&id=' . $id, true, 303); return;
            } catch (InvalidArgumentException $e) { http_response_code(422); $error = $e->getMessage(); }
            catch (PDOException $e) { http_response_code(503); $error = 'No pudimos registrar la recepción. Intentá nuevamente.'; }
        }
        $objetivos = (new ObjetivosRepository($this->pdo))->buscarDetallePorCampana($id);
        $recepciones = $repo->recepcionesPorCampana($id, $usuario->getId());
        $clave = bin2hex(random_bytes(32));
        $_SESSION['recepcion_claves'] = array_slice($_SESSION['recepcion_claves'] ?? [], -19, null, true);
        $_SESSION['recepcion_claves'][$clave] = $id;
        $csrfToken = Csrf::generar();
        $usuarioActual = ['nombre' => $usuario->getNombre(), 'admin' => $usuario->getAdmin()];
        require __DIR__ . '/../vistas/recepcion_insumos.php';
    }
}
