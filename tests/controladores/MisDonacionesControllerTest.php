<?php
require_once __DIR__ . '/../../app/controladores/MisDonacionesController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../app/repositorios/ObjetivosRepository.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
function misDonacionesControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $original = [$_SESSION, $_GET, http_response_code()];
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $usuarios = new UsuariosRepository($pdo);
        $owner = (int) $pdo->query('SELECT owner_id FROM camp WHERE id = ' . $f['ropa'])->fetchColumn();
        $nivel = (new ValidacionesRepository($pdo))->findByNivel(1)->getId();
        $correo = $f['prefijo'] . '-historial@example.com';
        $donante = (int) $usuarios->insertarUsuario(Usuario::crear('Donante historial', $correo, 'Password1234', $nivel));
        $ajeno = (int) $usuarios->insertarUsuario(Usuario::crear('Sin donaciones', $f['prefijo'] . '-vacio@example.com', 'Password1234', $nivel));
        $stmt = $pdo->prepare('INSERT INTO categoria_insumos (nombre) VALUES (?)'); $stmt->execute(['Frazadas <script>insumo</script>']);
        $cat = (int) $pdo->lastInsertId();
        $objetivo = (int) (new ObjetivosRepository($pdo))->save(['id_camp' => $f['ropa'], 'id_categoria_insumos' => $cat, 'objetivo' => 20]);
        $stmt = $pdo->prepare('UPDATE camp SET nombre = ?, imagen = ? WHERE id = ?');
        $stmt->execute(['Abrigo <script>campaña</script>', '../config/privado.png', $f['ropa']]);
        $repo = new DonacionRepository($pdo);
        // El historial del donante recibe lo registrado por el creador, sin datos de demostración.
        $repo->registrarRecepcion($owner, $f['ropa'], $objetivo, $correo, '2,50');
        $repo->save(['id_usuario' => $ajeno, 'id_camp' => $f['mesa'], 'cantidad' => 123]);
        $controller = new MisDonacionesController($repo);
        $render = static function (?Usuario $usuario) use ($controller): array {
            http_response_code(200); ob_start();
            try { $controller->mostrar($usuario); return [http_response_code(), ob_get_contents()]; }
            finally { ob_end_clean(); }
        };
        [$codigo] = $render(null);
        TestUtils::comprobar($codigo === 303, 'Mis donaciones exige iniciar sesión');
        $_GET = ['id_usuario' => (string) $ajeno];
        [$codigo, $html] = $render($usuarios->findById($donante));
        TestUtils::comprobar($codigo === 200 && str_contains($html, '2,5 de Frazadas') && str_contains($html, 'Donante historial'), 'El donante ve la entrega real confirmada por el creador, incluido el decimal');
        TestUtils::comprobar(str_contains($html, 'href="./index.php?ruta=campana&amp;id=' . $f['ropa'] . '"'), 'El botón abre la campaña correcta');
        TestUtils::comprobar(!str_contains($html, $f['prefijo'] . ' Comedor') && !str_contains($html, 'Mochilas llenas de futuro'), 'La identidad proviene de la sesión, sin donaciones ajenas ni ejemplos');
        TestUtils::comprobar(str_contains($html, '&lt;script&gt;campaña&lt;/script&gt;') && !str_contains($html, '<script>campaña</script>') && !str_contains($html, '../config/privado.png'), 'Escapa nombres y rechaza imágenes fuera de la carpeta pública');
        TestUtils::comprobar(!str_contains($html, '<h3><a href="#"') && substr_count($html, '<main') === 1, 'El historial conserva la estructura compartida y reemplaza el enlace ficticio');
        [$codigo, $html] = $render($usuarios->findById($owner));
        TestUtils::comprobar($codigo === 200 && str_contains($html, 'Todavía no realizaste ninguna donación'), 'El creador no ve como propia la donación que recibió de otra persona');
        [$codigo, $html] = $render($usuarios->findById($ajeno));
        TestUtils::comprobar(str_contains($html, '$123') && !str_contains($html, 'Frazadas'), 'El otro usuario ve solo su propio historial y el formato monetario sigue funcionando');
        $pdo->exec("UPDATE camp SET estado = 'finalizada' WHERE id = " . $f['ropa']);
        [$codigo, $html] = $render($usuarios->findById($donante));
        TestUtils::comprobar($codigo === 200 && str_contains($html, 'Campaña finalizada.') && str_contains($html, '2,5 de Frazadas') && !str_contains($html, '>Ver campaña</a>'), 'Conserva el historial de campañas finalizadas sin ofrecer un enlace público que devolvería 404');
    } finally {
        $pdo->rollBack(); [$_SESSION, $_GET, $codigo] = $original; http_response_code($codigo ?: 200);
    }
}
