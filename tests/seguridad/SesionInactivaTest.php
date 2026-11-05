<?php
require_once __DIR__ . '/../../app/seguridad/SesionInactiva.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function sesionInactivaTest(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $original = $_SESSION;
    try {
        $_SESSION = ['csrf_token' => 'visitante'];
        TestUtils::comprobar(!SesionInactiva::comprobar(1000) && $_SESSION === ['csrf_token' => 'visitante'],
            'El control no vence formularios de visitantes ni crea identidad.');
        $_SESSION = ['usuario_id' => 1, 'ultima_actividad' => 1000];
        $token = Csrf::generar();
        TestUtils::comprobar(!SesionInactiva::comprobar(1599) && $_SESSION['ultima_actividad'] === 1599
            && Csrf::validar($token), 'Una petición a los 599 segundos conserva la identidad y renueva la actividad.');
        TestUtils::comprobar(!SesionInactiva::comprobar(2198) && $_SESSION['ultima_actividad'] === 2198,
            'La navegación antes del límite permite continuar, sin imponer un límite absoluto de diez minutos.');
        $_SESSION['ultima_actividad'] = 1000;
        $_SESSION['crear_campana_claves'] = ['anterior' => true];
        $idAnterior = session_id();
        TestUtils::comprobar(SesionInactiva::comprobar(1600) && !isset($_SESSION['usuario_id'])
            && !isset($_SESSION['crear_campana_claves']) && !isset($_SESSION['ultima_actividad'])
            && $_SESSION['sesion_vencida'] === true && session_id() !== $idAnterior && !Csrf::validar($token),
            'Al cumplir exactamente 600 segundos revoca identidad, claves y CSRF, y cambia el ID de sesión.');
        foreach ([1000, '1000', 2001, []] as $ultima) {
            $_SESSION = ['usuario_id' => 1, 'ultima_actividad' => $ultima];
            TestUtils::comprobar(SesionInactiva::comprobar(2000) && !isset($_SESSION['usuario_id']),
                'Un tiempo vencido, inválido o futuro no mantiene una sesión autenticada.');
        }
        $_SESSION = ['usuario_id' => 1];
        TestUtils::comprobar(!SesionInactiva::comprobar(1000) && $_SESSION['ultima_actividad'] === 1000,
            'Una sesión previa al cambio obtiene una ventana inicial y luego queda sujeta al mismo límite.');
        TestUtils::comprobar(SesionInactiva::comprobar(1600), 'La sesión previa también vence al cumplir diez minutos.');
    } finally { $_SESSION = $original; }
}
