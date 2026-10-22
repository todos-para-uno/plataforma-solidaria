<?php
require_once __DIR__ . '/../../app/modelos/Ubicaciones.php';

function ubicacionesComprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException('FALLÓ: ' . $mensaje);
    }
    echo 'OK: ' . $mensaje . PHP_EOL;
}

function ubicacionesTest(): void
{
    echo 'INICIANDO TEST: app/modelos/Ubicaciones.php' . PHP_EOL;

    $ubicacion = new Ubicaciones(' Buenos Aires ', ' Avellaneda ', -34.663561, -58.364675);
    ubicacionesComprobar(
        $ubicacion->getProvincia() === 'Buenos Aires'
            && $ubicacion->getLocalidad() === 'Avellaneda'
            && $ubicacion->getLatitud() === -34.663561
            && $ubicacion->getLongitud() === -58.364675
            && $ubicacion->getReferencia() === null
            && $ubicacion->getId() === null,
        'Se crea una ubicación con coordenadas del mapa, sin referencia ni ID asignado.'
    );

    $conReferencia = new Ubicaciones(
        'Córdoba', 'Río Cuarto', -33.123158, -64.349344, ' Portón azul, frente a la plaza. '
    );
    ubicacionesComprobar(
        $conReferencia->getReferencia() === 'Portón azul, frente a la plaza.',
        'La referencia es opcional y se recortan los espacios de los extremos.'
    );

    foreach ([null, '', '   '] as $referenciaVacia) {
        $conReferencia->setReferencia($referenciaVacia);
        ubicacionesComprobar(
            $conReferencia->getReferencia() === null,
            'Una referencia sin completar se representa como null.'
        );
    }

    foreach ([[-90.0, -180.0], [90.0, 180.0], [0.0, 0.0]] as [$latitud, $longitud]) {
        $limite = new Ubicaciones('Buenos Aires', 'Avellaneda', $latitud, $longitud);
        ubicacionesComprobar(
            $limite->getLatitud() === $latitud && $limite->getLongitud() === $longitud,
            "Se aceptan las coordenadas válidas ($latitud, $longitud), incluidos los límites y el cero."
        );
    }

    $limitesTexto = new Ubicaciones(
        'Córdoba', 'Río Cuarto', 0, 0, str_repeat('ó', 150)
    );
    ubicacionesComprobar(
        $limitesTexto->getProvincia() === 'Córdoba'
            && $limitesTexto->getLocalidad() === 'Río Cuarto'
            && mb_strlen($limitesTexto->getReferencia(), 'UTF-8') === 150,
        'Se aceptan nombres del catálogo con tildes y una referencia de 150 caracteres.'
    );

    $casosInvalidos = [
        ['provincia' => ''],
        ['provincia' => '   '],
        ['provincia' => str_repeat('á', 101)],
        ['provincia' => 'Provincia inventada'],
        ['provincia' => 'Mendoza'],
        ['localidad' => ''],
        ['localidad' => '   '],
        ['localidad' => str_repeat('ñ', 101)],
        ['localidad' => 'Localidad inventada'],
        ['localidad' => 'Río Cuarto'],
        ['referencia' => str_repeat('ó', 151)],
    ];
    foreach ([-90.000001, 90.000001, NAN, INF, -INF] as $latitud) {
        $casosInvalidos[] = ['latitud' => $latitud];
    }
    foreach ([-180.000001, 180.000001, NAN, INF, -INF] as $longitud) {
        $casosInvalidos[] = ['longitud' => $longitud];
    }

    $datosValidos = [
        'provincia' => 'Buenos Aires',
        'localidad' => 'Avellaneda',
        'latitud' => -34.663561,
        'longitud' => -58.364675,
        'referencia' => 'Portón azul',
    ];
    foreach ($casosInvalidos as $caso) {
        $campo = array_key_first($caso);
        $rechazado = false;
        try {
            new Ubicaciones(...array_replace($datosValidos, $caso));
        } catch (InvalidArgumentException) {
            $rechazado = true;
        }
        ubicacionesComprobar($rechazado, "El constructor rechaza un valor inválido de $campo.");

        $setter = 'set' . ucfirst($campo);
        $getter = 'get' . ucfirst($campo);
        $anterior = $ubicacion->$getter();
        $rechazado = false;
        try {
            $ubicacion->$setter($caso[$campo]);
        } catch (InvalidArgumentException) {
            $rechazado = true;
        }
        ubicacionesComprobar(
            $rechazado && $ubicacion->$getter() === $anterior,
            "El setter de $campo rechaza el valor inválido y conserva el anterior."
        );
    }

    $rechazado = false;
    try {
        new Ubicaciones('Buenos Aires', 'Río Cuarto', 0, 0);
    } catch (InvalidArgumentException $error) {
        $rechazado = $error->getMessage() === 'La localidad no pertenece a la provincia seleccionada.';
    }
    ubicacionesComprobar(
        $rechazado,
        'Una localidad existente en otra provincia se rechaza con el mensaje de pertenencia.'
    );

    $rechazado = false;
    try {
        new Ubicaciones('Provincia inventada', 'Avellaneda', 0, 0);
    } catch (InvalidArgumentException $error) {
        $rechazado = $error->getMessage() === 'La provincia no existe en el catálogo de Argentina.';
    }
    ubicacionesComprobar(
        $rechazado,
        'Una provincia inexistente se rechaza con su propio mensaje.'
    );

    $rechazado = false;
    try {
        $ubicacion->setProvinciaLocalidad('Córdoba', 'Localidad inventada');
    } catch (InvalidArgumentException) {
        $rechazado = true;
    }
    ubicacionesComprobar(
        $rechazado
            && $ubicacion->getProvincia() === 'Buenos Aires'
            && $ubicacion->getLocalidad() === 'Avellaneda',
        'Si falla el cambio conjunto, no se modifica ni la provincia ni la localidad.'
    );

    $ubicacion->setProvinciaLocalidad(' Córdoba ', ' Río Cuarto ');
    $ubicacion->setLatitud(-33.123158);
    $ubicacion->setLongitud(-64.349344);
    $ubicacion->setReferencia(' Portón azul ');
    $ubicacion->setId(53);
    ubicacionesComprobar(
        $ubicacion->getProvincia() === 'Córdoba'
            && $ubicacion->getLocalidad() === 'Río Cuarto'
            && $ubicacion->getLatitud() === -33.123158
            && $ubicacion->getLongitud() === -64.349344
            && $ubicacion->getReferencia() === 'Portón azul'
            && $ubicacion->getId() === 53,
        'Se pueden actualizar los datos válidos y asignar el ID heredado de RegistroDb.'
    );

    $ubicacion->setLocalidad(' Avellaneda ');
    $ubicacion->setProvincia(' Santa Fe ');
    ubicacionesComprobar(
        $ubicacion->getProvincia() === 'Santa Fe' && $ubicacion->getLocalidad() === 'Avellaneda',
        'Los setters individuales aceptan cambios que conservan una pareja válida.'
    );

    $caba = new Ubicaciones('Ciudad Autónoma de Buenos Aires', 'Palermo', 0, 0);
    ubicacionesComprobar(
        $caba->getProvincia() === 'Ciudad Autónoma de Buenos Aires' && $caba->getLocalidad() === 'Palermo',
        'El catálogo también contempla CABA.'
    );

    echo 'Todas las pruebas de Ubicaciones pasaron.' . PHP_EOL;
    echo '<hr>';
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    ubicacionesTest();
}
