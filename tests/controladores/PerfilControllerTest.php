<?php
require_once __DIR__ . '/../../app/controladores/PerfilController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
function perfilControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $original = [$_SESSION, $_POST, $_SERVER, http_response_code()];
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $usuarios = new UsuariosRepository($pdo);
        $owner = (int) $pdo->query('SELECT owner_id FROM camp WHERE id = ' . $f['ropa'])->fetchColumn();
        $nivelInicial = (new ValidacionesRepository($pdo))->findByNivel(1)->getId();
        $donante = (int) $usuarios->insertarUsuario(Usuario::crear('Donante confianza', $f['prefijo'] . '-nivel@example.com', 'Password1234', $nivelInicial));
        $repo = new ConfianzaRepository($pdo);
        $estado = $repo->actualizar($donante);
        TestUtils::comprobar((int) $estado['nivel'] === 1 && $estado['total'] === 0, 'Sin documento ni actividad permanece Blanco');
        $stmt = $pdo->prepare('INSERT INTO donacion (id_usuario,id_camp,cantidad) VALUES (?, ?, ?)');
        $stmt->execute([$donante, $f['ropa'], 2]);
        TestUtils::comprobar((int) $repo->actualizar($donante)['nivel'] === 1 && (int) $repo->actualizar($owner)['nivel'] === 1, 'Donar o recibir sin documento no cambia el nivel Blanco');
        $repo->guardarDocumento($donante, 'Pasaporte', 'abc123456');
        $estado = $repo->actualizar($donante);
        TestUtils::comprobar((int) $estado['nivel'] === 3 && $estado['realizadas'] === 1 && $usuarios->findById($donante)->getDocumento() === 'ABC123456', 'Cargar pasaporte reconoce la donación anterior y pasa directamente a Azul');
        $repo->guardarDocumento($owner, 'DNI', '35123456');
        $estado = $repo->actualizar($owner);
        TestUtils::comprobar((int) $estado['nivel'] === 3 && $estado['recibidas'] === 1, 'Recibir una donación con documento también da nivel Azul');
        $stmt->execute([$donante, $f['ropa'], 1]);
        TestUtils::comprobar((int) $repo->actualizar($donante)['nivel'] === 3, 'Dos donaciones mantienen el nivel Azul');
        $stmt->execute([$donante, $f['ropa'], 1]);
        TestUtils::comprobar((int) $repo->actualizar($donante)['nivel'] === 4 && (int) $repo->actualizar($owner)['nivel'] === 4, 'Tres donaciones realizadas o recibidas dan nivel Violeta');
        TestUtils::comprobar($usuarios->findById($donante)->getIdValidacion() === (int) $repo->actualizar($donante)['id'], 'Guarda el ID del nivel existente en la cuenta');
        $nuevo = (int) $usuarios->insertarUsuario(Usuario::crear('Sin actividad', $f['prefijo'] . '-nuevo@example.com', 'Password1234', $nivelInicial));
        $controller = new PerfilController($pdo);
        $render = static function (?Usuario $actor) use ($controller): array {
            http_response_code(200); ob_start();
            try { $controller->mostrar($actor); return [http_response_code(), ob_get_contents()]; }
            finally { ob_end_clean(); }
        };
        $_SERVER['REQUEST_METHOD'] = 'GET'; $_POST = [];
        [$codigo] = $render(null); TestUtils::comprobar($codigo === 303, 'El perfil requiere sesión');
        [$codigo, $html] = $render($usuarios->findById($nuevo));
        $descripcion = $pdo->query('SELECT descripcion FROM validaciones WHERE nivel = 1')->fetchColumn();
        TestUtils::comprobar($codigo === 200 && str_contains($html, $descripcion) && str_contains($html, 'Podés cargar tu DNI o Pasaporte') && str_contains($html, 'name="csrf_token"'), 'El perfil muestra el significado de la base, el aviso y el formulario protegido');
        $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = ['csrf_token' => 'incorrecto', 'tipo_documento' => 'DNI', 'documento' => '35123456'];
        [$codigo] = $render($usuarios->findById($nuevo));
        TestUtils::comprobar($codigo === 403 && $usuarios->findById($nuevo)->getDocumento() === null, 'Sin CSRF no guarda documento ni asciende');
        $_POST = ['csrf_token' => Csrf::generar(), 'tipo_documento' => 'DNI', 'documento' => '<script>1</script>'];
        [$codigo, $html] = $render($usuarios->findById($nuevo));
        TestUtils::comprobar($codigo === 422 && !str_contains($html, '<script>1</script>') && $usuarios->findById($nuevo)->getDocumento() === null, 'Valida el DNI y escapa los valores rechazados');
        $_POST['documento'] = '30123456'; $_POST['id_usuario'] = (string) $owner;
        [$codigo] = $render($usuarios->findById($nuevo));
        TestUtils::comprobar($codigo === 303 && (int) $repo->actualizar($nuevo)['nivel'] === 2 && $usuarios->findById($owner)->getDocumento() === '35123456', 'Carga el documento solo en la cuenta autenticada y pasa a Celeste si no tiene donaciones');
        $pdo->exec("UPDATE validaciones SET descripcion = 'Descripción desde BD <script>nivel</script>' WHERE nivel = 4");
        $_SERVER['REQUEST_METHOD'] = 'GET'; $_POST = [];
        [$codigo, $html] = $render($usuarios->findById($donante));
        TestUtils::comprobar(str_contains($html, 'Descripción desde BD &lt;script&gt;nivel&lt;/script&gt;') && str_contains($html, 'Donaciones realizadas: 3'), 'Los textos de niveles se leen de la base y se escapan al mostrarlos');
        $pdo->exec('UPDATE camp SET owner_id = ' . $nuevo . ' WHERE id = ' . $f['mesa']);
        $stmt = $pdo->prepare('INSERT INTO donacion (id_usuario,id_camp,cantidad) VALUES (?, ?, ?)');
        $stmt->execute([$nuevo, $f['ropa'], 1]);
        $stmt->execute([$owner, $f['mesa'], 1]);
        $stmt->execute([$owner, $f['mesa'], 1]);
        $mixto = $repo->actualizar($nuevo);
        TestUtils::comprobar((int) $mixto['nivel'] === 4 && $mixto['realizadas'] === 1 && $mixto['recibidas'] === 2, 'Combina donaciones realizadas y recibidas para alcanzar el nivel Violeta');
    } finally {
        $pdo->rollBack(); [$_SESSION, $_POST, $_SERVER, $codigo] = $original; http_response_code($codigo ?: 200);
    }
}
