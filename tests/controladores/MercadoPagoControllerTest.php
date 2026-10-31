<?php
require_once __DIR__ . '/../../app/controladores/MercadoPagoController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
function mercadoPagoControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $sesion = $_SESSION; $get = $_GET; $post = $_POST; $server = $_SERVER; $codigo = http_response_code();
    // Las pruebas revierten sus datos. Si falta la migración, pueden usar tablas temporales.
    $temporales = false;
    try {
        $pdo->query('SELECT referencia FROM mercadopago_pagos LIMIT 0');
        $pdo->query('SELECT id_usuario FROM mercadopago_cuentas LIMIT 0');
    } catch (PDOException $e) {
        $sql = file_get_contents(__DIR__ . '/../../database/migrations/20261031_mercadopago.sql');
        $sql = preg_replace('/,\n    FOREIGN KEY[^;]+(?=\n\);)/s', '', $sql);
        $sql = str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $sql);
        foreach (explode(';', $sql) as $sentencia) if (trim($sentencia) !== '') $pdo->exec($sentencia);
        $temporales = true;
    }
    $pdo->beginTransaction();
    $assertThrows = static function (Closure $accion, string $mensaje): void {
        try { $accion(); } catch (InvalidArgumentException | RuntimeException | PDOException $e) { TestUtils::comprobar(true, $mensaje); return; }
        throw new LogicException('No rechazó: ' . $mensaje);
    };
    $capturar = static function (Closure $accion): string {
        http_response_code(200); ob_start(); try { $accion(); return ob_get_contents(); } finally { ob_end_clean(); }
    };
    try {
        $_SESSION = [];
        $f = crearFixtureBuscador($pdo);
        $campana = (new CampRepository($pdo))->findById($f['mesa']);
        $creadorId = $campana->getOwnerId();
        $usuarios = new UsuariosRepository($pdo);
        $creador = $usuarios->findById($creadorId);
        $nivel = (new ValidacionesRepository($pdo))->findByNivel(1)->getId();
        $donanteId = (int) $usuarios->insertarUsuario(Usuario::crear('Donante prueba', 'mp-' . bin2hex(random_bytes(6)) . '@example.com', 'Password123', $nivel));
        $donante = $usuarios->findById($donanteId);
        $config = ['habilitado' => true, 'client_id' => '123456', 'client_secret' => 'solo-ficticio', 'url_publica' => 'https://ejemplo.test',
            'clave_cifrado' => base64_encode(random_bytes(32)), 'webhook_secret' => 'firma-ficticia', 'produccion' => false];
        $repo = new MercadoPagoRepository($pdo);
        $llamadas = []; $fallar = false; $estado = 'created'; $detalle = 'created'; $version = 1; $ref = null;
        $receptor = '123456789'; $identidadReal = false; $manipular = null;
        $cliente = new MercadoPagoCliente(static function ($metodo, $ruta, $datos, $token, $clave) use (&$llamadas, &$fallar, &$estado, &$detalle, &$version, &$ref, &$receptor, &$identidadReal, &$manipular): array {
            $llamadas[] = compact('metodo', 'ruta', 'datos', 'token', 'clave');
            if ($ruta === '/oauth/token') return ['access_token' => 'token-vendedor-ficticio', 'refresh_token' => 'refresh-ficticio', 'user_id' => $receptor, 'expires_in' => 3600, 'live_mode' => true];
            if ($ruta === '/users/me') return ['id' => $receptor, 'site_id' => 'MLA', 'tags' => $identidadReal ? [] : ['test_user']];
            if ($fallar) throw new RuntimeException('Error de transporte simulado.');
            $respuesta = ['id' => 'ORDTSTPRUEBA123', 'checkout_url' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?order_id=ORDTSTPRUEBA123',
                'external_reference' => $ref, 'user_id' => $receptor, 'currency' => 'ARS', 'type' => 'online', 'total_amount' => '1000.01',
                'total_paid_amount' => '1000.01', 'status' => $estado, 'status_detail' => $detalle, 'version' => $version,
                'integration_data' => ['application_id' => '123456'], 'transactions' => ['refunds' => [['amount' => '100.01']]]];
            if ($manipular !== null) $respuesta[$manipular] = 'incorrecto';
            return $respuesta;
        });
        $servicio = new MercadoPagoServicio($repo, $cliente, $config);
        $control = new MercadoPagoController($repo, $servicio, $config, new BaneoRepository($pdo));
        $url = $servicio->iniciarOAuth($creadorId, 'crear_campana');
        parse_str(parse_url($url, PHP_URL_QUERY), $parametros);
        TestUtils::comprobar($parametros['code_challenge_method'] === 'S256' && isset($parametros['state']) && !str_contains($url, 'solo-ficticio'), 'OAuth usa state y PKCE sin exponer el secreto.');
        $assertThrows(static fn () => $servicio->terminarOAuth($creadorId, ['state' => 'ajeno', 'code' => 'x']), 'Rechaza un callback con state ajeno.');
        $assertThrows(static fn () => $servicio->terminarOAuth($donanteId, ['state' => $parametros['state'], 'code' => 'x']), 'No vincula la autorización a otro usuario.');
        $_SESSION['mp_oauth']['creado'] = time() - 601;
        $assertThrows(static fn () => $servicio->terminarOAuth($creadorId, ['state' => $parametros['state'], 'code' => 'x']), 'Rechaza una autorización vencida.');
        $servicio->iniciarOAuth($creadorId, 'crear_campana'); $state = $_SESSION['mp_oauth']['state'];
        $identidadReal = true;
        $assertThrows(static fn () => $servicio->terminarOAuth($creadorId, ['state' => $state, 'code' => 'x']), 'El entorno de pruebas rechaza cuentas reales.');
        $identidadReal = false;
        $servicio->iniciarOAuth($creadorId, 'crear_campana'); $state = $_SESSION['mp_oauth']['state'];
        TestUtils::comprobar($servicio->terminarOAuth($creadorId, ['state' => $state, 'code' => 'x']) === 'crear_campana', 'Completa la vinculación y conserva un destino interno.');
        $assertThrows(static fn () => $servicio->terminarOAuth($creadorId, ['state' => $state, 'code' => 'x']), 'No permite reutilizar el callback OAuth.');
        TestUtils::comprobar(!str_contains($repo->cuenta($creadorId)['access_cifrado'], 'token-vendedor') && $servicio->token($creadorId) === 'token-vendedor-ficticio', 'Guarda la autorización cifrada y la recupera en backend.');
        $cuentaOriginal = $repo->cuenta($creadorId);
        $servicio->iniciarOAuth($donanteId, 'mercadopago'); $state = $_SESSION['mp_oauth']['state'];
        $assertThrows(static fn () => $servicio->terminarOAuth($donanteId, ['state' => $state, 'code' => 'x']), 'Una misma cuenta externa no se atribuye a dos usuarios.');
        TestUtils::comprobar($repo->cuenta($donanteId) === null && $repo->cuenta($creadorId)['access_cifrado'] === $cuentaOriginal['access_cifrado'], 'La colisión no sobrescribe los tokens de otra cuenta.');
        $pdo->prepare('UPDATE mercadopago_cuentas SET vence_en=0 WHERE id_usuario=?')->execute([$creadorId]);
        $servicio->token($creadorId);
        TestUtils::comprobar((int) $repo->cuenta($creadorId)['vence_en'] > time(), 'Renueva y cifra el token vencido.');

        $ref = MercadoPagoSeguridad::uuid();
        $pdo->prepare('UPDATE mercadopago_cuentas SET vence_en=0 WHERE id_usuario=?')->execute([$creadorId]);
        $fallar = true;
        $assertThrows(static fn () => $servicio->crearPago($donanteId, $donante->getEmail(), $f['mesa'], '1000.01', $ref), 'Un fallo de red conserva el intento sin acreditar.');
        TestUtils::comprobar($repo->pago($ref)['order_id'] === null && $repo->pago($ref)['acreditado'] === '0.00', 'No inventa una aprobación si falla Mercado Pago.');
        TestUtils::comprobar((int) $repo->cuenta($creadorId)['vence_en'] > time(), 'El fallo del cobro no revierte una renovación de token ya confirmada.');
        $fallar = false;
        $checkout = $servicio->crearPago($donanteId, $donante->getEmail(), $f['mesa'], '1000.01', $ref);
        $posts = array_values(array_filter($llamadas, static fn ($l) => $l['ruta'] === '/v1/orders'));
        TestUtils::comprobar($posts[0]['clave'] === $posts[1]['clave'] && $posts[1]['token'] === 'token-vendedor-ficticio', 'El reintento conserva la idempotencia y usa el token del creador.');
        $n = count($llamadas);
        TestUtils::comprobar($servicio->crearPago($donanteId, $donante->getEmail(), $f['mesa'], '1000.01', $ref) === $checkout && count($llamadas) === $n, 'Un POST repetido reutiliza el checkout sin crear otro cobro.');
        $assertThrows(static fn () => $servicio->crearPago($donanteId, $donante->getEmail(), $f['mesa'], '2000', $ref), 'No reutiliza el intento con otro monto.');
        $assertThrows(static fn () => $servicio->crearPago($creadorId, $creador->getEmail(), $f['mesa'], '1000.01', $ref), 'No permite apropiarse de un intento ajeno.');
        $assertThrows(static fn () => $servicio->crearPago($donanteId, $donante->getEmail(), $f['ropa'], '1000.01', MercadoPagoSeguridad::uuid()), 'No procesa dinero para una campaña de insumos.');
        foreach (['user_id', 'currency', 'total_amount', 'external_reference'] as $campo) {
            $manipular = $campo;
            $assertThrows(static fn () => $servicio->sincronizar($ref), 'No acredita una order con datos ajenos al aporte.');
        }
        $manipular = null;
        $servicio->sincronizar($ref);
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '0.00', 'Una order creada no es una donación acreditada.');
        $estado = 'processed'; $detalle = 'accredited'; $version = 2;
        $servicio->sincronizar($ref); $servicio->sincronizar($ref);
        $q = $pdo->prepare('SELECT logrado FROM camp WHERE id=?'); $q->execute([$f['mesa']]);
        TestUtils::comprobar($q->fetchColumn() === '1000.01' && $repo->pago($ref)['id_donacion'] !== null, 'Acredita una sola vez y actualiza el progreso en la misma transacción.');
        $estado = 'created'; $detalle = 'created'; $version = 1; $servicio->sincronizar($ref);
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '1000.01', 'Una notificación antigua no revierte un pago confirmado.');
        $estado = 'processed'; $detalle = 'partially_refunded'; $version = 3; $servicio->sincronizar($ref);
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '900.00', 'Descuenta un reembolso parcial confirmado.');
        $estado = 'refunded'; $detalle = 'refunded'; $version = 4; $servicio->sincronizar($ref); $servicio->sincronizar($ref);
        $q->execute([$f['mesa']]);
        TestUtils::comprobar($q->fetchColumn() === '0.00' && $repo->pago($ref)['id_donacion'] !== null, 'El reembolso total corrige el progreso sin borrar el historial.');

        $_SERVER['REQUEST_METHOD'] = 'GET'; $_GET = ['referencia' => $ref, 'status' => 'approved'];
        $html = $capturar(static fn () => $control->atender('pago_resultado', $donante));
        TestUtils::comprobar(str_contains($html, 'Pago reembolsado') && !str_contains($html, 'token-vendedor-ficticio'), 'La página ignora el estado enviado por URL y no filtra tokens.');
        $capturar(static fn () => $control->atender('pago_resultado', $creador));
        TestUtils::comprobar(http_response_code() === 404, 'No expone el aporte a otro usuario.');
        $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = ['csrf_token' => 'inválido'];
        $capturar(static fn () => $control->atender('mp_conectar', $creador));
        TestUtils::comprobar(http_response_code() === 403, 'La conexión exige CSRF.');
        $_GET = ['data_id' => 'ORDTSTPRUEBA123']; $_SERVER['HTTP_X_SIGNATURE'] = 'inválida';
        $capturar(static fn () => $control->webhook());
        TestUtils::comprobar(http_response_code() === 401, 'El webhook rechaza solicitudes sin firma válida.');
        $ts = (string) time(); $_SERVER['HTTP_X_REQUEST_ID'] = 'request-123';
        $_SERVER['HTTP_X_SIGNATURE'] = 'ts=' . $ts . ',v1=' . hash_hmac('sha256', 'id:ordtstprueba123;request-id:request-123;ts:' . $ts . ';', $config['webhook_secret']);
        $html = $capturar(static fn () => $control->webhook());
        TestUtils::comprobar(http_response_code() === 200 && str_contains($html, 'true'), 'El webhook firmado consulta la order y reconoce el reintento.');
        $historial = $repo->historial($donanteId);
        TestUtils::comprobar(count($historial) === 1 && $historial[0]['estado'] === 'refunded' && $repo->historial($creadorId) === [], 'El historial no duplica el pago y pertenece al usuario autenticado.');
    } finally {
        $pdo->rollBack();
        if ($temporales) {
            $pdo->exec('DROP TEMPORARY TABLE mercadopago_pagos');
            $pdo->exec('DROP TEMPORARY TABLE mercadopago_cuentas');
        }
        $_SESSION=$sesion; $_GET=$get; $_POST=$post; $_SERVER=$server; http_response_code($codigo ?: 200);
    }
}
