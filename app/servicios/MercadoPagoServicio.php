<?php
require_once __DIR__ . '/MercadoPagoCliente.php';
require_once __DIR__ . '/MercadoPagoSeguridad.php';
require_once __DIR__ . '/../repositorios/MercadoPagoRepository.php';
require_once __DIR__ . '/../repositorios/BaneoRepository.php';
final class MercadoPagoServicio
{
    private MercadoPagoSeguridad $seguridad;
    public function __construct(private MercadoPagoRepository $repositorio, private MercadoPagoCliente $cliente, private array $config)
    {
        $this->seguridad = new MercadoPagoSeguridad($config['clave_cifrado']);
    }

    public function verificarConfiguracion(): void
    {
        if (!$this->config['habilitado']) throw new RuntimeException('Mercado Pago todavía no está habilitado en este entorno.');
        foreach (['client_id', 'client_secret', 'url_publica', 'clave_cifrado', 'webhook_secret'] as $campo) {
            if (!is_string($this->config[$campo]) || trim($this->config[$campo]) === '') {
                throw new RuntimeException('Falta completar la configuración de Mercado Pago en el servidor.');
            }
        }
        $url = parse_url($this->config['url_publica']);
        if (!filter_var($this->config['url_publica'], FILTER_VALIDATE_URL) || ($url['scheme'] ?? '') !== 'https'
            || isset($url['query']) || isset($url['fragment']) || isset($url['user'])) {
            throw new RuntimeException('La URL pública de Mercado Pago debe usar HTTPS y no contener parámetros.');
        }
        $this->seguridad->cifrar('comprobar');
    }

    public function url(string $ruta, array $parametros = []): string
    {
        return rtrim($this->config['url_publica'], '/') . '/index.php?' . http_build_query(['ruta' => $ruta] + $parametros);
    }

    public function iniciarOAuth(int $usuario, string $volver): string
    {
        $this->verificarConfiguracion();
        $state = bin2hex(random_bytes(32));
        $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $_SESSION['mp_oauth'] = ['state' => $state, 'verifier' => $verifier, 'usuario' => $usuario, 'creado' => time(),
            'volver' => $volver === 'crear_campana' ? 'crear_campana' : 'mercadopago'];
        return 'https://auth.mercadopago.com.ar/authorization?' . http_build_query([
            'client_id' => $this->config['client_id'], 'response_type' => 'code', 'platform_id' => 'mp',
            'redirect_uri' => $this->url('mp_retorno'), 'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]);
    }

    public function terminarOAuth(int $usuario, array $query): string
    {
        $this->verificarConfiguracion();
        $pendiente = $_SESSION['mp_oauth'] ?? null;
        if (!is_array($pendiente) || !is_string($query['state'] ?? null)
            || !hash_equals($pendiente['state'], $query['state']) || $pendiente['usuario'] !== $usuario
            || time() - $pendiente['creado'] > 600) {
            throw new InvalidArgumentException('La vinculación venció o no corresponde a esta sesión. Iniciala nuevamente.');
        }
        unset($_SESSION['mp_oauth']); // Consumir antes de intercambiar: un solo uso.
        if (isset($query['error'])) throw new InvalidArgumentException('La conexión con Mercado Pago no fue autorizada. Podés intentarlo nuevamente.');
        if (!is_string($query['code'] ?? null) || $query['code'] === '' || strlen($query['code']) > 2048) {
            throw new InvalidArgumentException('No se recibió un código de autorización válido.');
        }
        $datos = $this->cliente->solicitar('POST', '/oauth/token', [
            'client_id' => $this->config['client_id'], 'client_secret' => $this->config['client_secret'],
            'grant_type' => 'authorization_code', 'code' => $query['code'],
            'redirect_uri' => $this->url('mp_retorno'), 'code_verifier' => $pendiente['verifier'],
        ]);
        $this->repositorio->transaccion(function () use ($usuario, $datos): void {
            $cuenta = $this->repositorio->cuenta($usuario, true);
            $this->guardarAutorizacion($usuario, $datos, $cuenta);
        });
        return $pendiente['volver'];
    }

    private function guardarAutorizacion(int $usuario, array $datos, ?array $anterior): void
    {
        foreach (['access_token', 'refresh_token', 'user_id', 'expires_in', 'live_mode'] as $campo) {
            if (!array_key_exists($campo, $datos)) throw new RuntimeException('La autorización de Mercado Pago llegó incompleta.');
        }
        if (!is_string($datos['access_token']) || $datos['access_token'] === '' || preg_match('/[\r\n]/', $datos['access_token'])
            || !is_string($datos['refresh_token']) || $datos['refresh_token'] === ''
            || !ctype_digit((string) $datos['user_id']) || strlen((string) $datos['user_id']) > 32
            || !is_bool($datos['live_mode'])
            || !is_int($datos['expires_in']) || $datos['expires_in'] < 1) {
            throw new RuntimeException('La cuenta o el entorno autorizado no coincide con la configuración.');
        }
        // El prefijo APP_USR y live_mode no identifican por sí solos a un usuario de prueba.
        $identidad = $this->cliente->solicitar('GET', '/users/me', null, $datos['access_token']);
        $esPrueba = is_array($identidad['tags'] ?? null) && in_array('test_user', $identidad['tags'], true);
        $produccion = !$esPrueba;
        if ((string) ($identidad['id'] ?? '') !== (string) $datos['user_id'] || ($identidad['site_id'] ?? null) !== 'MLA'
            || $produccion !== $this->config['produccion']) {
            throw new RuntimeException('La cuenta debe ser de Argentina y corresponder al entorno configurado.');
        }
        if ($anterior !== null && ($anterior['mp_usuario'] !== (string) $datos['user_id'] || (bool) $anterior['produccion'] !== $produccion)) {
            throw new InvalidArgumentException('Este usuario ya tiene vinculada otra cuenta o entorno. No se reemplazó su autorización.');
        }
        $this->repositorio->guardarCuenta($usuario, [
            'mp_usuario' => (string) $datos['user_id'], 'produccion' => $produccion,
            'access_cifrado' => $this->seguridad->cifrar($datos['access_token']),
            'refresh_cifrado' => $this->seguridad->cifrar($datos['refresh_token']),
            'vence_en' => time() + min($datos['expires_in'], 31536000),
        ]);
    }

    public function token(int $usuario, ?string $receptor = null): string
    {
        // Bloquear la fila evita dos renovaciones concurrentes con el mismo refresh token.
        return $this->repositorio->transaccion(function () use ($usuario, $receptor): string {
            $cuenta = $this->repositorio->cuenta($usuario, true);
            if ($cuenta === null || (bool) $cuenta['produccion'] !== $this->config['produccion']
                || ($receptor !== null && $cuenta['mp_usuario'] !== $receptor)) {
                throw new RuntimeException('El creador necesita conectar su cuenta de Mercado Pago.');
            }
            if ((int) $cuenta['vence_en'] <= time() + 300) {
                $datos = $this->cliente->solicitar('POST', '/oauth/token', [
                    'client_id' => $this->config['client_id'], 'client_secret' => $this->config['client_secret'],
                    'grant_type' => 'refresh_token', 'refresh_token' => $this->seguridad->descifrar($cuenta['refresh_cifrado']),
                ]);
                $this->guardarAutorizacion($usuario, $datos, $cuenta);
                return $datos['access_token'];
            }
            return $this->seguridad->descifrar($cuenta['access_cifrado']);
        });
    }

    public function crearPago(int $usuario, string $mail, int $campanaId, string $monto, string $referencia): string
    {
        $this->verificarConfiguracion();
        $centavos = MercadoPagoSeguridad::centavos($monto);
        if ($centavos <= 0) throw new InvalidArgumentException('El monto debe ser mayor a cero.');
        $monto = MercadoPagoSeguridad::pesos($centavos);
        if (!preg_match('/^[a-f0-9-]{36}$/D', $referencia)) throw new InvalidArgumentException('Intento de pago inválido.');
        $campana = $this->repositorio->campana($campanaId);
        if ($campana === null || !$campana['monetaria'] || $campana['estado'] !== 'activa') {
            throw new InvalidArgumentException('Esta campaña no puede recibir aportes de dinero.');
        }
        $cuenta = $this->repositorio->cuenta((int) $campana['owner_id']);
        if ($cuenta === null || (bool) $cuenta['produccion'] !== $this->config['produccion']) {
            throw new RuntimeException('El creador todavía no conectó su cuenta para recibir donaciones.');
        }
        $this->repositorio->crearIntento($referencia, $usuario, $campana, $cuenta, $monto);
        // El intento se persiste ANTES de llamar a la API. Un error/timeout conserva la clave de reintento.
        // Confirmar la renovación en su propia transacción antes de intentar el cobro.
        $token = $this->token((int) $campana['owner_id'], $cuenta['mp_usuario']);
        return $this->repositorio->transaccion(function () use ($referencia, $mail, $campana, $token): string {
            $pago = $this->repositorio->pago($referencia, true);
            if ($pago['order_id'] !== null) return $pago['checkout_url'];
            $retorno = $this->url('pago_resultado', ['referencia' => $referencia]);
            $order = $this->cliente->solicitar('POST', '/v1/orders', [
                'type' => 'online', 'processing_mode' => 'manual', 'total_amount' => $pago['monto'],
                'external_reference' => $referencia, 'payer' => ['email' => $mail],
                'items' => [['title' => 'Aporte a ' . $campana['nombre'], 'unit_price' => $pago['monto'], 'quantity' => 1]],
                'config' => ['online' => ['success_url' => $retorno, 'failure_url' => $retorno, 'pending_url' => $retorno, 'auto_return' => 'all']],
            ], $token, $referencia);
            $id = $order['id'] ?? null;
            $url = $order['checkout_url'] ?? null;
            if (!is_string($id) || !preg_match('/^ORD[A-Z0-9]{1,60}$/D', $id) || !self::checkoutValido($url)) {
                throw new RuntimeException('No se recibió un enlace de pago válido. Reintentá este mismo aporte.');
            }
            $this->verificarOrder($pago, $order, $id);
            $this->repositorio->guardarOrder($referencia, $id, $url);
            return $url;
        });
    }

    public static function checkoutValido(mixed $url): bool
    {
        if (!is_string($url) || preg_match('/[\r\n]/', $url)) return false;
        $partes = parse_url($url);
        return ($partes['scheme'] ?? '') === 'https' && ($partes['host'] ?? '') === 'www.mercadopago.com.ar'
            && !isset($partes['user']) && !isset($partes['port']) && str_starts_with($partes['path'] ?? '', '/checkout/');
    }

    private function verificarOrder(array $pago, array $order, string $id): void
    {
        if (($order['id'] ?? null) !== $id || ($order['external_reference'] ?? null) !== $pago['referencia']
            || (string) ($order['user_id'] ?? '') !== $pago['mp_receptor'] || ($order['currency'] ?? null) !== 'ARS'
            || ($order['type'] ?? null) !== 'online' || MercadoPagoSeguridad::centavos($order['total_amount'] ?? null) !== MercadoPagoSeguridad::centavos($pago['monto'])
            || (isset($order['integration_data']['application_id']) && (string) $order['integration_data']['application_id'] !== $this->config['client_id'])
            || (bool) $pago['produccion'] !== $this->config['produccion']) {
            throw new RuntimeException('La operación no coincide con el aporte registrado. No se acreditó.');
        }
    }

    public function sincronizar(string $referencia): void
    {
        $this->verificarConfiguracion();
        $actual = $this->repositorio->pago($referencia);
        if ($actual === null || $actual['order_id'] === null) return;
        $token = $this->token((int) $actual['id_creador'], $actual['mp_receptor']);
        $this->repositorio->transaccion(function () use ($referencia, $token): void {
            $pago = $this->repositorio->pago($referencia, true);
            if ($pago === null || $pago['order_id'] === null) return;
            // Consultar DESPUÉS de bloquear evita aplicar dos snapshots en orden inverso.
            $order = $this->cliente->solicitar('GET', '/v1/orders/' . $pago['order_id'], null,
                $token);
            $this->verificarOrder($pago, $order, $pago['order_id']);
            $version = isset($order['version']) && is_int($order['version']) ? $order['version'] : 0;
            if ($version > 0 && $version < (int) $pago['version_mp']) return;
            $estado = $order['status'] ?? '';
            $detalle = $order['status_detail'] ?? '';
            if (!is_string($estado) || !is_string($detalle) || strlen($estado) > 40 || strlen($detalle) > 80) {
                throw new RuntimeException('Estado de pago inválido.');
            }
            $anterior = MercadoPagoSeguridad::centavos($pago['acreditado']);
            $acreditado = $anterior;
            if ($estado === 'processed' && in_array($detalle, ['accredited', 'partially_refunded'], true)) {
                $total = MercadoPagoSeguridad::centavos($order['total_paid_amount'] ?? null);
                if ($total !== MercadoPagoSeguridad::centavos($pago['monto'])) throw new RuntimeException('El importe pagado no coincide.');
                $reembolsado = 0;
                if ($detalle === 'partially_refunded') {
                    $devoluciones = $order['transactions']['refunds'] ?? [];
                    if (!is_array($devoluciones) || $devoluciones === []) throw new RuntimeException('No se pudo verificar el reembolso parcial.');
                    foreach ($devoluciones as $devolucion) $reembolsado += MercadoPagoSeguridad::centavos($devolucion['amount'] ?? null);
                    if ($reembolsado <= 0 || $reembolsado >= $total) throw new RuntimeException('Reembolso parcial inválido.');
                }
                $acreditado = $total - $reembolsado;
                // Un snapshot antiguo no puede restaurar importes reembolsados.
                if (in_array($pago['estado'], ['refunded', 'charged_back'], true)
                    || ($pago['detalle'] === 'partially_refunded' && $acreditado > $anterior)) return;
            } elseif (in_array($estado, ['refunded', 'charged_back'], true)) {
                $acreditado = 0;
            } elseif ($anterior > 0 || in_array($pago['estado'], ['refunded', 'charged_back'], true)) {
                return; // No degradar un pago acreditado a un estado previo.
            }
            $this->repositorio->acreditar($pago, $acreditado, $estado, $detalle, max($version, (int) $pago['version_mp']));
        });
    }
}
