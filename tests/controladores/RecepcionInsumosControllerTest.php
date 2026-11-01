<?php
require_once __DIR__ . '/../../app/controladores/RecepcionInsumosController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
function recepcionInsumosControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $original = [$_POST, $_GET, $_SERVER, $_SESSION, http_response_code()];
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $usuarios = new UsuariosRepository($pdo);
        $owner = (int) $pdo->query('SELECT owner_id FROM camp WHERE id = ' . $f['ropa'])->fetchColumn();
        $usuario = $usuarios->findById($owner);
        $nivel = (new ValidacionesRepository($pdo))->findByNivel(1)->getId();
        $correo = $f['prefijo'] . '-donante@example.com';
        $donante = (int) $usuarios->insertarUsuario(Usuario::crear('Donante de insumos', $correo, 'Password1234', $nivel));
        $stmt = $pdo->prepare('INSERT INTO categoria_insumos (nombre) VALUES (?)'); $stmt->execute([$f['prefijo']]);
        $cat = (int) $pdo->lastInsertId();
        $repoObjetivos = new ObjetivosRepository($pdo);
        $objetivo = (int) $repoObjetivos->save(['id_camp' => $f['ropa'], 'id_categoria_insumos' => $cat, 'objetivo' => 20]);
        $otro = (int) $repoObjetivos->save(['id_camp' => $f['mesa'], 'objetivo' => 200]);
        $repo = new DonacionRepository($pdo);
        $rechaza = static function (Closure $accion, string $mensaje): void {
            try { $accion(); } catch (InvalidArgumentException $e) { TestUtils::comprobar(true, $mensaje); return; }
            throw new RuntimeException('No rechazó: ' . $mensaje);
        };
        foreach (['0','-1','1e2','NaN','1.001','10000000000', ['2']] as $cantidad) {
            $rechaza(fn() => $repo->registrarRecepcion($owner, $f['ropa'], $objetivo, $correo, $cantidad), 'Rechaza cantidades inválidas sin registrar la donación');
        }
        $rechaza(fn() => $repo->registrarRecepcion($donante, $f['ropa'], $objetivo, $correo, '2'), 'El donante no puede confirmar su propia entrega');
        $rechaza(fn() => $repo->registrarRecepcion($owner, $f['ropa'], $otro, $correo, '2'), 'Rechaza un objetivo de otra campaña');
        $rechaza(fn() => $repo->registrarRecepcion($owner, $f['ropa'], $objetivo, $usuario->getEmail(), '2'), 'No permite atribuirse una donación propia');
        $rechaza(fn() => $repo->registrarRecepcion($owner, $f['ropa'], $objetivo, 'inexistente@example.com', '2'), 'Exige una cuenta de donante existente');
        $controller = new RecepcionInsumosController($pdo);
        $render = static function (?Usuario $actor) use ($controller): array {
            http_response_code(200); ob_start();
            try { $controller->atender($actor); return [http_response_code(), ob_get_contents()]; }
            finally { ob_end_clean(); }
        };
        $_GET = ['id' => (string) $f['ropa']]; $_SERVER['REQUEST_METHOD'] = 'GET'; $_POST = [];
        [$codigo, $html] = $render($usuario);
        preg_match('/name="clave" value="([a-f0-9]{64})"/', $html, $coincidencias);
        TestUtils::comprobar($codigo === 200 && isset($coincidencias[1]), 'El creador tiene un formulario de recepción con clave de un solo uso');
        [$codigo] = $render(null); TestUtils::comprobar($codigo === 303, 'Exige sesión');
        [$codigo] = $render($usuarios->findById($donante)); TestUtils::comprobar($codigo === 403, 'Otro usuario no puede ver ni registrar las recepciones privadas');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf_token' => 'incorrecto', 'clave' => $coincidencias[1], 'id_objetivo' => (string) $objetivo, 'correo' => $correo, 'cantidad' => '2,50', 'confirmado' => '1'];
        [$codigo] = $render($usuario); TestUtils::comprobar($codigo === 403 && count($repo->findByIdCamp($f['ropa'])) === 0, 'Sin CSRF no crea donaciones');
        $_POST['csrf_token'] = Csrf::generar(); unset($_POST['confirmado']);
        [$codigo] = $render($usuario); TestUtils::comprobar($codigo === 422, 'Requiere declarar que los insumos ya se recibieron');
        $_POST['confirmado'] = '1';
        [$codigo] = $render($usuario);
        $progreso = fn() => (float) $pdo->query("SELECT parcial FROM objetivos WHERE id = $objetivo")->fetchColumn();
        $donaciones = $repo->findByIdCamp($f['ropa']);
        TestUtils::comprobar($codigo === 303 && $progreso() === 2.5 && count($donaciones) === 1 && (int) $donaciones[0]['id_usuario'] === $donante, 'La recepción guarda el donante y actualiza exactamente el objetivo seleccionado');
        [$codigo] = $render($usuario);
        TestUtils::comprobar($codigo === 422 && $progreso() === 2.5 && count($repo->findByIdCamp($f['ropa'])) === 1, 'Reenviar el mismo formulario no duplica la donación');
        TestUtils::comprobar(count($repo->recepcionesPorCampana($f['ropa'], $donante)) === 0, 'El historial de recepción del creador no se expone a terceros');
        $pdo->exec("UPDATE objetivos SET parcial = 9999999999.99 WHERE id = $objetivo");
        $rechaza(fn() => $repo->registrarRecepcion($owner, $f['ropa'], $objetivo, $correo, '1'), 'Evita exceder la capacidad numérica sin crear una donación parcial');
        TestUtils::comprobar(count($repo->findByIdCamp($f['ropa'])) === 1, 'Un rechazo no deja una donación sin su progreso correspondiente');
        $pdo->exec("UPDATE camp SET estado = 'suspendida' WHERE id = " . $f['ropa']);
        $rechaza(fn() => $repo->registrarRecepcion($owner, $f['ropa'], $objetivo, $correo, '1'), 'No acredita recepciones en campañas suspendidas');
    } finally {
        $pdo->rollBack(); [$_POST, $_GET, $_SERVER, $_SESSION, $codigo] = $original; http_response_code($codigo ?: 200);
    }
}
