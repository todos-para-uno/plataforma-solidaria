<?php
require_once __DIR__ . '/../../app/controladores/BuscadorController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function buscadorControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $codigoOriginal = http_response_code();
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $controller = new BuscadorController(new CampRepository($pdo), new CatCampRepository($pdo));
        $render = static function (array $entrada, bool $resultados = true) use ($controller): array {
            http_response_code(200);
            ob_start();
            try {
                $controller->mostrar('.', './assets', $entrada, $resultados, ['nombre' => 'Visitante autenticado', 'admin' => false]);
                $html = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            return [$html, http_response_code()];
        };
        [$html, $codigo] = $render([], false);
        TestUtils::comprobar($codigo === 200 && str_contains($html, 'method="get"') && str_contains($html, 'name="ruta" value="resultados_buscar"'), 'El formulario permite búsquedas compartibles por GET');
        [$html, $codigo] = $render(['buscar' => '  Comedor  ', 'id_categoria_campana' => (string) $f['alimentos']]);
        TestUtils::comprobar($codigo === 200 && str_contains($html, '1</strong> campaña encontrada'), 'Muestra la cantidad de coincidencias combinadas');
        TestUtils::comprobar(str_contains($html, 'value="Comedor"') && str_contains($html, 'value="' . $f['alimentos'] . '" selected'), 'Conserva los filtros normalizados');
        TestUtils::comprobar(!str_contains($html, '<script>alert(1)</script>') && str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Escapa el contenido de las campañas');
        TestUtils::comprobar(substr_count($html, '<main') === 1 && str_contains($html, 'Visitante autenticado'), 'Conserva una sola región principal y el menú de sesión');
        TestUtils::comprobar(str_contains($html, 'href="./index.php?ruta=campana&amp;id=' . $f['mesa'] . '"'), 'Cada tarjeta enlaza al detalle mediante el punto de entrada público y su ID');
        TestUtils::comprobar(!str_contains($html, '0000000000000000000001'), 'El resultado no expone el CBU almacenado en la entidad');
        [$html] = $render(['buscar' => $f['prefijo'] . ' inexistente']);
        TestUtils::comprobar(str_contains($html, 'aria-label="Sin resultados"') && str_contains($html, 'Ver todas las campañas activas'), 'Ofrece un camino para recuperar una búsqueda sin resultados');
        foreach ([['buscar' => ['texto']], ['buscar' => str_repeat('á', 151)], ['id_categoria_campana' => ['1']], ['id_categoria_campana' => '-1'], ['id_categoria_campana' => '2147483647']] as $invalida) {
            [$html, $codigo] = $render($invalida);
            TestUtils::comprobar($codigo === 422 && str_contains($html, 'role="alert"') && !str_contains($html, 'resultado-campana'), 'Rechaza filtros inválidos sin mostrar resultados engañosos');
        }
        $inicio = static function (): string {
            $rutaBase = '.';
            $rutaAssets = './assets';
            ob_start();
            try {
                require __DIR__ . '/../../app/vistas/index.php';
                return ob_get_contents();
            } finally {
                ob_end_clean();
            }
        };
        $htmlInicio = $inicio();
        TestUtils::comprobar(str_contains($htmlInicio, $f['prefijo'] . ' Comedor')
            && str_contains($htmlInicio, 'href="./index.php?ruta=campana&amp;id=' . $f['mesa'] . '"'),
            'El inicio también consume entidades y enlaza al detalle sin romper las tarjetas');
        [$html] = $render(['buscar' => '"><script>alert(2)</script>']);
        TestUtils::comprobar(!str_contains($html, '<script>alert(2)</script>'), 'Escapa la palabra clave al volver a mostrarla');
    } finally {
        $pdo->rollBack();
        http_response_code($codigoOriginal ?: 200);
    }
}
