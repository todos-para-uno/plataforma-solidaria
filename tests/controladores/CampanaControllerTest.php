<?php
require_once __DIR__ . '/../../app/controladores/CampanaController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function campanaControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $codigoOriginal = http_response_code();
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $repo = new CampRepository($pdo);
        $controller = new CampanaController($repo, new CatCampRepository($pdo),
            new UsuariosRepository($pdo), new ValidacionesRepository($pdo), new ObjetivosRepository($pdo));
        $render = static function (mixed $id) use ($controller): array {
            http_response_code(200);
            ob_start();
            try {
                $controller->mostrar('.', './assets', $id, ['nombre' => 'Visitante autenticado', 'admin' => false]);
                return [ob_get_contents(), http_response_code()];
            } finally {
                ob_end_clean();
            }
        };

        $objetivos = new ObjetivosRepository($pdo);
        $idObjetivo = $objetivos->save(['id_camp' => $f['mesa'], 'objetivo' => 250000]);
        $objetivo = $objetivos->findById($idObjetivo);
        TestUtils::comprobar($objetivo instanceof Objetivos && $objetivo->getObjetivo() === 250000.0
            && $objetivo->getId_categoria_insumos() === null && $objetivo->getId_sub_categoria_insumos() === null,
            'Hidrata objetivos monetarios con categorías nulas como permite el esquema');
        [$html, $codigo] = $render((string) $f['mesa']);
        TestUtils::comprobar($codigo === 200 && str_contains($html, $f['prefijo'] . ' Comedor')
            && str_contains($html, $f['prefijo'] . ' Alimentos') && str_contains($html, 'Usuario buscador'),
            'El detalle muestra la campaña solicitada, su categoría y su creador reales');
        TestUtils::comprobar(str_contains($html, '250.000') && !str_contains($html, 'Una mesa para compartir'),
            'El objetivo se obtiene de la base sin reemplazarlo por datos de ejemplo');
        TestUtils::comprobar(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;')
            && !str_contains($html, '<script>alert(1)</script>') && !str_contains($html, '0000000000000000000001'),
            'Escapa la descripción y no publica el CBU de la campaña');
        TestUtils::comprobar(substr_count($html, '<main') === 1
            && str_contains($html, 'href="./index.php?ruta=resultados_buscar"'),
            'El detalle conserva una sola región principal y permite volver a resultados');

        TestUtils::comprobar(str_contains($html, 'Visitante autenticado')
            && str_contains($html, 'name="csrf_token" value="' . Csrf::generar() . '"'),
            'El menú conserva la sesión y el token para cerrar sesión desde el detalle');

        $consulta = $pdo->prepare('INSERT INTO categoria_insumos (nombre) VALUES (:nombre)');
        $consulta->execute(['nombre' => $f['prefijo'] . ' Ropa']);
        $cat = (int) $pdo->lastInsertId();
        $objetivos->save(['id_camp' => $f['ropa'], 'id_categoria_insumos' => $cat, 'objetivo' => 20]);
        [$html, $codigo] = $render((string) $f['ropa']);
        TestUtils::comprobar($codigo === 200 && str_contains($html, $f['prefijo'] . ' Ropa')
            && str_contains($html, 'de 20'), 'El detalle de insumos muestra sus objetivos y nombres reales');

        foreach ([null, '', '0', '-1', 'abc', '1 OR 1=1', ['1'], str_repeat('9', 40)] as $idInvalido) {
            [$html, $codigo] = $render($idInvalido);
            TestUtils::comprobar($codigo === 400 && !str_contains($html, 'tarjeta-detalle'),
                'Rechaza un identificador inválido sin mostrar una campaña');
        }
        [$html, $codigo] = $render('2147483647');
        TestUtils::comprobar($codigo === 404, 'Una campaña inexistente devuelve 404');
        foreach ([EstadoCamp::Suspendida, EstadoCamp::Finalizada] as $estado) {
            $campana = $repo->findByEstado($estado, $f['prefijo'])[0];
            [$html, $codigo] = $render((string) $campana->getId());
            TestUtils::comprobar($codigo === 404 && !str_contains($html, 'tarjeta-detalle'),
                'No permite acceder a una campaña no activa modificando la URL');
        }
    } finally {
        $pdo->rollBack();
        http_response_code($codigoOriginal ?: 200);
    }
}
