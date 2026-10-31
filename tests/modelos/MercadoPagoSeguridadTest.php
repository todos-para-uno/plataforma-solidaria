<?php
require_once __DIR__ . '/../../app/servicios/MercadoPagoSeguridad.php';
require_once __DIR__ . '/../../app/servicios/MercadoPagoServicio.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
function mercadoPagoSeguridadTest(): void
{
    $cifrado = new MercadoPagoSeguridad(base64_encode(random_bytes(32)));
    $valor = 'token-ficticio-no-utilizable';
    $a = $cifrado->cifrar($valor);
    $b = $cifrado->cifrar($valor);
    TestUtils::comprobar($a !== $b && !str_contains($a, $valor) && $cifrado->descifrar($a) === $valor, 'Cifra tokens con nonces distintos y recupera su valor.');
    $alterado = base64_decode($a);
    $alterado[30] = chr(ord($alterado[30]) ^ 1);
    try { $cifrado->descifrar(base64_encode($alterado)); throw new LogicException('Debía rechazar manipulación.'); }
    catch (RuntimeException $e) { TestUtils::comprobar(true, 'Rechaza tokens cifrados manipulados.'); }
    try { (new MercadoPagoSeguridad(base64_encode(random_bytes(32))))->descifrar($a); throw new LogicException('Debía rechazar otra clave.'); }
    catch (RuntimeException $e) { TestUtils::comprobar(true, 'Rechaza una clave de cifrado diferente.'); }
    foreach (['1' => 100, '1.2' => 120, '1000.01' => 100001, '0.01' => 1] as $entrada => $esperado) {
        TestUtils::comprobar(MercadoPagoSeguridad::centavos((string) $entrada) === $esperado, 'Valida el importe sin redondeos binarios.');
    }
    foreach (['-1', '1e3', 'NaN', '1,20', '1.001', [], 1.2, '10000000000', ' 1'] as $entrada) {
        try { MercadoPagoSeguridad::centavos($entrada); throw new LogicException('Debía rechazar el importe.'); }
        catch (InvalidArgumentException $e) { TestUtils::comprobar(true, 'Rechaza importes ambiguos o fuera del esquema.'); }
    }
    $id = 'ORDTSTPRUEBA1'; $request = 'request-123'; $ts = '1742505638683'; $secret = 'secreto-ficticio';
    $firma = 'ts=' . $ts . ',v1=' . hash_hmac('sha256', 'id:' . strtolower($id) . ';request-id:' . $request . ';ts:' . $ts . ';', $secret);
    TestUtils::comprobar(MercadoPagoSeguridad::firmaValida($id, $firma, $request, $secret), 'Valida HMAC usando el ID normalizado y request-id.');
    TestUtils::comprobar(!MercadoPagoSeguridad::firmaValida($id . '2', $firma, $request, $secret)
        && !MercadoPagoSeguridad::firmaValida($id, $firma, 'otro', $secret)
        && !MercadoPagoSeguridad::firmaValida($id, $firma, $request, '')
        && !MercadoPagoSeguridad::firmaValida($id, $firma . ',ts=1', $request, $secret), 'Rechaza firmas alteradas, campos duplicados o secretos ausentes.');
    foreach (['https://www.mercadopago.com.ar.ejemplo.test/checkout/', 'http://www.mercadopago.com.ar/checkout/', "https://www.mercadopago.com.ar/checkout/\r\nX:mal", 'https://otro.test/checkout/'] as $url) {
        TestUtils::comprobar(!MercadoPagoServicio::checkoutValido($url), 'No redirige a un checkout ajeno o inseguro.');
    }
}
