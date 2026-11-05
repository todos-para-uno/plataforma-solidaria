
<?php

require_once __DIR__ . '/../../app/servicios/BajaCuenta.php';
require_once __DIR__ . '/../conexion_pruebas.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function bajaCuentaTest(?PDO $pdoDesarrollo = null): void
{
    $pdo = conectarBdPruebas();

    $temporal = sys_get_temp_dir()
        . '/baja-cuenta-' . bin2hex(random_bytes(8));

    $directorioImagenes = $temporal . '/campanas';

    if (!mkdir($directorioImagenes, 0700, true)) {
        throw new RuntimeException(
            'No se pudo crear el directorio temporal.'
        );
    }

    $idValidacion = null;
    $idUsuario = null;
    $idCategoria = null;
    $idCampana = null;

    try {
        // Validación mínima requerida.
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

        // Usuario descartable.
        $correoOriginal = 'prueba-' . bin2hex(random_bytes(8))
            . '@example.invalid';

        $passwordOriginal = password_hash(
            'password-temporal',
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare(
            'INSERT INTO usuarios
                (nombre, apellido, mail, pass, id_validacion)
             VALUES (?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            'Usuario',
            'De prueba',
            $correoOriginal,
            $passwordOriginal,
            $idValidacion
        ]);

        $idUsuario = (int) $pdo->lastInsertId();

        // Categoría descartable.
        $stmt = $pdo->prepare(
            'INSERT INTO cat_camp (nombre) VALUES (?)'
        );

        $stmt->execute([
            'Categoría prueba ' . bin2hex(random_bytes(6))
        ]);

        $idCategoria = (int) $pdo->lastInsertId();

        // Campaña activa.
        $stmt = $pdo->prepare(
            'INSERT INTO camp
                (owner_id, nombre, monetaria, categoria,
                 descripcion, cbu)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $idUsuario,
            'Campaña de prueba',
            1,
            $idCategoria,
            'Descripción personal de prueba',
            '0000000000000000000001'
        ]);

        $idCampana = (int) $pdo->lastInsertId();

        // Archivos físicos simulados.
        $archivos = [];

        foreach (['jpg', 'png'] as $orden => $extension) {
            $nombre = bin2hex(random_bytes(16))
                . '.' . $extension;

            $rutaRelativa = 'subidas/campanas/' . $nombre;
            $rutaFisica = $directorioImagenes . '/' . $nombre;

            file_put_contents(
                $rutaFisica,
                'Contenido temporal de prueba'
            );

            $archivos[] = $rutaFisica;

            $stmt = $pdo->prepare(
                'INSERT INTO campana_imagenes
                    (id_camp, ruta, orden)
                 VALUES (?, ?, ?)'
            );

            $stmt->execute([
                $idCampana,
                $rutaRelativa,
                $orden
            ]);
        }

        // Ejecutamos la baja irreversible.
        $servicio = new BajaCuenta(
            $pdo,
            null,
            new ImagenesCampana($directorioImagenes)
        );

        $servicio->desactivar($idUsuario, true);

        // Verificamos la anonimización del usuario.
        $stmt = $pdo->prepare(
            'SELECT * FROM usuarios WHERE id = ?'
        );
        $stmt->execute([$idUsuario]);
        $usuario = $stmt->fetch();

        TestUtils::comprobar(
            $usuario['estado_cuenta'] === 'eliminada'
                && $usuario['nombre'] === 'Usuario eliminado'
                && $usuario['apellido'] === null
                && $usuario['mail'] !== $correoOriginal
                && $usuario['avatar'] === null,
            'La baja irreversible anonimiza al usuario.'
        );

        TestUtils::comprobar(
            !password_verify('password-temporal', $usuario['pass'])
                && $usuario['pass'] !== $passwordOriginal,
            'La baja irreversible reemplaza la contraseña.'
        );

        // Verificamos el cierre y la anonimización de la campaña.
        $stmt = $pdo->prepare(
            'SELECT * FROM camp WHERE id = ?'
        );
        $stmt->execute([$idCampana]);
        $campana = $stmt->fetch();

        TestUtils::comprobar(
            $campana['estado'] === 'cerrada'
                && $campana['motivo_cierre'] === 'usuario_eliminado'
                && $campana['cbu'] === null
                && $campana['imagen'] === null
                && $campana['descripcion']
                    === 'Campaña de usuario eliminado',
            'La baja cierra y anonimiza la campaña.'
        );

        // Verificamos que no queden referencias a las imágenes.
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM campana_imagenes
             WHERE id_camp = ?'
        );
        $stmt->execute([$idCampana]);

        TestUtils::comprobar(
            (int) $stmt->fetchColumn() === 0,
            'La baja elimina las referencias a las imágenes.'
        );

        // Comprobación principal de la regresión.
        TestUtils::comprobar(
            !is_file($archivos[0]) && !is_file($archivos[1]),
            'La baja elimina físicamente imágenes JPEG y PNG.'
        );

    } finally {
        // Limpieza de registros descartables.
        if ($idCampana !== null) {
            $stmt = $pdo->prepare(
                'DELETE FROM campana_imagenes WHERE id_camp = ?'
            );
            $stmt->execute([$idCampana]);

            $stmt = $pdo->prepare(
                'DELETE FROM camp WHERE id = ?'
            );
            $stmt->execute([$idCampana]);
        }

        if ($idCategoria !== null) {
            $stmt = $pdo->prepare(
                'DELETE FROM cat_camp WHERE id = ?'
            );
            $stmt->execute([$idCategoria]);
        }

        if ($idUsuario !== null) {
            $stmt = $pdo->prepare(
                'DELETE FROM usuarios WHERE id = ?'
            );
            $stmt->execute([$idUsuario]);
        }

        if ($idValidacion !== null) {
            $stmt = $pdo->prepare(
                'DELETE FROM validaciones WHERE id = ?'
            );
            $stmt->execute([$idValidacion]);
        }

        // Limpieza de archivos temporales.
        foreach (glob($directorioImagenes . '/*') ?: [] as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }

        rmdir($directorioImagenes);
        rmdir($temporal);
    }
}
