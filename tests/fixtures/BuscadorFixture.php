<?php
require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../app/repositorios/CampRepository.php';
require_once __DIR__ . '/../../app/repositorios/CatCampRepository.php';

/** Llamar dentro de una transacción y deshacerla al terminar la prueba. */
function crearFixtureBuscador(PDO $pdo): array
{
    $prefijo = 'buscador-' . bin2hex(random_bytes(6));
    $validacion = (new ValidacionesRepository($pdo))->findByNivel(1);
    if ($validacion === null) {
        throw new RuntimeException('Falta el nivel de validación inicial en la base de prueba.');
    }
    $usuario = (new UsuariosRepository($pdo))->insertarUsuario(
        Usuario::crear('Usuario buscador', $prefijo . '@example.com', 'PasswordBuscar123', $validacion->getId())
    );
    $categorias = new CatCampRepository($pdo);
    $alimentos = (int) $categorias->save(['nombre' => $prefijo . ' Alimentos']);
    $abrigo = (int) $categorias->save(['nombre' => $prefijo . ' Abrigo']);
    $pdo->exec("INSERT INTO ubicaciones (provincia, localidad, latitud, longitud) VALUES ('Buenos Aires', 'La Plata', -34.92, -57.95)");
    $ubicacion = (int) $pdo->lastInsertId();
    $campanas = new CampRepository($pdo);
    $crear = static function (string $nombre, string $descripcion, int $categoria, string $estado = 'activa', bool $monetaria = true) use ($campanas, $usuario, $ubicacion): int {
        return (int) $campanas->save([
            'owner_id' => $usuario, 'nombre' => $nombre, 'descripcion' => $descripcion,
            'categoria' => $categoria, 'monetaria' => (int) $monetaria, 'estado' => $estado,
            'cbu' => $monetaria ? '0000000000000000000001' : null,
            'id_ubicacion' => $monetaria ? null : $ubicacion,
        ]);
    };
    $mesa = $crear($prefijo . ' Comedor', 'Alimentos para el barrio <script>alert(1)</script>', $alimentos);
    $ropa = $crear($prefijo . ' Abrigo', $prefijo . ' Comedor de invierno', $abrigo, 'activa', false);
    $literal = $crear($prefijo . ' 100%_!', 'Una campaña con signos en el nombre.', $alimentos);
    $crear($prefijo . ' Suspendida', 'Comedor suspendido', $alimentos, 'suspendida');
    $crear($prefijo . ' Finalizada', 'Comedor finalizado', $abrigo, 'finalizada');
    return compact('prefijo', 'alimentos', 'abrigo', 'mesa', 'ropa', 'literal');
}
