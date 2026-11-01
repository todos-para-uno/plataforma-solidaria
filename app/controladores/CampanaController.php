<?php
require_once __DIR__ . '/../repositorios/CampRepository.php';
require_once __DIR__ . '/../repositorios/CatCampRepository.php';
require_once __DIR__ . '/../repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../repositorios/ObjetivosRepository.php';
require_once __DIR__ . '/../seguridad/Csrf.php';
require_once __DIR__ . '/../repositorios/PuntoEntregaRepository.php';

final class CampanaController
{
    public function __construct(
        private CampRepository $campanas,
        private CatCampRepository $categorias,
        private UsuariosRepository $usuarios,
        private ValidacionesRepository $validaciones,
        private ObjetivosRepository $objetivos,
        private ?PuntoEntregaRepository $puntos = null
    ) {}

    public function mostrar(string $rutaBase, string $rutaAssets, mixed $id, ?array $usuarioActual = null): void
    {
        $idCampana = is_string($id)
            ? filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        if ($idCampana === false) {
            http_response_code(400);
            echo 'El identificador de campaña no es válido.';
            return;
        }

        $campana = $this->campanas->findById($idCampana);
        // El detalle público solo muestra campañas activas, igual que el buscador.
        if ($campana === null || $campana->getEstado() !== EstadoCamp::Activa) {
            http_response_code(404);
            echo 'Campaña no encontrada.';
            return;
        }

        $categoria = $this->categorias->findById($campana->getCategoria());
        $creador = $this->usuarios->findById($campana->getOwnerId());
        $validacion = $creador === null ? null : $this->validaciones->findById($creador->getIdValidacion());
        $objetivos = $this->objetivos->buscarDetallePorCampana($idCampana);
        $objetivoMonetario = array_sum(array_column($objetivos, 'objetivo'));
        $puntoEntrega = $this->puntos?->buscar($idCampana);
        $csrfToken = Csrf::generar();
        require __DIR__ . '/../vistas/campana.php';
    }
}
