<?php
require_once __DIR__ . '/../../app/controladores/PerfilController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function perfilImagenControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $original = [$_SESSION, $_POST, $_GET, $_FILES, $_SERVER, http_response_code()];
    $tmp = sys_get_temp_dir() . '/perfil-integracion-' . bin2hex(random_bytes(6)); mkdir($tmp, 0700);
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $usuarios = new UsuariosRepository($pdo);
        $owner = (int) $pdo->query('SELECT owner_id FROM camp WHERE id = ' . $f['ropa'])->fetchColumn();
        $cuenta = $usuarios->findById($owner);
        $otro = (int) $usuarios->insertarUsuario(Usuario::crear('Otra cuenta', $f['prefijo'] . '-otro@example.com', 'Password1234', $cuenta->getIdValidacion()));
        $foto = $tmp . '/foto.jpg'; imagejpeg(imagecreatetruecolor(300, 400), $foto);
        $imagenes = new ImagenPerfil($tmp . '/destino', static fn(string $ruta): bool => is_file($ruta));
        $controller = new PerfilController($pdo, $imagenes);
        $ejecutar = static function (string $metodo, ?Usuario $actor) use ($controller): array {
            http_response_code(200); ob_start();
            try { $controller->$metodo($actor); return [http_response_code(), ob_get_contents()]; }
            finally { ob_end_clean(); }
        };
        $_GET = []; $_POST = []; $_FILES = []; $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_SERVER['CONTENT_LENGTH']);
        [$codigo] = $ejecutar('actualizar', null);
        TestUtils::comprobar($codigo === 303, 'Guardar el perfil exige sesión');
        $_POST = ['csrf_token' => 'incorrecto', 'nombre' => 'Modificado', 'apellido' => 'Apellido'];
        [$codigo] = $ejecutar('actualizar', $cuenta);
        TestUtils::comprobar($codigo === 403 && $usuarios->findById($owner)->getNombre() === $cuenta->getNombre(), 'Sin CSRF no modifica el perfil');
        $_SERVER['CONTENT_LENGTH'] = (string) (ImagenPerfil::tamanoMaximoTotal() + 1);
        [$codigo] = $ejecutar('actualizar', $cuenta);
        TestUtils::comprobar($codigo === 413, 'Un formulario demasiado grande se rechaza sin modificar el perfil');
        unset($_SERVER['CONTENT_LENGTH']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        [$codigo] = $ejecutar('actualizar', $cuenta);
        TestUtils::comprobar($codigo === 405, 'La acción de guardado solo permite POST');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf_token' => Csrf::generar(), 'nombre' => 'Nombre <script>perfil</script>', 'apellido' => 'Apellido <b>prueba</b>',
            'id_usuario' => (string) $otro, 'admin' => '1', 'mail' => 'cambiado@example.com', 'avatar' => '../config/conexion.php'];
        $_FILES = ['avatar' => ['name' => 'foto.jpeg', 'tmp_name' => $foto, 'error' => 0, 'size' => filesize($foto)]];
        [$codigo] = $ejecutar('actualizar', $cuenta);
        $guardada = $usuarios->findById($owner);
        $primeraFoto = $imagenes->archivo($guardada->getAvatar());
        TestUtils::comprobar($codigo === 303 && $guardada->getApellido() === $_POST['apellido'] && is_file($primeraFoto), 'Guarda nombre, apellido y una imagen recodificada');
        TestUtils::comprobar($guardada->getEmail() === $cuenta->getEmail() && !$guardada->getAdmin() && $usuarios->findById($otro)->getApellido() === null, 'Ignora ID, permisos, correo y ruta enviados por el navegador');
        $_SERVER['REQUEST_METHOD'] = 'GET'; $_POST = []; $_FILES = [];
        [$codigo, $html] = $ejecutar('mostrar', $guardada);
        TestUtils::comprobar($codigo === 200 && str_contains($html, 'Tu perfil se guardó correctamente.')
            && str_contains($html, '&lt;script&gt;perfil&lt;/script&gt;') && !str_contains($html, '<script>perfil</script>')
            && str_contains($html, 'ruta=imagen_perfil') && str_contains($html, 'name="apellido"'), 'La página y el modal muestran los datos escapados y la imagen a través de la ruta controlada');
        [$codigo, $binario] = $ejecutar('imagen', null);
        TestUtils::comprobar($codigo === 401 && $binario === '', 'La imagen exige una sesión autenticada');
        $_GET = ['id_usuario' => (string) $owner, 'archivo' => $guardada->getAvatar()];
        [$codigo, $binario] = $ejecutar('imagen', $usuarios->findById($otro));
        TestUtils::comprobar($codigo === 404 && $binario === '', 'Otra cuenta no puede leer la foto cambiando parámetros');
        [$codigo, $binario] = $ejecutar('imagen', $guardada);
        TestUtils::comprobar($codigo === 200 && $binario === file_get_contents($primeraFoto), 'La sesión puede recuperar únicamente su propia imagen procesada');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf_token' => Csrf::generar(), 'nombre' => 'No debe guardarse', 'apellido' => 'Tampoco'];
        $danada = $tmp . '/danada.jpg';
        $jpeg = file_get_contents($foto);
        file_put_contents($danada, substr($jpeg, 0, strpos($jpeg, "\xFF\xDA")) . "\xFF\xD9");
        $_FILES = ['avatar' => ['name' => 'danada.jpg', 'tmp_name' => $danada, 'error' => 0, 'size' => filesize($danada)]];
        [$codigo] = $ejecutar('actualizar', $guardada);
        TestUtils::comprobar($codigo === 503 && $usuarios->findById($owner)->getNombre() === $guardada->getNombre()
            && is_file($primeraFoto) && count(glob($tmp . '/destino/*')) === 1, 'Una conversión fallida conserva los datos y la foto anterior sin archivos parciales');
        $_POST = ['csrf_token' => Csrf::generar(), 'nombre' => 'Nombre final', 'apellido' => 'Apellido final'];
        $_FILES = ['avatar' => ['name' => 'foto.jpg', 'tmp_name' => $foto, 'error' => 0, 'size' => filesize($foto)]];
        [$codigo] = $ejecutar('actualizar', $guardada);
        $reemplazo = $usuarios->findById($owner);
        TestUtils::comprobar($codigo === 303 && !is_file($primeraFoto) && $imagenes->archivo($reemplazo->getAvatar()) !== null
            && count(glob($tmp . '/destino/*')) === 1, 'Reemplazar la foto elimina la anterior después de guardar el cambio');
        $inexistente = clone $cuenta; $inexistente->setId(2147483647);
        $antes = glob($tmp . '/destino/*');
        try { (new PerfilUsuario($pdo, $imagenes))->guardar($inexistente, $_POST, $_FILES['avatar']); throw new LogicException('Aceptó cuenta inexistente'); }
        catch (InvalidArgumentException) {}
        TestUtils::comprobar(glob($tmp . '/destino/*') === $antes, 'Un fallo en la base después de convertir limpia la nueva imagen');
        $_POST['eliminar_avatar'] = '1'; $_FILES = [];
        [$codigo] = $ejecutar('actualizar', $reemplazo);
        TestUtils::comprobar($codigo === 303 && $usuarios->findById($owner)->getAvatar() === null
            && glob($tmp . '/destino/*') === [], 'Quitar la foto limpia su ruta y su archivo');
        unset($_POST['eliminar_avatar']); $_POST['apellido'] = '';
        [$codigo] = $ejecutar('actualizar', $reemplazo);
        TestUtils::comprobar($codigo === 303 && $usuarios->findById($owner)->getApellido() === null, 'Permite guardar el perfil sin foto ni apellido');
        $_POST['apellido'] = ['valor'];
        [$codigo] = $ejecutar('actualizar', $reemplazo);
        TestUtils::comprobar($codigo === 422, 'Rechaza estructuras manipuladas en los campos personales');
    } finally {
        $pdo->rollBack();
        [$_SESSION, $_POST, $_GET, $_FILES, $_SERVER, $codigo] = $original; http_response_code($codigo ?: 200);
        foreach (glob($tmp . '/*') ?: [] as $ruta) {
            if (is_dir($ruta)) { foreach (glob($ruta . '/*') ?: [] as $archivo) unlink($archivo); rmdir($ruta); }
            else unlink($ruta);
        }
        rmdir($tmp);
    }
}
