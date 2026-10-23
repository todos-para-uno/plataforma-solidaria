<?php
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function usuariosRepositoryTest(PDO $pdo): void
{
    require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
    require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';

    echo 'INICIANDO TEST: app/repositorios/UsuariosRepository.php' . eol();

    $validacionesRepositorio = new ValidacionesRepository($pdo);
    $usuariosRepositorio = new UsuariosRepository($pdo);

    $validaciones = $validacionesRepositorio->findByNivel(1);

    if ($validaciones === []) {
        throw new RuntimeException(
            'No existe una validación con nivel 1.'
        );
    }

    $validacionId = (int) $validaciones[0]['id'];

    // Identificador único para no depender de usuarios anteriores.
    $mail = 'test-' . bin2hex(random_bytes(8)) . '@example.com';
    $nombre = 'Usuario de prueba';

    $pdo->beginTransaction();

    try {
        // 1. INSERTAR
        $id = $usuariosRepositorio->save([
            'nombre' => $nombre,
            'mail' => $mail,
            'telefono' => '0000000000',
            'pass' => password_hash('PasswordPrueba123', PASSWORD_BCRYPT),
            'verificado' => 0,
            'admin' => 0,
            'validacion' => $validacionId
        ]);

        TestUtils::comprobar(
            (int) $id > 0,
            'Se insertó un usuario y se obtuvo su ID' . eol()
        );

        $usuarioEntidad = $usuariosRepositorio->findById($id);

        $usuarios = $usuariosRepositorio->findAll();

        TestUtils::comprobar(
            count($usuarios) > 0,
            'findAll devuelve una colección no vacía' . eol()
        );

        TestUtils::comprobar(
            $usuarios[0] instanceof Usuario,
            'findAll devuelve objetos Usuario' . eol()
        );

        $usuarioEncontrado = false;

        foreach ($usuarios as $usuario) {
            if ($usuario->getId() === (int) $id) {
                $usuarioEncontrado = true;
                break;
            }
        }

        TestUtils::comprobar(
            $usuarioEncontrado,
            'findAll incluye el usuario insertado' . eol()
        );


        TestUtils::comprobar(
            $usuarioEntidad instanceof Usuario,
            'findById devuelve un objeto Usuario' . eol()
        );

        TestUtils::comprobar(
            $usuarioEntidad->getEmail() === $mail,
            'La entidad recuperada conserva su correo' . eol()
        );


        // 2. RECUPERAR POR ID
        $filaUsuario = $usuariosRepositorio->findRowById($id);

        TestUtils::comprobar(
            $filaUsuario !== null,
            'Se recuperó el usuario por su ID' . eol()
        );

        TestUtils::comprobar(
            $filaUsuario['mail'] === $mail,
            'El correo recuperado coincide con el insertado' . eol()
        );

        // 3. RECUPERAR POR CORREO
        $usuariosEncontrados = $usuariosRepositorio->findByMail($mail);

        TestUtils::comprobar(
            count($usuariosEncontrados) === 1,
            'Se encontró exactamente un usuario con ese correo' . eol()
        );

        // 4. ACTUALIZAR
        $nuevoNombre = 'Usuario actualizado';

        $usuariosRepositorio->save([
            'id' => $id,
            'nombre' => $nuevoNombre
        ]);

        $filaActualizada = $usuariosRepositorio->findRowById($id);

        TestUtils::comprobar(
            $filaActualizada !== null
                && $filaActualizada['nombre'] === $nuevoNombre,
            'La actualización del nombre se guardó correctamente' . eol()
        );

        // 5. ELIMINAR
        $eliminado = $usuariosRepositorio->delete($id);

        TestUtils::comprobar(
            $eliminado,
            'El usuario se eliminó correctamente' . eol()
        );

        TestUtils::comprobar(
            $usuariosRepositorio->findRowById($id) === null,
            'El usuario eliminado ya no puede recuperarse' . eol()
        );

        TestUtils::comprobar(
            $usuariosRepositorio->findById($id) === null,
            'La búsqueda de una entidad inexistente devuelve null' . eol()
        );        

        echo 'Pruebas de UsuariosRepository completadas correctamente.' . eol();

    } finally {
        // Incluso si falla una comprobación, deshacemos los cambios.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    echo '<hr>';
}
