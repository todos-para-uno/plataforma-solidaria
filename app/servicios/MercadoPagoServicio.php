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
            || isset($url['query']) || isset($url['fragment']) || isset($url['user'])
            || in_array(strtolower($url['host'] ?? ''), ['localhost', '127.0.0.1', '0.0.0.0', '[::1]'], true)) {
            throw new RuntimeException('La URL pública de Mercado Pago debe usar HTTPS y no contener parámetros.');
        }
        $this->seguridad->cifrar('comprobar');
    }

    public function url(string $ruta, array $parametros = []): string
    {
        if ($ruta === 'mp_retorno') {
            return rtrim($this->config['url_publica'], '/') . '/mercadopago-retorno.php';
        }
        return rtrim($this->config['url_publica'], '/') . '/index.php?' . http_build_query(['ruta' => $ruta] + $parametros);
    }

    public function iniciarOAuth(int $usuario, string $volver): string
    {
        $this->verificarConfiguracion();
        $state = bin2hex(random_bytes(32));
        $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $this->repositorio->guardarEstado($state, $usuario, $this->seguridad->cifrar($verifier),
            $volver === 'crear_campana' ? 'crear_campana' : 'mercadopago');
        $_SESSION['mp_oauth'] = ['state' => $state, 'verifier' => $verifier, 'usuario' => $usuario, 'creado' => time(),
            'volver' => $volver === 'crear_campana' ? 'crear_campana' : 'mercadopago'];
        return 'https://auth.mercadopago.com/authorization?' . http_build_query([
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
        $estado = $this->repositorio->consumirEstado($query['state'], $usuario);
        unset($_SESSION['mp_oauth']); // Consumir antes de intercambiar: un solo uso.
        if (isset($query['error'])) throw new InvalidArgumentException('La conexión con Mercado Pago no fue autorizada. Podés intentarlo nuevamente.');
        if (!is_string($query['code'] ?? null) || $query['code'] === '' || strlen($query['code']) > 2048) {
            throw new InvalidArgumentException('No se recibió un código de autorización válido.');
        }
        $datos = $this->cliente->solicitar('POST', '/oauth/token', [
            'client_id' => $this->config['client_id'], 'client_secret' => $this->config['client_secret'],
            'grant_type' => 'authorization_code', 'code' => $query['code'],
            'redirect_uri' => $this->url('mp_retorno'), 'code_verifier' => $this->seguridad->descifrar($estado['verifier_cifrado']),
            'test_token' => !$this->config['produccion'],
        ]);
        $this->repositorio->transaccion(function () use ($usuario, $datos): void {
            $cuenta = $this->repositorio->cuenta($usuario, true);
            $this->guardarAutorizacion($usuario, $datos, $cuenta);
        });
        return $pendiente['volver'];
    }

    private function guardarAutorizacion(int $usuario, array $datos, ?array $anterior, bool $renovacion = false): void
    {
        foreach (['access_token', 'refresh_token', 'user_id', 'expires_in', 'live_mode'] as $campo) {
            if (!array_key_exists($campo, $datos)) throw new RuntimeException('La autorización de Mercado Pago llegó incompleta.');
        }
        $scopes = is_string($datos['scope'] ?? null) ? preg_split('/\s+/', trim($datos['scope'])) : [];
        // Split 1:1 documenta "offline_access payments write"; otras respuestas usan read.
        $lecturaPermitida = in_array('read', $scopes, true) || in_array('payments', $scopes, true);
        if (!is_string($datos['token_type'] ?? null) || strtolower($datos['token_type']) !== 'bearer'
            || !$lecturaPermitida || array_diff(['write', 'offline_access'], $scopes) !== []) {
            throw new InvalidArgumentException('La autorización necesita un token Bearer con permisos de lectura, escritura y renovación. Volvé a conectar Mercado Pago.');
        }
        if (!is_string($datos['access_token']) || $datos['access_token'] === '' || preg_match('/[\r\n]/', $datos['access_token'])
            || !is_string($datos['refresh_token']) || $datos['refresh_token'] === ''
            || !ctype_digit((string) $datos['user_id']) || strlen((string) $datos['user_id']) > 32
            || !is_bool($datos['live_mode'])
            || !is_int($datos['expires_in']) || $datos['expires_in'] < 1) {
            throw new RuntimeException('La cuenta o el entorno autorizado no coincide con la configuración.');
        }
        // El prefijo APP_USR y live_mode no identifican por sí solos a un usuario de prueba.
        if ($renovacion) {
            // La identidad ya se comprobó al vincular. No agregar llamadas de red entre
            // la rotación remota y el guardado de ambos tokens.
            $produccion = (bool) $anterior['produccion'];
        } else {
            $identidad = $this->cliente->solicitar('GET', '/users/me', null, $datos['access_token']);
            $esPrueba = is_array($identidad['tags'] ?? null) && in_array('test_user', $identidad['tags'], true);
            $produccion = !$esPrueba;
            if ((string) ($identidad['id'] ?? '') !== (string) $datos['user_id'] || ($identidad['site_id'] ?? null) !== 'MLA'
                || $produccion !== $this->config['produccion']) {
                throw new RuntimeException('La cuenta debe ser de Argentina y corresponder al entorno configurado.');
            }
        }
        if ($anterior !== null && ($anterior['mp_usuario'] !== (string) $datos['user_id'] || (bool) $anterior['produccion'] !== $produccion)) {
            throw new InvalidArgumentException('Este usuario ya tiene vinculada otra cuenta o entorno. No se reemplazó su autorización.');
        }
        $this->repositorio->guardarCuenta($usuario, [
            'mp_usuario' => (string) $datos['user_id'], 'produccion' => $produccion,
            'access_cifrado' => $this->seguridad->cifrar($datos['access_token']),
            'refresh_cifrado' => $this->seguridad->cifrar($datos['refresh_token']),
            'vence_en' => time() + min($datos['expires_in'], 31536000),
            'scope' => implode(' ', $scopes),
        ]);
    }

    public function token(int $usuario, ?string $receptor = null): string
    {
        // Bloquear la fila evita dos renovaciones concurrentes con el mismo refresh token.
        $token = $this->repositorio->transaccion(function () use ($usuario, $receptor): ?string {
            $cuenta = $this->repositorio->cuenta($usuario, true);
            if ($cuenta === null || (bool) $cuenta['produccion'] !== $this->config['produccion']
                || ($receptor !== null && $cuenta['mp_usuario'] !== $receptor)) {
                throw new RuntimeException('El creador necesita conectar su cuenta de Mercado Pago.');
            }
            if ((bool) $cuenta['requiere_reautorizar']) return null;
            if ((int) $cuenta['vence_en'] <= time() + 300) {
                try {
                    $datos = $this->cliente->solicitar('POST', '/oauth/token', [
                        'client_id' => $this->config['client_id'], 'client_secret' => $this->config['client_secret'],
                        'grant_type' => 'refresh_token', 'refresh_token' => $this->seguridad->descifrar($cuenta['refresh_cifrado']),
                    ]);
                    $this->guardarAutorizacion($usuario, $datos, $cuenta, true);
                } catch (MercadoPagoErrorApi $e) {
                    if (!$e->requiereReautorizar(true)) throw $e;
                    $this->repositorio->marcarReautorizacion($usuario);
                    return null;
                } catch (InvalidArgumentException $e) {
                    $this->repositorio->marcarReautorizacion($usuario);
                    return null;
                }
                return $datos['access_token'];
            }
            return $this->seguridad->descifrar($cuenta['access_cifrado']);
        });
        // Lanzar después del commit: el estado de reconexión no debe revertirse.
        if ($token === null) throw new RuntimeException('El creador necesita volver a conectar su cuenta de Mercado Pago para recibir o verificar aportes.');
        return $token;
    }

    private function conAutorizacion(int $usuario, string $token, Closure $operacion): mixed
    {
        try {
            return $operacion();
        } catch (MercadoPagoErrorApi $e) {
            if (!$e->requiereReautorizar(false)) throw $e;
            // La transacción del pago ya terminó. No invalidar una autorización
            // nueva si otra solicitud la renovó mientras esta usaba el token anterior.
            $this->repositorio->transaccion(function () use ($usuario, $token): void {
                $cuenta = $this->repositorio->cuenta($usuario, true);
                if ($cuenta !== null && hash_equals($this->seguridad->descifrar($cuenta['access_cifrado']), $token)) {
                    $this->repositorio->marcarReautorizacion($usuario);
                }
            });
            throw new RuntimeException('Mercado Pago rechazó la autorización. El creador debe revisar la conexión de su cuenta.');
        }
    }

    public function crearPago(?int $usuario, string $mail, int $campanaId, string $monto, string $referencia, ?string $invitadoHash = null): string
    {
        $this->verificarConfiguracion();
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL) || strlen($mail) > 254 || preg_match('/[\r\n]/', $mail)) {
            throw new InvalidArgumentException('Ingresá un correo electrónico válido para continuar con el pago.');
        }
        if ($usuario === null && (!is_string($invitadoHash) || !preg_match('/^[a-f0-9]{64}$/D', $invitadoHash))) {
            throw new InvalidArgumentException('No se pudo identificar la sesión del aporte. Recargá el formulario.');
        }
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
        $this->repositorio->crearIntento($referencia, $usuario, $campana, $cuenta, $monto, $mail, $invitadoHash);
        // El intento se persiste ANTES de llamar a la API. Un error/timeout conserva la clave de reintento.
        // Confirmar la renovación en su propia transacción antes de intentar el cobro.
        $token = $this->token((int) $campana['owner_id'], $cuenta['mp_usuario']);
        return $this->conAutorizacion((int) $campana['owner_id'], $token, fn () => $this->repositorio->transaccion(function () use ($referencia, $mail, $campana, $token): string {
            $pago = $this->repositorio->pago($referencia, true);
            if ($pago['preference_id'] !== null) return $pago['checkout_url'];
            if ($pago['order_id'] !== null) throw new RuntimeException('Este aporte pertenece a la integración anterior. Consultá su estado antes de iniciar otro.');
            $retorno = $this->url('pago_resultado', ['referencia' => $referencia]);
            $preferencia = $this->cliente->solicitar('POST', '/checkout/preferences', [
                'items' => [['id' => (string) $campana['id'], 'title' => 'Donación a ' . $campana['nombre'],
                    'description' => 'Aporte solidario a la campaña ' . $campana['nombre'],
                    'unit_price' => (float) $pago['monto'], 'quantity' => 1, 'currency_id' => 'ARS']],
                'payer' => ['email' => $pago['correo_donante']],
                'external_reference' => $referencia, 'marketplace_fee' => 0,
                'statement_descriptor' => 'TODOSPARAUNO',
                'back_urls' => ['success' => $retorno, 'failure' => $retorno, 'pending' => $retorno],
                'auto_return' => 'approved',
                'notification_url' => $this->url('mp_webhook'),
            ], $token, $referencia);
            $id = $preferencia['id'] ?? null;
            $url = $preferencia['init_point'] ?? null;
            if (!is_string($id) || !preg_match('/^[A-Za-z0-9-]{1,100}$/D', $id) || !self::checkoutValido($url)
                || ($preferencia['external_reference'] ?? null) !== $referencia
                || (string) ($preferencia['collector_id'] ?? '') !== $pago['mp_receptor']) {
                throw new RuntimeException('No se recibió un enlace de pago válido. Reintentá este mismo aporte.');
            }
            $this->repositorio->guardarPreferencia($referencia, $id, $url);
            return $url;
        }));
    }

    public static function checkoutValido(mixed $url): bool
    {
        if (!is_string($url) || preg_match('/[\r\n]/', $url)) return false;
        $partes = parse_url($url);
        return ($partes['scheme'] ?? '') === 'https' && ($partes['host'] ?? '') === 'www.mercadopago.com.ar'
            && !isset($partes['user']) && !isset($partes['port']) && str_starts_with($partes['path'] ?? '', '/checkout/');
    }

    /** La API puede devolver dinero como número JSON; normalizar sin redondear entradas de usuario. */
    private static function centavosApi(mixed $valor): int
    {
        if (is_int($valor) || is_float($valor)) $valor = (string) $valor;
        return MercadoPagoSeguridad::centavos($valor);
    }

    public function sincronizar(string $referencia, ?string $paymentId = null): void
    {
        $this->verificarConfiguracion();
        $actual = $this->repositorio->pago($referencia);
        if ($actual === null || $actual['preference_id'] === null) return;
        $token = $this->token((int) $actual['id_creador'], $actual['mp_receptor']);
        $this->conAutorizacion((int) $actual['id_creador'], $token, function () use ($referencia, $paymentId, $token): void {
            if ($paymentId !== null) {
                if (!preg_match('/^[0-9]{1,32}$/D', $paymentId)) throw new InvalidArgumentException('Identificador de pago inválido.');
                $ids = [$paymentId];
            } else {
                // El retorno y la consulta manual buscan por nuestra referencia, nunca por status del navegador.
                $busqueda = $this->cliente->solicitar('GET', '/v1/payments/search?external_reference=' . $referencia . '&limit=100', null, $token);
                if (!is_array($busqueda['results'] ?? null) || ($busqueda['paging']['total'] ?? 0) > 100) {
                    throw new RuntimeException('No se pudo consultar la lista completa de pagos. Reintentá la consulta.');
                }
                $ids = array_map(static fn ($p) => (string) ($p['id'] ?? ''), $busqueda['results']);
            }
            foreach (array_unique($ids) as $id) {
                if (!preg_match('/^[0-9]{1,32}$/D', $id)) throw new RuntimeException('Identificador de pago inválido.');
                $this->repositorio->transaccion(function () use ($referencia, $id, $token): void {
                    $pago = $this->repositorio->pago($referencia, true);
                    // El bloqueo serializa consultas y notificaciones del mismo aporte.
                    $remoto = $this->cliente->solicitar('GET', '/v1/payments/' . $id, null, $token);
                    $this->aplicarPago($pago, $remoto, $id);
                });
            }
        });
    }

    private function aplicarPago(array $pago, array $remoto, string $id): void
    {
        if ((string) ($remoto['id'] ?? '') !== $id || ($remoto['external_reference'] ?? null) !== $pago['referencia']
            || (string) ($remoto['collector_id'] ?? '') !== $pago['mp_receptor'] || ($remoto['currency_id'] ?? null) !== 'ARS'
            || self::centavosApi($remoto['transaction_amount'] ?? null) !== MercadoPagoSeguridad::centavos($pago['monto'])
            || (bool) $pago['produccion'] !== $this->config['produccion']) {
            throw new RuntimeException('El pago no coincide con el aporte registrado. No se acreditó.');
        }
        $fecha = $remoto['date_last_updated'] ?? null;
        $version = is_string($fecha) ? strtotime($fecha) : false;
        if ($version === false) throw new RuntimeException('La fecha del pago no es válida.');
        // Milisegundos evitan perder cambios dentro del mismo segundo.
        $version = $version * 1000 + (int) (new DateTimeImmutable($fecha))->format('v');
        $estado = $remoto['status'] ?? null;
        $detalle = $remoto['status_detail'] ?? null;
        if (!in_array($estado, ['approved', 'pending', 'in_process', 'authorized', 'in_mediation', 'rejected', 'cancelled', 'refunded', 'charged_back'], true)
            || !is_string($detalle) || strlen($detalle) > 80) throw new RuntimeException('Estado de pago inválido.');
        $acreditado = 0;
        if (in_array($estado, ['approved', 'in_mediation'], true)) {
            $total = MercadoPagoSeguridad::centavos($pago['monto']);
            $reembolso = self::centavosApi($remoto['transaction_amount_refunded'] ?? null);
            if ($reembolso > $total) throw new RuntimeException('El reembolso no coincide con el aporte.');
            $acreditado = $total - $reembolso;
            if ($reembolso > 0 && $reembolso < $total) $detalle = 'partially_refunded';
        }
        $this->repositorio->acreditarTransaccion($pago, $id, $acreditado, $estado, $detalle, $version);
    }

    /** Procesamiento de la cola fuera de la petición HTTP. user_id solo selecciona el token de consulta. */
    public function procesarNotificacion(array $evento): void
    {
        $cuenta = $this->repositorio->cuentaPorMp($evento['mp_usuario']);
        if ($cuenta === null) throw new RuntimeException('El receptor todavía no tiene una cuenta vinculada.');
        $token = $this->token((int) $cuenta['id_usuario'], $evento['mp_usuario']);
        $remoto = $this->cliente->solicitar('GET', '/v1/payments/' . $evento['payment_id'], null, $token);
        $ref = $remoto['external_reference'] ?? null;
        $pago = is_string($ref) ? $this->repositorio->pago($ref) : null;
        if ($pago === null || $pago['mp_receptor'] !== $evento['mp_usuario']) throw new RuntimeException('No se encontró el aporte del receptor.');
        $this->sincronizar($ref, $evento['payment_id']);
    }
}
