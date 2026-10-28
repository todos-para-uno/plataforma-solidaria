<?php
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function buscadorRepositoryTest(PDO $pdo): void
{
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $repo = new CampRepository($pdo);
        $ids = static fn (array $filas): array => array_map('intval', array_column($filas, 'id'));
        TestUtils::comprobar($ids($repo->buscarActivas($f['prefijo'])) === [$f['literal'], $f['ropa'], $f['mesa']], 'Busca por nombre y descripción, ordena recientes y excluye campañas suspendidas y finalizadas');
        TestUtils::comprobar($ids($repo->buscarActivas('', $f['alimentos'])) === [$f['literal'], $f['mesa']], 'La categoría funciona sin palabra clave');
        TestUtils::comprobar($ids($repo->buscarActivas('Comedor', $f['abrigo'])) === [$f['ropa']], 'Combina categoría y coincidencia en descripción');
        TestUtils::comprobar($repo->buscarActivas('invierno', $f['alimentos']) === [], 'Ambos filtros deben cumplirse');
        TestUtils::comprobar($ids($repo->buscarActivas('%_!', $f['alimentos'])) === [$f['literal']], 'Los caracteres de LIKE se buscan como texto literal');
        TestUtils::comprobar($repo->buscarActivas("' OR 1=1 --", $f['alimentos']) === [], 'La entrada no modifica la consulta SQL');
        $todas = $ids($repo->buscarActivas());
        TestUtils::comprobar(count(array_intersect($todas, [$f['mesa'], $f['ropa'], $f['literal']])) === 3, 'Sin filtros se pueden explorar todas las campañas activas');
        $fila = $repo->buscarActivas('', $f['abrigo'])[0];
        TestUtils::comprobar((int) $fila['monetaria'] === 0 && str_ends_with($fila['nombre_categoria'], 'Abrigo') && !isset($fila['cbu']), 'El resultado identifica el tipo y la categoría sin exponer datos bancarios');
        $categorias = new CatCampRepository($pdo);
        $categoria = $categorias->findById($f['alimentos']);
        TestUtils::comprobar($categoria instanceof CatCamp && $categoria->getId() === $f['alimentos'], 'El repositorio de categorías hidrata sus entidades');
    } finally {
        $pdo->rollBack();
    }
}
