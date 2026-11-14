<?php
require_once __DIR__ . '/../../app/controladores/PuntoEntregaController.php';
require_once __DIR__ . '/../../app/controladores/CampanaController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
function puntoEntregaControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $original = [$_POST, $_GET, $_SERVER, $_SESSION, http_response_code()];
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $usuarios = new UsuariosRepository($pdo);
        $owner = (int) $pdo->query('SELECT owner_id FROM camp WHERE id = ' . $f['ropa'])->fetchColumn();
        $usuario = $usuarios->findById($owner);
        $controller = new PuntoEntregaController($pdo);
        $repo = new PuntoEntregaRepository($pdo);
        $render = static function (?Usuario $actor) use ($controller): array {
            http_response_code(200); ob_start();
            try { $controller->atender($actor); return [http_response_code(), ob_get_contents()]; }
            finally { ob_end_clean(); }
        };
        $_GET = ['id' => (string) $f['ropa']]; $_SERVER['REQUEST_METHOD'] = 'GET'; $_POST = [];
        [$codigo, $html] = $render($usuario);
        TestUtils::comprobar($codigo === 200 && str_contains($html, 'id="mapa-entrega"') && str_contains($html, 'name="csrf_token"'), 'El creador puede elegir un único punto en el mapa');
        [$codigo] = $render(null);
        TestUtils::comprobar($codigo === 303, 'Exige iniciar sesión para modificar la ubicación');
        $_GET = ['id' => (string) $f['mesa']];
        [$codigo] = $render($usuario);
        TestUtils::comprobar($codigo === 403, 'El editor solo admite campañas de insumos');
        $_GET = ['id' => (string) $f['ropa']]; $_SERVER['REQUEST_METHOD'] = 'POST';
        $antes = $repo->buscar($f['ropa']);
        $_POST = ['csrf_token' => 'incorrecto'];
        [$codigo] = $render($usuario);
        TestUtils::comprobar($codigo === 403 && $repo->buscar($f['ropa']) === $antes, 'Sin CSRF no modifica la ubicación');
        $_POST = ['csrf_token' => Csrf::generar(), 'provincia' => 'Buenos Aires', 'localidad' => 'La Plata', 'latitud' => '-34.920500', 'longitud' => '-57.950100', 'referencia' => 'Escuela <script>alert(1)</script> · de 10 a 12'];
        [$codigo] = $render($usuario);
        $guardada = $repo->buscar($f['ropa']);
        TestUtils::comprobar($codigo === 303 && $guardada['id'] === $antes['id'] && (float) $guardada['latitud'] === -34.9205, 'Guarda el punto actualizando la ubicación existente, sin agregar otra');
        $_POST['latitud'] = '91';
        [$codigo] = $render($usuario);
        TestUtils::comprobar($codigo === 422 && $repo->buscar($f['ropa']) === $guardada, 'Coordenadas inválidas no cambian el punto de entrega');
        $_POST['latitud'] = '-34.9'; $_POST['localidad'] = 'Localidad inexistente';
        [$codigo] = $render($usuario);
        TestUtils::comprobar($codigo === 422, 'Valida que la localidad pertenezca a la provincia');
        $rechazado = false;
        try { $repo->guardar($owner + 100000, $f['ropa'], new Ubicaciones('Buenos Aires', 'La Plata', -34.9, -57.9)); }
        catch (InvalidArgumentException $e) { $rechazado = true; }
        TestUtils::comprobar($rechazado && $repo->buscar($f['ropa']) === $guardada, 'Otro usuario no puede cambiar la ubicación, incluso llamando al repositorio');
        $detalle = new CampanaController(new CampRepository($pdo), new CatCampRepository($pdo), $usuarios, new ValidacionesRepository($pdo), new ObjetivosRepository($pdo), $repo);
        $_SESSION['usuario_id'] = $owner;
        ob_start();
        try { $detalle->mostrar('.', './assets', (string) $f['ropa']); $html = ob_get_contents(); }
        finally { ob_end_clean(); }
        TestUtils::comprobar(str_contains($html, 'Todos los insumos pedidos se reciben en este lugar') && str_contains($html, 'openstreetmap.org/export/embed.html') && str_contains($html, '&lt;script&gt;') && !str_contains($html, '<script>alert(1)</script>'), 'El detalle publica el mismo destino para todos los insumos y escapa la referencia');
        $pdo->exec('UPDATE camp SET id_ubicacion = NULL WHERE id = ' . $f['ropa']);
        $repo->guardar($owner, $f['ropa'], new Ubicaciones('Buenos Aires', 'La Plata', -34.9, -57.9));
        $nueva = $repo->buscar($f['ropa']);
        $repo->guardar($owner, $f['ropa'], new Ubicaciones('Buenos Aires', 'La Plata', -34.8, -57.8));
        TestUtils::comprobar($nueva['id'] === $repo->buscar($f['ropa'])['id'], 'Si no había ubicación, crea una sola y la reutiliza en las siguientes ediciones');
        $pdo->exec("UPDATE camp SET estado = 'suspendida' WHERE id = " . $f['ropa']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        [$codigo] = $render($usuario);
        TestUtils::comprobar($codigo === 403, 'No modifica el destino de campañas suspendidas');
    } finally {
        $pdo->rollBack();
        [$_POST, $_GET, $_SERVER, $_SESSION, $codigo] = $original;
        http_response_code($codigo ?: 200);
    }
}
