<?php
require_once __DIR__ . '/MercadoPagoErrorApi.php';
/** API REST de Checkout Pro y OAuth. Las pruebas sustituyen el transporte. */
class MercadoPagoCliente
{
    public function __construct(private ?Closure $transporte = null) {}

    public function solicitar(string $metodo, string $ruta, ?array $datos = null, ?string $token = null, ?string $idempotencia = null): array
    {
        if (!preg_match('~^/(oauth/token|users/me|checkout/preferences|v1/payments/(?:[0-9]+|search\?external_reference=[a-f0-9-]{36}&limit=100))$~D', $ruta)) {
            throw new InvalidArgumentException('Recurso de Mercado Pago no permitido.');
        }
        if ($this->transporte !== null) {
            return ($this->transporte)($metodo, $ruta, $datos, $token, $idempotencia);
        }
        $oauth = $ruta === '/oauth/token';
        $cabeceras = ['Accept: application/json', 'Content-Type: ' . ($oauth ? 'application/x-www-form-urlencoded' : 'application/json')];
        if ($token !== null) $cabeceras[] = 'Authorization: Bearer ' . $token;
        if ($idempotencia !== null) $cabeceras[] = 'X-Idempotency-Key: ' . $idempotencia;
        $ch = curl_init('https://api.mercadopago.com' . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $cabeceras, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        if ($datos !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $oauth
            ? http_build_query(array_map(static fn ($valor) => is_bool($valor) ? ($valor ? 'true' : 'false') : $valor, $datos), '', '&', PHP_QUERY_RFC3986)
            : json_encode($datos, JSON_THROW_ON_ERROR));
        $cuerpo = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // No incluir respuestas, URLs con code ni tokens en mensajes de error o registros.
        if ($cuerpo === false) {
            throw new RuntimeException('Mercado Pago no pudo completar la operación. Intentá nuevamente.');
        }
        if ($codigo < 200 || $codigo >= 300) {
            $error = json_decode($cuerpo, true);
            throw new MercadoPagoErrorApi($codigo, is_array($error) ? ($error['error'] ?? null) : null);
        }
        try {
            $respuesta = json_decode($cuerpo, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Mercado Pago devolvió una respuesta no válida.');
        }
        if (!is_array($respuesta)) throw new RuntimeException('Respuesta de Mercado Pago no válida.');
        return $respuesta;
    }
}
