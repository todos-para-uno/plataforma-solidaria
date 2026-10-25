<?php
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
require_once __DIR__ . '/../../app/modelos/NivelValidacion.php';

function usuariosRepositoryTest(PDO $pdo): void
{
    require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
    require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';

    echo 'INICIANDO TEST: app/repositorios/UsuariosRepository.php' . eol();

    $validacionesRepositorio = new ValidacionesRepository($pdo);
    $usuariosRepositorio = new UsuariosRepository($pdo);

    $validacion = $validacionesRepositorio->findByNivel(
        NivelValidacion::DEFAULT_NIVEL_USUARIO
    );

    if ($validacion === null) {
        throw new RuntimeException(
            'No existe la validación inicial.'
        );
    }

    $validacionId = $validacion->getId();

    // Identificador único para no depender de usuarios anteriores.
    $mail = 'test-' . bin2hex(random_bytes(8)) . '@example.com';
    $nombre = 'Usuario_' . bin2hex(random_bytes(8));

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
            'id_validacion' => $validacionId
        ]);

        TestUtils::comprobar(
            (int) $id > 0,
            'Se insertó un usuario y se obtuvo su ID' . eol()
        );

        // 1.1. VERIFICAR UNICIDAD DEL CORREO
        $correoDuplicadoRechazado = false;

        try {
            $usuariosRepositorio->save([
                'nombre' => 'Usuario duplicado',
                'mail' => $mail,
                'telefono' => '0000000000',
                'pass' => password_hash('PasswordPrueba456', PASSWORD_BCRYPT),
                'verificado' => 0,
                'admin' => 0,
                'id_validacion' => $validacionId
            ]);
        }catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                $correoDuplicadoRechazado = true;
            } else {
                throw $e;
            }
        }

        TestUtils::comprobar(
            $correoDuplicadoRechazado,
            'La base de datos rechaza correos electrónicos duplicados' . eol()
        );

        $usuarioEntidad = $usuariosRepositorio->findById($id);

        $usuarios = $usuariosRepositorio->findAll();

        TestUtils::comprobar(
            count($usuarios) > 0,
            'findAll devuelve una colección no vacía' . eol()
        );

        $usuariosPorNombre = $usuariosRepositorio->findByNombre($nombre);

        TestUtils::comprobar(
            count($usuariosPorNombre) > 0,
            'findByNombre devuelve una colección no vacía' . eol()
        );

        $todosSonUsuarios = true;
        $encontroUsuarioPrueba = false;

        foreach ($usuariosPorNombre as $usuario) {
            if (!($usuario instanceof Usuario)) {
                $todosSonUsuarios = false;
                continue;
            }

            if ($usuario->getId() === (int) $id) {
                $encontroUsuarioPrueba = true;
            }
        }

        TestUtils::comprobar(
            $todosSonUsuarios,
            'findByNombre devuelve únicamente objetos Usuario' . eol()
        );

        TestUtils::comprobar(
            $encontroUsuarioPrueba,
            'findByNombre incluye el usuario insertado' . eol()
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
            $usuarioEntidad !== null
                && $usuarioEntidad->getIdValidacion() === $validacionId,
            'La entidad recuperada conserva su ID de validación' . eol()
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
        $usuarioPorMail = $usuariosRepositorio->findByMail($mail);

        TestUtils::comprobar(
            $usuarioPorMail instanceof Usuario,
            'findByMail devuelve una entidad Usuario' . eol()
        );

        TestUtils::comprobar(
            $usuarioPorMail?->getEmail() === $mail,
            'findByMail conserva el correo buscado' . eol()
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

        // 6. BUSQUEDAS SIN COINCIDENCIAS
        TestUtils::comprobar(
            $usuariosRepositorio->findByNombre($nombre) === [],
            'findByNombre devuelve una colección vacía cuando no hay coincidencias' . eol()
        );

        TestUtils::comprobar(
            $usuariosRepositorio->findByMail($mail) === null,
            'findByMail devuelve null cuando el correo no existe' . eol()
        );


        echo 'Pruebas de UsuariosRepository completadas correctamente.' . eol();


        

    } finally {
        // Incluso si falla una comprobación, deshacemos los cambios.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

}
