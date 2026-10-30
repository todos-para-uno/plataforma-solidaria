<?php
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function buscadorRepositoryTest(PDO $pdo): void
{
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $repo = new CampRepository($pdo);
        $ids = static fn (array $filas): array => array_map(static fn (Camp $campana): int => $campana->getId(), $filas);
        TestUtils::comprobar($ids($repo->findByEstado(EstadoCamp::Activa, $f['prefijo'])) === [$f['literal'], $f['ropa'], $f['mesa']], 'Busca por nombre y descripción, ordena recientes y excluye campañas suspendidas y finalizadas');
        TestUtils::comprobar($ids($repo->findByEstado(EstadoCamp::Activa, '', $f['alimentos'])) === [$f['literal'], $f['mesa']], 'La categoría funciona sin palabra clave');
        TestUtils::comprobar($ids($repo->findByEstado(EstadoCamp::Activa, 'Comedor', $f['abrigo'])) === [$f['ropa']], 'Combina categoría y coincidencia en descripción');
        TestUtils::comprobar($repo->findByEstado(EstadoCamp::Activa, 'invierno', $f['alimentos']) === [], 'Ambos filtros deben cumplirse');
        TestUtils::comprobar($ids($repo->findByEstado(EstadoCamp::Activa, '%_!', $f['alimentos'])) === [$f['literal']], 'Los caracteres de LIKE se buscan como texto literal');
        TestUtils::comprobar($repo->findByEstado(EstadoCamp::Activa, "' OR 1=1 --", $f['alimentos']) === [], 'La entrada no modifica la consulta SQL');
        $todas = $ids($repo->findByEstado(EstadoCamp::Activa));
        TestUtils::comprobar(count(array_intersect($todas, [$f['mesa'], $f['ropa'], $f['literal']])) === 3, 'Sin filtros se pueden explorar todas las campañas activas');
        $fila = $repo->findByEstado(EstadoCamp::Activa, '', $f['abrigo'])[0];
        TestUtils::comprobar($fila instanceof Camp && !$fila->getMonetaria() && $fila->getCategoria() === $f['abrigo'], 'El resultado es una entidad Camp con su tipo y categoría');
        TestUtils::comprobar($ids($repo->buscarActivas($f['prefijo'])) === [$f['literal'], $f['ropa'], $f['mesa']], 'El método de compatibilidad también devuelve entidades');
        $suspendidas = $repo->findByEstado(EstadoCamp::Suspendida, $f['prefijo']);
        TestUtils::comprobar(count($suspendidas) === 1 && $suspendidas[0]->getEstado() === EstadoCamp::Suspendida, 'El filtro por estado conserva el enum de la entidad');
        $categorias = new CatCampRepository($pdo);
        $categoria = $categorias->findById($f['alimentos']);
        TestUtils::comprobar($categoria instanceof CatCamp && $categoria->getId() === $f['alimentos'], 'El repositorio de categorías hidrata sus entidades');
    } finally {
        $pdo->rollBack();
    }
}
