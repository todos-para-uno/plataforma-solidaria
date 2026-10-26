<?php
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';
require_once __DIR__ . '/../../app/modelos/Camp.php';

function campComprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException('FALLÓ: ' . $mensaje);
    }
    echo 'OK: ' . $mensaje . PHP_EOL;
}

function campTest(): void
{   
    eol();
    echo 'INICIANDO TEST: app/modelos/Camp.php' . PHP_EOL;
    $cbu = '00' . str_repeat('53', 10);

    $monetaria = new Camp(53, 'Campaña de Lola', true, 53, null, $cbu);
    campComprobar(
        $monetaria->getCbu() === $cbu
            && $monetaria->getIdUbicacion() === null
            && $monetaria->getEstado()->value === 'activa'
            && $monetaria->getLogrado() === 0.0,
        'La campaña monetaria se crea sin ubicación, activa y con logrado en cero.'
    );

    $insumos = new Camp(53, 'Campaña de Pepe', false, 53, null, null, 53);
    campComprobar(
        $insumos->getCbu() === null && $insumos->getIdUbicacion() === 53,
        'La campaña de insumos se crea con ubicación y sin CBU.'
    );

    $rechazado = false;
    try {
        new Camp(53, 'Campaña de Lola', true, 53);
    } catch (InvalidArgumentException) {
        $rechazado = true;
    }
    campComprobar($rechazado, 'Una campaña monetaria exige CBU.');

    $rechazado = false;
    try {
        new Camp(53, 'Campaña de Pepe', false, 53);
    } catch (InvalidArgumentException) {
        $rechazado = true;
    }
    campComprobar($rechazado, 'Una campaña de insumos exige ubicación.');

    $rechazado = false;
    try {
        $monetaria->setCbu('123');
    } catch (InvalidArgumentException) {
        $rechazado = true;
    }
    campComprobar(
        $rechazado && $monetaria->getCbu() === $cbu,
        'Un CBU inválido se rechaza y conserva el anterior, incluidos los ceros iniciales.'
    );

    $rechazado = false;
    try {
        $insumos->setNombre('   ');
    } catch (InvalidArgumentException) {
        $rechazado = true;
    }
    campComprobar(
        $rechazado && $insumos->getNombre() === 'Campaña de Pepe',
        'Un nombre vacío se rechaza y conserva el anterior.'
    );

    $filaCamp = [
        'id' => '12',
        'owner_id' => '53',
        'nombre' => 'Campaña guardada',
        'monetaria' => '1',
        'categoria' => '2',
        'logrado' => '1250.75',
        'descripcion' => 'Descripción guardada',
        'cbu' => $cbu,
        'estado' => 'finalizada',
        'id_ubicacion' => null,
    ];
    $guardada = Camp::desdePersistencia($filaCamp);
    campComprobar(
        $guardada->getId() === 12
            && $guardada->getOwnerId() === 53
            && $guardada->getNombre() === $filaCamp['nombre']
            && $guardada->getMonetaria()
            && $guardada->getCategoria() === 2
            && $guardada->getLogrado() === 1250.75
            && $guardada->getDescripcion() === $filaCamp['descripcion']
            && $guardada->getCbu() === $cbu
            && $guardada->getEstado() === EstadoCamp::Finalizada
            && $guardada->getIdUbicacion() === null,
        'La reconstrucción conserva los datos, el avance y el estado finalizado.'
    );

    $filaCamp['monetaria'] = '0';
    $filaCamp['cbu'] = null;
    $filaCamp['id_ubicacion'] = '7';
    $filaCamp['descripcion'] = null;
    $filaCamp['estado'] = 'suspendida';
    $guardada = Camp::desdePersistencia($filaCamp);
    campComprobar(
        !$guardada->getMonetaria()
            && $guardada->getCbu() === null
            && $guardada->getIdUbicacion() === 7
            && $guardada->getDescripcion() === null
            && $guardada->getEstado() === EstadoCamp::Suspendida,
        'La reconstrucción de insumos conserva la ubicación y el estado suspendido.'
    );

    echo 'Todas las pruebas de Camp pasaron.' . PHP_EOL;
    eol();
    echo '<hr>';
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    campTest();
}
