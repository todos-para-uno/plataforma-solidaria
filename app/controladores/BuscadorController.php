<?php
require_once __DIR__ . '/../repositorios/CampRepository.php';
require_once __DIR__ . '/../repositorios/CatCampRepository.php';
require_once __DIR__ . '/../seguridad/Csrf.php';

class BuscadorController
{
    public function __construct(
        private CampRepository $campanas,
        private CatCampRepository $categorias
    ) {}

    public function mostrar(
        string $rutaBase,
        string $rutaAssets,
        array $parametros,
        bool $mostrarResultados = false,
        ?array $usuarioActual = null
    ): void {
        $categoriasCampanas = $this->categorias->listarParaBuscador();
        $nombresCategorias = array_column($categoriasCampanas, 'nombre', 'id');
        $errores = [];
        $buscar = '';
        $idCategoria = null;

        if (!is_string($parametros['buscar'] ?? '')) {
            $errores[] = 'Ingresá una palabra o frase para buscar.';
        } else {
            $buscar = trim($parametros['buscar'] ?? '');
            if (!mb_check_encoding($buscar, 'UTF-8') || mb_strlen($buscar, 'UTF-8') > 150) {
                $errores[] = 'La búsqueda debe tener hasta 150 caracteres válidos.';
                $buscar = '';
            }
        }

        $categoria = $parametros['id_categoria_campana'] ?? '';
        if ($categoria !== '') {
            $id = is_string($categoria)
                ? filter_var($categoria, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                : false;
            if ($id === false || !in_array($id, array_map('intval', array_column($categoriasCampanas, 'id')), true)) {
                $errores[] = 'Elegí una categoría disponible en la lista.';
            } else {
                $idCategoria = $id;
            }
        }

        $resultados = [];
        if ($errores !== []) {
            http_response_code(422);
        } elseif ($mostrarResultados) {
            $resultados = $this->campanas->findByEstado(EstadoCamp::Activa, $buscar, $idCategoria);
        }

        $csrfToken = Csrf::generar();
        require __DIR__ . '/../vistas/' . ($mostrarResultados ? 'resultados_buscar.php' : 'buscador.php');
    }
}
