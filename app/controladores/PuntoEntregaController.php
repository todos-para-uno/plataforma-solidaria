<?php
require_once __DIR__ . '/../repositorios/PuntoEntregaRepository.php';
require_once __DIR__ . '/../repositorios/CampRepository.php';
require_once __DIR__ . '/../seguridad/Csrf.php';
final class PuntoEntregaController
{
    public function __construct(private PDO $pdo) {}

    public function atender(?Usuario $usuario, string $rutaBase = '.', string $rutaAssets = './assets'): void
    {
        if (!$usuario) {
            header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303);
            return;
        }
        $id = is_string($_GET['id'] ?? null) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if ($id === false) { http_response_code(400); echo 'Campaña no válida.'; return; }
        $campana = (new CampRepository($this->pdo))->findById($id);
        if (!$campana || $campana->getOwnerId() !== $usuario->getId() || $campana->getMonetaria() || $campana->getEstado() !== EstadoCamp::Activa) {
            http_response_code(403); echo 'No podés modificar el punto de entrega de esta campaña.'; return;
        }
        $repo = new PuntoEntregaRepository($this->pdo);
        $punto = $repo->buscar($id) ?? [];
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validar($_POST['csrf_token'] ?? null)) { http_response_code(403); echo 'El formulario venció. Recargá la página.'; return; }
            $punto = [];
            foreach (['provincia', 'localidad', 'latitud', 'longitud', 'referencia'] as $campo) {
                $punto[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
            }
            try {
                if (!is_numeric($punto['latitud']) || !is_numeric($punto['longitud'])) throw new InvalidArgumentException('Seleccioná un punto de entrega en el mapa.');
                $ubicacion = new Ubicaciones($punto['provincia'], $punto['localidad'], (float) $punto['latitud'], (float) $punto['longitud'], $punto['referencia']);
                $repo->guardar($usuario->getId(), $id, $ubicacion);
                header('Location: ' . $rutaBase . '/index.php?ruta=campana&id=' . $id, true, 303);
                return;
            } catch (InvalidArgumentException $e) {
                http_response_code(422); $error = $e->getMessage();
            } catch (PDOException $e) {
                http_response_code(503); $error = 'No pudimos guardar el punto de entrega. Intentá nuevamente.';
            }
        }
        $usuarioActual = ['nombre' => $usuario->getNombre(), 'admin' => $usuario->getAdmin()];
        $csrfToken = Csrf::generar();
        $catalogo = CatalogoGeografico::getLocalidadesPorProvincia();
        header('Cache-Control: no-store');
        require __DIR__ . '/../vistas/punto_entrega.php';
    }
}
