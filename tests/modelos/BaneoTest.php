<?php
require_once __DIR__ . '/../../utilidades/TestUtils.php';
require_once __DIR__ . '/../../app/modelos/Baneo.php';

function baneoTest()
{
    echo "INICIANDO TEST: app/modelos/Baneo.php<br>";

    $baneo = new Baneo(3, 1, ' Publicación fraudulenta ', '2026-10-27 20:00:00', '2026-11-03 20:00:00');
    TestUtils::comprobar(
        $baneo->getIdUsuario() === 3
            && $baneo->getIdAdmin() === 1
            && $baneo->getMotivo() === 'Publicación fraudulenta'
            && $baneo->getFechaInicio() === '2026-10-27 20:00:00'
            && $baneo->getFechaFin() === '2026-11-03 20:00:00',
        'Conserva los datos del baneo y quita espacios del motivo.'
    );

    $baneoSinFechas = new Baneo(3, 1, str_repeat('á', 500));
    TestUtils::comprobar(
        $baneoSinFechas->getFechaInicio() === null
            && $baneoSinFechas->getFechaFin() === null
            && mb_strlen($baneoSinFechas->getMotivo(), 'UTF-8') === 500
            && !$baneoSinFechas->tieneId(),
        'Acepta fechas opcionales y un motivo de 500 caracteres, sin ID al crearse.'
    );
    $baneoSinFechas->setId(12);
    TestUtils::comprobar(
        $baneoSinFechas->getId() === 12 && $baneoSinFechas->tieneId(),
        'Hereda el manejo del ID de RegistroDb.'
    );

    foreach ([[0, 1], [-1, 1], [3, 0], [3, -1]] as [$idUsuario, $idAdmin]) {
        $rechazado = false;
        try {
            new Baneo($idUsuario, $idAdmin, 'Publicación fraudulenta');
        } catch (InvalidArgumentException $excepcion) {
            $rechazado = true;
        }
        TestUtils::comprobar($rechazado, "Rechaza IDs inválidos: usuario $idUsuario, administrador $idAdmin.");
    }

    foreach (['', '   ', str_repeat('á', 501)] as $motivo) {
        $rechazado = false;
        try {
            new Baneo(3, 1, $motivo);
        } catch (InvalidArgumentException $excepcion) {
            $rechazado = true;
        }
        TestUtils::comprobar($rechazado, 'Rechaza un motivo vacío o de más de 500 caracteres.');
    }

    echo '<hr>';
}
