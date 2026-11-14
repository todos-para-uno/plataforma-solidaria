<?php
final class MercadoPagoSeguridad
{
    public function __construct(private string $claveBase64) {}

    private function clave(): string
    {
        $clave = base64_decode($this->claveBase64, true);
        if ($clave === false || strlen($clave) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('Falta configurar la clave de cifrado de Mercado Pago.');
        }
        return $clave;
    }

    public function cifrar(string $texto): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce . sodium_crypto_secretbox($texto, $nonce, $this->clave()));
    }

    public function descifrar(string $texto): string
    {
        $datos = base64_decode($texto, true);
        if ($datos === false || strlen($datos) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            throw new RuntimeException('No se pudo recuperar la autorización de Mercado Pago.');
        }
        $valor = sodium_crypto_secretbox_open(substr($datos, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($datos, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $this->clave());
        if ($valor === false) throw new RuntimeException('No se pudo recuperar la autorización de Mercado Pago.');
        return $valor;
    }

    public static function uuid(): string
    {
        $datos = random_bytes(16);
        $datos[6] = chr((ord($datos[6]) & 0x0f) | 0x40);
        $datos[8] = chr((ord($datos[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($datos), 4));
    }

    /** Dinero en centavos: no redondear floats ni aceptar notación científica. */
    public static function centavos(mixed $valor): int
    {
        if (!is_string($valor) || !preg_match('/^(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/D', $valor, $m)) {
            throw new InvalidArgumentException('Ingresá un monto válido, con hasta dos decimales separados por punto.');
        }
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    public static function pesos(int $centavos): string
    {
        return intdiv($centavos, 100) . '.' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function firmaValida(mixed $id, mixed $firma, mixed $requestId, string $secreto): bool
    {
        if ($secreto === '' || !is_string($id) || !preg_match('/^[A-Za-z0-9]+$/D', $id)
            || !is_string($firma) || !is_string($requestId) || !preg_match('/^[A-Za-z0-9-]{1,100}$/D', $requestId)) return false;
        $partes = [];
        foreach (explode(',', $firma) as $parte) {
            $par = explode('=', trim($parte), 2);
            if (count($par) !== 2 || isset($partes[$par[0]])) return false;
            $partes[$par[0]] = $par[1];
        }
        if (!ctype_digit($partes['ts'] ?? '') || !preg_match('/^[a-f0-9]{64}$/D', $partes['v1'] ?? '')) return false;
        $manifiesto = 'id:' . strtolower($id) . ';request-id:' . $requestId . ';ts:' . $partes['ts'] . ';';
        return hash_equals(hash_hmac('sha256', $manifiesto, $secreto), $partes['v1']);
    }
}
