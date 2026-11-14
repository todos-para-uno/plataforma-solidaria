
<?php

require_once __DIR__ . '/../../app/modelos/Usuarios.php';
require_once __DIR__ . '/../../app/controladores/BajaCuentaController.php';
require_once __DIR__ . '/../conexion_pruebas.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function bajaCuentaControllerTest(?PDO $pdoDesarrollo = null): void
{
    // Nunca utilizamos la conexión de desarrollo.
    $pdo = conectarBdPruebas();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!session_start()) {
            throw new RuntimeException('No se pudo iniciar la sesión de pruebas.');
        }
    }

    $sesionOriginal = $_SESSION;
    $postOriginal = $_POST;
    $codigoOriginal = http_response_code();

    $idValidacion = null;
    $idUsuario = null;

    // Esta prueba no debe ejecutar ninguna baja efectiva.
    $pdo->beginTransaction();

    try {
        $correo = 'controlador-baja-'
            . bin2hex(random_bytes(8))
            . '@example.invalid';

        $clave = 'PasswordTemporal123!';
        $hash = password_hash($clave, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO validaciones (nivel, nombre, descripcion)
             VALUES (?, ?, ?)'
        );

        $stmt->execute([
            1,
            'Prueba ' . bin2hex(random_bytes(4)),
            'Validación temporal'
        ]);

        $idValidacion = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO usuarios
                (nombre, mail, pass, id_validacion)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            'Usuario de prueba',
            $correo,
            $hash,
            $idValidacion
        ]);

        $idUsuario = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'SELECT * FROM usuarios WHERE id = ?'
        );

        $stmt->execute([$idUsuario]);
        $filaUsuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$filaUsuario) {
            throw new RuntimeException(
                'No se pudo recuperar el usuario de prueba.'
            );
        }

        $usuario = Usuario::desdePersistencia($filaUsuario);

        // Simulamos una sesión y un formulario legítimos.
        $_SESSION = [];
        $token = Csrf::generar();

        $_POST = [
            'csrf_token' => $token,
            'pass' => $clave,
            'eliminar_datos' => '1',

            // Intencionalmente NO incluimos
            // confirmar_eliminacion.
        ];

        $controlador = new BajaCuentaController($pdo);

        http_response_code(200);
        ob_start();

        try {
            $controlador->procesar($usuario, '');
            $respuesta = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $codigo = http_response_code();

        TestUtils::comprobar(
            $codigo === 422,
            'Rechaza eliminación irreversible sin segunda confirmación.'
        );

        TestUtils::comprobar(
            str_contains($respuesta, 'irreversible'),
            'Informa que falta la confirmación irreversible.'
        );

        $stmt->execute([$idUsuario]);
        $usuarioPosterior = $stmt->fetch(PDO::FETCH_ASSOC);

        TestUtils::comprobar(
            $usuarioPosterior !== false
            && $usuarioPosterior['estado_cuenta'] === 'activa',
            'La cuenta permanece activa tras el rechazo.'
        );

        TestUtils::comprobar(
            $usuarioPosterior['mail'] === $correo
            && $usuarioPosterior['pass'] === $hash,
            'El rechazo conserva el correo y la contraseña.'
        );
    } finally {
        // Descartamos los registros temporales.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION = $sesionOriginal;
        $_POST = $postOriginal;
        http_response_code($codigoOriginal ?: 200);
    }
}

function bajaCuentaControllerDesactivacionTest(?PDO $pdoDesarrollo = null): void
{
    $pdo = conectarBdPruebas();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!session_start()) {
            throw new RuntimeException('No se pudo iniciar la sesión.');
        }
    }

    $sesionOriginal = $_SESSION;
    $postOriginal = $_POST;
    $codigoOriginal = http_response_code();

    $idValidacion = null;
    $validacionCreada = false;
    $idUsuario = null;

    try {
        // Reutilizar la validación de nivel 1 si ya existe.
        $stmt = $pdo->query(
            'SELECT id FROM validaciones WHERE nivel = 1 LIMIT 1'
        );

        $idValidacion = $stmt->fetchColumn();

        if ($idValidacion === false) {
            $stmt = $pdo->prepare(
                'INSERT INTO validaciones (nivel, nombre, descripcion)
                 VALUES (?, ?, ?)'
            );

            $stmt->execute([
                1,
                'Prueba ' . bin2hex(random_bytes(4)),
                'Validación temporal'
            ]);

            $idValidacion = (int) $pdo->lastInsertId();
            $validacionCreada = true;
        }

        $correo = 'desactivacion-'
            . bin2hex(random_bytes(8))
            . '@example.invalid';

        $clave = 'PasswordTemporal123!';
        $hash = password_hash($clave, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO usuarios
                (nombre, mail, pass, id_validacion)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            'Usuario de prueba',
            $correo,
            $hash,
            (int) $idValidacion
        ]);

        $idUsuario = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'SELECT * FROM usuarios WHERE id = ?'
        );

        $stmt->execute([$idUsuario]);
        $filaUsuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$filaUsuario) {
            throw new RuntimeException(
                'No se pudo recuperar el usuario de prueba.'
            );
        }

        $usuario = Usuario::desdePersistencia($filaUsuario);

        $_SESSION = [];
        $token = Csrf::generar();

        // Desactivación normal: no se solicita eliminar datos.
        $_POST = [
            'csrf_token' => $token,
            'pass' => $clave
        ];

        $controlador = new BajaCuentaController($pdo);

        http_response_code(200);
        ob_start();

        try {
            $controlador->procesar($usuario, '/pruebas');
            $respuesta = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $codigo = http_response_code();

        $stmt->execute([$idUsuario]);
        $usuarioPosterior = $stmt->fetch(PDO::FETCH_ASSOC);

        TestUtils::comprobar(
            $codigo === 303,
            'La desactivación normal finaliza con una redirección.'
        );

        TestUtils::comprobar(
            $usuarioPosterior !== false
            && $usuarioPosterior['estado_cuenta'] === 'desactivada',
            'La cuenta queda desactivada sin segunda confirmación.'
        );

        TestUtils::comprobar(
            $usuarioPosterior !== false
            && $usuarioPosterior['mail'] === $correo
            && $usuarioPosterior['pass'] === $hash,
            'La desactivación conserva correo y contraseña.'
        );

        $servicio = new BajaCuenta($pdo);
        $reactivada = $servicio->reactivar($idUsuario);

        $stmt->execute([$idUsuario]);
        $usuarioReactivado = $stmt->fetch(PDO::FETCH_ASSOC);

        TestUtils::comprobar(
            $reactivada
            && $usuarioReactivado !== false
            && $usuarioReactivado['estado_cuenta'] === 'activa',
            'La cuenta desactivada puede reactivarse.'
        );
    } finally {
        // La desactivación confirma su propia transacción.
        // Limpiamos únicamente los registros creados aquí.
        try {
            if ($idUsuario !== null) {
                $stmt = $pdo->prepare(
                    'DELETE FROM usuarios WHERE id = ?'
                );
                $stmt->execute([$idUsuario]);
            }

            if ($validacionCreada && $idValidacion !== null) {
                $stmt = $pdo->prepare(
                    'DELETE FROM validaciones WHERE id = ?'
                );
                $stmt->execute([$idValidacion]);
            }
        } finally {
            $_SESSION = $sesionOriginal;
            $_POST = $postOriginal;
            http_response_code($codigoOriginal ?: 200);
        }
    }
}


function bajaCuentaControllerEliminacionTest(?PDO $pdoDesarrollo = null): void
{
    $pdo = conectarBdPruebas();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!session_start()) {
            throw new RuntimeException('No se pudo iniciar la sesión.');
        }
    }

    $sesionOriginal = $_SESSION;
    $postOriginal = $_POST;
    $codigoOriginal = http_response_code();

    $idValidacion = null;
    $validacionCreada = false;
    $idUsuario = null;

    try {
        // La eliminación irreversible necesita una validación de nivel 1.
        $stmt = $pdo->query(
            'SELECT id FROM validaciones WHERE nivel = 1 LIMIT 1'
        );
        $idValidacion = $stmt->fetchColumn();

        if ($idValidacion === false) {
            $stmt = $pdo->prepare(
                'INSERT INTO validaciones (nivel, nombre, descripcion)
                 VALUES (?, ?, ?)'
            );
            $stmt->execute([
                1,
                'Prueba ' . bin2hex(random_bytes(4)),
                'Validación temporal'
            ]);

            $idValidacion = (int) $pdo->lastInsertId();
            $validacionCreada = true;
        }

        $correo = 'eliminacion-'
            . bin2hex(random_bytes(8))
            . '@example.invalid';

        $clave = 'PasswordTemporal123!';
        $hash = password_hash($clave, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO usuarios
                (nombre, mail, pass, id_validacion)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            'Usuario de prueba',
            $correo,
            $hash,
            (int) $idValidacion
        ]);

        $idUsuario = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'SELECT * FROM usuarios WHERE id = ?'
        );
        $stmt->execute([$idUsuario]);
        $filaUsuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$filaUsuario) {
            throw new RuntimeException(
                'No se pudo recuperar el usuario de prueba.'
            );
        }

        $usuario = Usuario::desdePersistencia($filaUsuario);

        $_SESSION = [];
        $token = Csrf::generar();

        // Solicitud legítima con ambas confirmaciones.
        $_POST = [
            'csrf_token' => $token,
            'pass' => $clave,
            'eliminar_datos' => '1',
            'confirmar_eliminacion' => '1'
        ];

        $controlador = new BajaCuentaController($pdo);

        http_response_code(200);
        ob_start();

        try {
            $controlador->procesar($usuario, '/pruebas');
        } finally {
            ob_end_clean();
        }

        $codigo = http_response_code();

        $stmt->execute([$idUsuario]);
        $usuarioPosterior = $stmt->fetch(PDO::FETCH_ASSOC);

        TestUtils::comprobar(
            $codigo === 303,
            'La eliminación confirmada finaliza con una redirección.'
        );

        TestUtils::comprobar(
            $usuarioPosterior !== false
            && $usuarioPosterior['estado_cuenta'] === 'eliminada',
            'La cuenta queda eliminada con ambas confirmaciones.'
        );

        TestUtils::comprobar(
            $usuarioPosterior !== false
            && $usuarioPosterior['nombre'] === 'Usuario eliminado'
            && $usuarioPosterior['mail'] !== $correo
            && str_ends_with(
                $usuarioPosterior['mail'],
                '@example.invalid'
            ),
            'La eliminación anonimiza el nombre y el correo.'
        );

        TestUtils::comprobar(
            $usuarioPosterior !== false
            && $usuarioPosterior['pass'] !== $hash
            && !password_verify($clave, $usuarioPosterior['pass']),
            'La eliminación invalida la contraseña original.'
        );

        $servicio = new BajaCuenta($pdo);
        $reactivada = $servicio->reactivar($idUsuario);

        TestUtils::comprobar(
            $reactivada === false,
            'Una cuenta eliminada no puede reactivarse.'
        );

        $stmt->execute([$idUsuario]);
        $estadoFinal = $stmt->fetch(PDO::FETCH_ASSOC);

        TestUtils::comprobar(
            $estadoFinal !== false
            && $estadoFinal['estado_cuenta'] === 'eliminada',
            'El intento de reactivación no modifica la cuenta eliminada.'
        );
    } finally {
        // Eliminar exclusivamente los registros creados por esta prueba.
        try {
            if ($idUsuario !== null) {
                $stmt = $pdo->prepare(
                    'DELETE FROM usuarios WHERE id = ?'
                );
                $stmt->execute([$idUsuario]);
            }

            if ($validacionCreada && $idValidacion !== null) {
                $stmt = $pdo->prepare(
                    'DELETE FROM validaciones WHERE id = ?'
                );
                $stmt->execute([$idValidacion]);
            }
        } finally {
            $_SESSION = $sesionOriginal;
            $_POST = $postOriginal;
            http_response_code($codigoOriginal ?: 200);
        }
    }
}
