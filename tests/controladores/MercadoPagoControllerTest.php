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
        $pdo->query('SELECT referencia,preference_id FROM mercadopago_pagos LIMIT 0');
        $pdo->query('SELECT state_hash FROM mercadopago_oauth_estados LIMIT 0');
        $pdo->query('SELECT payment_id FROM mercadopago_transacciones LIMIT 0');
        $pdo->query('SELECT payment_id FROM mercadopago_notificaciones LIMIT 0');
        $pdo->query('SELECT id_usuario FROM mercadopago_cuentas LIMIT 0');
    } catch (PDOException $e) {
        $sql = file_get_contents(__DIR__ . '/../../database/migrations/20261031_mercadopago.sql');
        $sql = preg_replace('/,\n    FOREIGN KEY[^;]+(?=\n\);)/s', '', $sql);
        $sql = str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $sql);
        foreach (explode(';', $sql) as $sentencia) if (trim($sentencia) !== '') $pdo->exec($sentencia);
        $pdo->exec(file_get_contents(__DIR__ . '/../../database/migrations/20261103_mercadopago_autorizacion.sql'));
        $sql = file_get_contents(__DIR__ . '/../../database/migrations/20261104_mercadopago_preferences.sql');
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
        $llamadas = []; $fallar = false; $estado = 'pending'; $detalle = 'created'; $version = 1; $ref = null;
        $paymentId = '100123'; $receptor = '123456789'; $identidadReal = false; $manipular = null;
        $respuestaOAuth = ['access_token' => 'token-vendedor-ficticio', 'refresh_token' => 'refresh-ficticio', 'expires_in' => 3600, 'live_mode' => true, 'token_type' => 'bearer', 'scope' => 'read write offline_access'];
        $errorOAuth = null; $errorPago = null; $fallarIdentidad = false;
        $cliente = new MercadoPagoCliente(static function ($metodo, $ruta, $datos, $token, $clave) use (&$llamadas, &$fallar, &$estado, &$detalle, &$version, &$ref, &$receptor, &$identidadReal, &$manipular, &$paymentId, &$respuestaOAuth, &$errorOAuth, &$errorPago, &$fallarIdentidad): array {
            $llamadas[] = compact('metodo', 'ruta', 'datos', 'token', 'clave');
            if ($ruta === '/oauth/token') {
                if ($errorOAuth !== null) throw $errorOAuth;
                return $respuestaOAuth + ['user_id' => $receptor];
            }
            if ($ruta === '/users/me' && $fallarIdentidad) throw new RuntimeException('Identidad no disponible.');
            if ($ruta === '/users/me') return ['id' => $receptor, 'site_id' => 'MLA', 'tags' => $identidadReal ? [] : ['test_user']];
            if ($errorPago !== null) throw $errorPago;
            if ($fallar) throw new RuntimeException('Error de transporte simulado.');
            if ($ruta === '/checkout/preferences') return ['id' => '123-' . $ref,
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=123-' . $ref,
                'collector_id' => $receptor, 'external_reference' => $ref];
            if (str_starts_with($ruta, '/v1/payments/search?')) return ['results' => [['id' => $paymentId]], 'paging' => ['total' => 1]];
            $respuesta = ['id' => $paymentId, 'external_reference' => $ref, 'collector_id' => $receptor,
                'currency_id' => 'ARS', 'transaction_amount' => 1000.01, 'status' => $estado, 'status_detail' => $detalle,
                'date_last_updated' => '2026-11-04T12:00:0' . $version . '.123-03:00',
                'transaction_amount_refunded' => $detalle === 'partially_refunded' ? 100.01 : ($estado === 'refunded' ? 1000.01 : 0)];
            if ($manipular !== null) $respuesta[$manipular] = 'incorrecto';
            return $respuesta;
        });
        $servicio = new MercadoPagoServicio($repo, $cliente, $config);
        $control = new MercadoPagoController($repo, $servicio, $config, new BaneoRepository($pdo));
        $url = $servicio->iniciarOAuth($creadorId, 'crear_campana');
        parse_str(parse_url($url, PHP_URL_QUERY), $parametros);
        TestUtils::comprobar($parametros['redirect_uri'] === 'https://ejemplo.test/mercadopago-retorno.php'
            && !str_contains($parametros['redirect_uri'], '?'), 'La autorización usa un retorno fijo sin parámetros de enrutamiento.');
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
        $sesionOAuth = $_SESSION['mp_oauth'];
        TestUtils::comprobar($servicio->terminarOAuth($creadorId, ['state' => $state, 'code' => 'x']) === 'crear_campana', 'Completa la vinculación y conserva un destino interno.');
        $intercambios = array_values(array_filter($llamadas, static fn ($l) => $l['ruta'] === '/oauth/token' && ($l['datos']['grant_type'] ?? '') === 'authorization_code'));
        $intercambio = $intercambios[array_key_last($intercambios)]['datos'];
        TestUtils::comprobar($intercambio['test_token'] === true && $intercambio['redirect_uri'] === $parametros['redirect_uri']
            && isset($intercambio['code_verifier']), 'El intercambio solicita un token de prueba y conserva el retorno y PKCE.');
        $assertThrows(static fn () => $servicio->terminarOAuth($creadorId, ['state' => $state, 'code' => 'x']), 'No permite reutilizar el callback OAuth.');
        $_SESSION['mp_oauth'] = $sesionOAuth;
        $assertThrows(static fn () => $servicio->terminarOAuth($creadorId, ['state' => $state, 'code' => 'x']), 'El estado persistente impide el replay incluso restaurando una sesión anterior.');
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
        TestUtils::comprobar($repo->pago($ref)['preference_id'] === null && $repo->pago($ref)['acreditado'] === '0.00', 'No inventa una aprobación si falla Mercado Pago.');
        TestUtils::comprobar((int) $repo->cuenta($creadorId)['vence_en'] > time(), 'El fallo del cobro no revierte una renovación de token ya confirmada.');
        $fallar = false;
        $checkout = $servicio->crearPago($donanteId, $donante->getEmail(), $f['mesa'], '1000.01', $ref);
        $posts = array_values(array_filter($llamadas, static fn ($l) => $l['ruta'] === '/checkout/preferences'));
        TestUtils::comprobar($posts[0]['clave'] === $posts[1]['clave'] && $posts[1]['token'] === 'token-vendedor-ficticio', 'El reintento conserva la idempotencia y usa el token del creador.');
        $datosPreferencia = $posts[1]['datos'];
        TestUtils::comprobar($datosPreferencia['items'][0]['currency_id'] === 'ARS'
            && $datosPreferencia['items'][0]['unit_price'] === 1000.01 && $datosPreferencia['marketplace_fee'] === 0
            && $datosPreferencia['external_reference'] === $ref && !isset($datosPreferencia['collector_id'])
            && $datosPreferencia['back_urls']['success'] === $servicio->url('pago_resultado', ['referencia' => $ref]),
            'La preferencia usa el importe persistido, comisión cero y retornos de la aplicación sin elegir receptor desde el navegador.');
        $n = count($llamadas);
        TestUtils::comprobar($servicio->crearPago($donanteId, $donante->getEmail(), $f['mesa'], '1000.01', $ref) === $checkout && count($llamadas) === $n, 'Un POST repetido reutiliza el checkout sin crear otro cobro.');
        $assertThrows(static fn () => $servicio->crearPago($donanteId, $donante->getEmail(), $f['mesa'], '2000', $ref), 'No reutiliza el intento con otro monto.');
        $assertThrows(static fn () => $servicio->crearPago($creadorId, $creador->getEmail(), $f['mesa'], '1000.01', $ref), 'No permite apropiarse de un intento ajeno.');
        $assertThrows(static fn () => $servicio->crearPago($donanteId, $donante->getEmail(), $f['ropa'], '1000.01', MercadoPagoSeguridad::uuid()), 'No procesa dinero para una campaña de insumos.');
        foreach (['collector_id', 'currency_id', 'transaction_amount', 'external_reference'] as $campo) {
            $manipular = $campo;
            $assertThrows(static fn () => $servicio->sincronizar($ref), 'No acredita un pago con datos ajenos al aporte.');
        }
        $manipular = null;
        $servicio->sincronizar($ref);
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '0.00', 'Un pago pendiente no es una donación acreditada.');
        $estado = 'approved'; $detalle = 'accredited'; $version = 2;
        $servicio->sincronizar($ref); $servicio->sincronizar($ref);
        $q = $pdo->prepare('SELECT logrado FROM camp WHERE id=?'); $q->execute([$f['mesa']]);
        TestUtils::comprobar($q->fetchColumn() === '1000.01' && $repo->pago($ref)['id_donacion'] !== null, 'Acredita una sola vez y actualiza el progreso en la misma transacción.');
        $estado = 'pending'; $detalle = 'created'; $version = 1; $servicio->sincronizar($ref);
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '1000.01', 'Una notificación antigua no revierte un pago confirmado.');
        $estado = 'approved'; $detalle = 'partially_refunded'; $version = 3; $servicio->sincronizar($ref);
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '900.00', 'Descuenta un reembolso parcial confirmado.');
        $estado = 'refunded'; $detalle = 'refunded'; $version = 4; $servicio->sincronizar($ref); $servicio->sincronizar($ref);
        $q->execute([$f['mesa']]);
        TestUtils::comprobar($q->fetchColumn() === '0.00' && $repo->pago($ref)['id_donacion'] !== null, 'El reembolso total corrige el progreso sin borrar el historial.');

        $paymentId = '100125'; $estado = 'approved'; $detalle = 'accredited'; $version = 5;
        $servicio->sincronizar($ref); $servicio->sincronizar($ref);
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '1000.01', 'Otro pago de la misma preferencia se registra una sola vez en su propia transacción.');
        $paymentId = '100126'; $estado = 'rejected'; $detalle = 'cc_rejected_other_reason';
        $servicio->sincronizar($ref);
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '1000.01' && $repo->pago($ref)['estado'] === 'approved', 'Un intento rechazado no oculta otro pago aprobado.');
        $paymentId = '100123'; $estado = 'refunded'; $detalle = 'refunded'; $version = 4;
        $servicio->sincronizar($ref);
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '1000.01', 'El reembolso de un pago anterior no resta dinero de otro pago.');
        $paymentId = '100125'; $version = 6; $servicio->sincronizar($ref);
        $paymentId = '100123'; $version = 4;
        TestUtils::comprobar($repo->pago($ref)['acreditado'] === '0.00', 'La suma acreditada contempla el reembolso de cada payment ID.');

        $_SERVER['REQUEST_METHOD'] = 'GET'; $_GET = ['referencia' => $ref, 'status' => 'approved'];
        $html = $capturar(static fn () => $control->atender('pago_resultado', $donante));
        TestUtils::comprobar(str_contains($html, 'Pago reembolsado') && !str_contains($html, 'token-vendedor-ficticio'), 'La página ignora el estado enviado por URL y no filtra tokens.');
        $capturar(static fn () => $control->atender('pago_resultado', $creador));
        TestUtils::comprobar(http_response_code() === 404, 'No expone el aporte a otro usuario.');
        $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = ['csrf_token' => 'inválido'];
        $capturar(static fn () => $control->atender('mp_conectar', $creador));
        TestUtils::comprobar(http_response_code() === 403, 'La conexión exige CSRF.');

        $controlDiagnostico = new MercadoPagoController($repo, $servicio, $config + ['diagnostico_oauth' => true], new BaneoRepository($pdo));
        $_POST = ['csrf_token' => Csrf::generar()];
        $html = $capturar(static fn () => $controlDiagnostico->atender('mp_conectar', $creador));
        $intentoDiagnostico = $_SESSION['mp_oauth'];
        parse_str(parse_url($intentoDiagnostico['url_diagnostico'], PHP_URL_QUERY), $parametrosDiagnostico);
        TestUtils::comprobar(http_response_code() === 200 && str_contains($html, 'https://ejemplo.test/mercadopago-retorno.php')
            && str_contains($html, 'Continuar a Mercado Pago'), 'El diagnóstico muestra el callback y permite continuar.');
        foreach ([$intentoDiagnostico['state'], $intentoDiagnostico['verifier'], $parametrosDiagnostico['code_challenge'],
            $config['client_secret'], $config['clave_cifrado'], $config['webhook_secret']] as $secreto) {
            TestUtils::comprobar(!str_contains($html, $secreto), 'El diagnóstico no expone valores sensibles del intento o la configuración.');
        }
        $_POST['continuar_oauth'] = '1';
        $_POST['url_diagnostico'] = 'https://ejemplo-malicioso.test';
        $html = $capturar(static fn () => $controlDiagnostico->atender('mp_conectar', $creador));
        TestUtils::comprobar(http_response_code() === 303 && $html === ''
            && $_SESSION['mp_oauth']['state'] === $intentoDiagnostico['state']
            && $_SESSION['mp_oauth']['verifier'] === $intentoDiagnostico['verifier']
            && !isset($_SESSION['mp_oauth']['url_diagnostico']), 'Continuar reutiliza el intento guardado y consume la continuación.');
        $capturar(static fn () => $controlDiagnostico->atender('mp_conectar', $creador));
        TestUtils::comprobar(http_response_code() === 422, 'No permite reutilizar la continuación del diagnóstico.');
        $_POST = ['csrf_token' => Csrf::generar()];
        $capturar(static fn () => $controlDiagnostico->atender('mp_conectar', $creador));
        $_POST['continuar_oauth'] = '1';
        $capturar(static fn () => $controlDiagnostico->atender('mp_conectar', $donante));
        TestUtils::comprobar(http_response_code() === 422, 'Otro usuario no puede continuar el diagnóstico pendiente.');
        $_SESSION['mp_oauth']['creado'] = time() - 601;
        $capturar(static fn () => $controlDiagnostico->atender('mp_conectar', $creador));
        TestUtils::comprobar(http_response_code() === 422, 'El diagnóstico respeta el vencimiento del intento OAuth.');
        $_GET = ['data_id' => '100123']; $_SERVER['HTTP_X_SIGNATURE'] = 'inválida';
        $capturar(static fn () => $control->webhook());
        TestUtils::comprobar(http_response_code() === 401, 'El webhook rechaza solicitudes sin firma válida.');
        $ts = (string) time(); $_SERVER['HTTP_X_REQUEST_ID'] = 'request-123';
        $_SERVER['HTTP_X_SIGNATURE'] = 'ts=' . $ts . ',v1=' . hash_hmac('sha256', 'id:100123;request-id:request-123;ts:' . $ts . ';', $config['webhook_secret']);
        $cuerpoWebhook = json_encode(['type' => 'payment', 'data' => ['id' => '100123'], 'user_id' => $receptor]);
        $antes = count($llamadas);
        $html = $capturar(static fn () => $control->webhook($cuerpoWebhook));
        TestUtils::comprobar(count($llamadas) === $antes, 'El webhook persiste el evento y responde sin llamadas a Mercado Pago.');
        TestUtils::comprobar($repo->procesarSiguienteNotificacion(fn ($evento) => $servicio->procesarNotificacion($evento)) === true,
            'El worker verifica el pago firmado con el token del receptor.');
        TestUtils::comprobar($repo->procesarSiguienteNotificacion(fn ($evento) => $servicio->procesarNotificacion($evento)) === null,
            'Un evento procesado no se consume nuevamente sin una notificación nueva.');
        $capturar(static fn () => $control->webhook($cuerpoWebhook));
        $fallar = true;
        TestUtils::comprobar($repo->procesarSiguienteNotificacion(fn ($evento) => $servicio->procesarNotificacion($evento)) === false,
            'Un fallo remoto conserva la notificación para reintentar.');
        $fallar = false;
        TestUtils::comprobar($repo->procesarSiguienteNotificacion(fn ($evento) => $servicio->procesarNotificacion($evento)) === null,
            'La cola respeta la espera entre reintentos.');
        $capturar(static fn () => $control->webhook($cuerpoWebhook));
        TestUtils::comprobar($repo->procesarSiguienteNotificacion(fn ($evento) => $servicio->procesarNotificacion($evento)) === true,
            'Una nueva notificación permite recuperar un pago pendiente de reintento.');
        $capturar(static fn () => $control->webhook(json_encode(['type' => 'payment', 'data' => ['id' => '100124'], 'user_id' => $receptor])));
        TestUtils::comprobar(http_response_code() === 400, 'La firma de un ID no autoriza otro ID enviado en el cuerpo.');
        TestUtils::comprobar(str_contains($html, 'true'), 'El webhook firmado reconoce el evento guardado.');
        $historial = $repo->historial($donanteId);
        TestUtils::comprobar(count($historial) === 1 && $historial[0]['estado'] === 'refunded' && $repo->historial($creadorId) === [], 'El historial no duplica el pago y pertenece al usuario autenticado.');

        // Aportes sin registro: ni el correo ni la referencia pública dan acceso a otra sesión.
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET'; $_GET = ['id' => (string) $f['mesa']];
        $usuariosAntes = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        $html = $capturar(static fn () => $control->atender('donar', null));
        TestUtils::comprobar(http_response_code() === 200 && str_contains($html, 'name="mail"') && str_contains($html, 'sin crear una cuenta'), 'Una visita sin sesión abre el formulario y puede indicar su correo.');
        $ref = array_key_last($_SESSION['mp_intentos']);
        $sesionInvitado = $_SESSION;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['referencia' => $ref, 'monto' => '1000.01', 'mail' => 'invitado@example.test', 'csrf_token' => 'ajeno'];
        $capturar(static fn () => $control->atender('donar', null));
        TestUtils::comprobar(http_response_code() === 403 && $repo->pago($ref) === null, 'El aporte de invitado también exige CSRF.');
        $_POST['csrf_token'] = Csrf::generar(); $_POST['mail'] = ['invalido'];
        $capturar(static fn () => $control->atender('donar', null));
        TestUtils::comprobar(http_response_code() === 422 && $repo->pago($ref) === null, 'El correo inválido no inicia un pago de invitado.');
        $_POST['mail'] = 'invitado@example.test'; $paymentId = '100124';
        $estado = 'pending'; $detalle = 'created'; $version = 1;
        $capturar(static fn () => $control->atender('donar', null));
        $pagoInvitado = $repo->pago($ref);
        TestUtils::comprobar(http_response_code() === 303 && $pagoInvitado['id_usuario'] === null && $pagoInvitado['invitado_hash'] === hash('sha256', $_SESSION['mp_invitado']), 'El checkout no exige registro ni inventa un usuario para el visitante.');
        $n = count($llamadas);
        $capturar(static fn () => $control->atender('donar', null));
        TestUtils::comprobar(http_response_code() === 303 && count($llamadas) === $n, 'El envío repetido de un invitado reutiliza el mismo checkout.');
        $assertThrows(static fn () => $servicio->crearPago(null, 'otro@example.test', $f['mesa'], '1000.01', $ref, $pagoInvitado['invitado_hash']), 'No cambia el correo del intento al reintentarlo.');
        $assertThrows(static fn () => $servicio->crearPago(null, 'invitado@example.test', $f['mesa'], '1000.01', $ref, hash('sha256', 'otro-navegador')), 'Otra sesión no reutiliza el intento de un invitado.');
        $estado = 'approved'; $detalle = 'accredited'; $version = 2;
        $servicio->sincronizar($ref); $servicio->sincronizar($ref);
        $pagoInvitado = $repo->pago($ref);
        $consulta = $pdo->prepare('SELECT id_usuario FROM donacion WHERE id=?'); $consulta->execute([$pagoInvitado['id_donacion']]);
        TestUtils::comprobar($consulta->fetch(PDO::FETCH_ASSOC)['id_usuario'] === null && (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() === $usuariosAntes, 'Acredita la donación sin crear cuentas y conserva al donante sin usuario.');
        $q->execute([$f['mesa']]);
        TestUtils::comprobar($q->fetchColumn() === '1000.01', 'El aporte de invitado actualiza el progreso una sola vez.');
        $_SERVER['REQUEST_METHOD'] = 'GET'; $_GET = ['referencia' => $ref];
        $html = $capturar(static fn () => $control->atender('pago_resultado', null));
        TestUtils::comprobar(http_response_code() === 200 && str_contains($html, 'Aporte confirmado') && !str_contains($html, 'Ver mis donaciones'), 'El visitante consulta su resultado en el mismo navegador sin ingresar.');
        $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = ['referencia' => $ref, 'csrf_token' => Csrf::generar()];
        $capturar(static fn () => $control->atender('mp_verificar', null));
        TestUtils::comprobar(http_response_code() === 303, 'El invitado puede verificar su pago con sesión y CSRF.');
        $_SESSION = []; $_SERVER['REQUEST_METHOD'] = 'GET';
        $capturar(static fn () => $control->atender('pago_resultado', null));
        TestUtils::comprobar(http_response_code() === 404, 'Conocer la referencia no permite consultar el aporte desde otra sesión.');
        $capturar(static fn () => $control->atender('pago_resultado', $donante));
        TestUtils::comprobar(http_response_code() === 404, 'Una cuenta registrada tampoco se apropia de un aporte de invitado.');
        $capturar(static fn () => $control->atender('mercadopago', null));
        TestUtils::comprobar(http_response_code() === 303, 'Vincular una cuenta receptora sigue requiriendo registro.');
        TestUtils::comprobar(count($repo->historial($donanteId)) === 1, 'Los aportes de invitado no se atribuyen al historial de una cuenta.');
        // Ciclo de autorización: errores permanentes, transitorios y rotación.
        $reconectar = static function () use ($servicio, $creadorId): void {
            $servicio->iniciarOAuth($creadorId, 'mercadopago');
            $servicio->terminarOAuth($creadorId, ['state' => $_SESSION['mp_oauth']['state'], 'code' => 'nuevo']);
        };
        $respuestaValida = $respuestaOAuth;
        $respuestaOAuth = array_replace($respuestaValida, ['scope' => 'offline_access payments write']);
        $reconectar();
        TestUtils::comprobar($repo->cuenta($creadorId)['scope'] === 'offline_access payments write',
            'Acepta los permisos OAuth publicados para Split 1:1 sin exigir el alias read.');
        $respuestaOAuth = $respuestaValida;
        $reconectar();
        $cuentaAntes = $repo->cuenta($creadorId);
        foreach ([['scope', 'read write'], ['scope', 'read offline_access'], ['scope', 'write offline_access'], ['scope', null], ['token_type', 'mac'], ['token_type', null]] as [$campo, $valor]) {
            $respuestaOAuth = array_replace($respuestaValida, [$campo => $valor]);
            $assertThrows($reconectar, 'Rechaza autorización sin tipo Bearer o permisos necesarios.');
            TestUtils::comprobar($repo->cuenta($creadorId)['access_cifrado'] === $cuentaAntes['access_cifrado'], 'Un consentimiento incompleto no reemplaza la cuenta existente.');
        }
        $respuestaOAuth = array_replace($respuestaValida, ['access_token' => 'token-renovado', 'refresh_token' => 'refresh-renovado']);
        $pdo->prepare('UPDATE mercadopago_cuentas SET vence_en=0 WHERE id_usuario=?')->execute([$creadorId]);
        $fallarIdentidad = true;
        $antes = count($llamadas);
        TestUtils::comprobar($servicio->token($creadorId) === 'token-renovado', 'La renovación funciona aunque users/me no esté disponible.');
        $seguridad = new MercadoPagoSeguridad($config['clave_cifrado']);
        $renovada = $repo->cuenta($creadorId);
        TestUtils::comprobar(count($llamadas) === $antes + 1
            && $seguridad->descifrar($renovada['refresh_cifrado']) === 'refresh-renovado'
            && $renovada['scope'] === 'read write offline_access', 'Guarda ambos tokens nuevos y los permisos sin una segunda llamada de red.');
        $fallarIdentidad = false;
        foreach ([new MercadoPagoErrorApi(400, 'invalid_client'), new MercadoPagoErrorApi(429, 'local_rate_limited'), new MercadoPagoErrorApi(500), new RuntimeException('Timeout simulado')] as $errorOAuth) {
            $pdo->prepare('UPDATE mercadopago_cuentas SET vence_en=0 WHERE id_usuario=?')->execute([$creadorId]);
            $assertThrows(static fn () => $servicio->token($creadorId), 'El error temporal o de configuración se propaga.');
            TestUtils::comprobar(!$repo->cuenta($creadorId)['requiere_reautorizar'], 'No confunde un fallo temporal o del cliente con revocación del vendedor.');
        }
        foreach (['invalid_grant', 'unauthorized_client'] as $codigoOAuth) {
            $errorOAuth = new MercadoPagoErrorApi(400, $codigoOAuth);
            $pdo->prepare('UPDATE mercadopago_cuentas SET vence_en=0 WHERE id_usuario=?')->execute([$creadorId]);
            $assertThrows(static fn () => $servicio->token($creadorId), 'El refresh rechazado exige reconexión.');
            TestUtils::comprobar((bool) $repo->cuenta($creadorId)['requiere_reautorizar'], 'Conserva el estado de reconexión después de lanzar el error.');
            $antes = count($llamadas);
            $assertThrows(static fn () => $servicio->token($creadorId), 'Una cuenta pendiente de reconexión no vuelve a utilizar tokens.');
            TestUtils::comprobar(count($llamadas) === $antes, 'No reintenta un refresh conocido como inválido.');
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $html = $capturar(static fn () => $control->atender('mercadopago', $creador));
            TestUtils::comprobar(str_contains($html, 'Volvé a conectar Mercado Pago') && !str_contains($html, '<h2>Mercado Pago conectado</h2>'), 'La pantalla distingue una autorización que necesita reconexión.');
            $errorOAuth = null;
            $reconectar();
            TestUtils::comprobar(!$repo->cuenta($creadorId)['requiere_reautorizar'], 'Reautorizar la misma cuenta recupera su estado operativo.');
        }
        foreach ([['scope' => 'read write'], ['user_id' => '999999']] as $cambio) {
            $respuestaOAuth = array_replace($respuestaValida, $cambio);
            $cuentaAntes = $repo->cuenta($creadorId);
            $pdo->prepare('UPDATE mercadopago_cuentas SET vence_en=0 WHERE id_usuario=?')->execute([$creadorId]);
            $assertThrows(static fn () => $servicio->token($creadorId), 'La renovación rechaza permisos insuficientes o un receptor diferente.');
            TestUtils::comprobar($repo->cuenta($creadorId)['requiere_reautorizar'] && $repo->cuenta($creadorId)['access_cifrado'] === $cuentaAntes['access_cifrado'], 'No guarda una renovación incompatible y solicita reconexión.');
            $respuestaOAuth = $respuestaValida;
            $reconectar();
        }
        foreach ([403, 429, 500, 401] as $estadoHttp) {
            $errorPago = new MercadoPagoErrorApi($estadoHttp);
            $refError = MercadoPagoSeguridad::uuid();
            $assertThrows(static fn () => $servicio->crearPago($donanteId, $donante->getEmail(), $f['mesa'], '10.00', $refError), 'La respuesta de error de Preferences no acredita dinero.');
            TestUtils::comprobar((bool) $repo->cuenta($creadorId)['requiere_reautorizar'] === ($estadoHttp === 401), 'Solo el rechazo de autenticación marca la cuenta, fuera del rollback del pago.');
            TestUtils::comprobar($repo->pago($refError)['acreditado'] === '0.00', 'Conserva el intento sin acreditar.');
        }
        $errorPago = null;
        $reconectar();
        $errorPago = new MercadoPagoErrorApi(401);
        $assertThrows(static fn () => $servicio->sincronizar($ref), 'La consulta de un pago también detecta autorización rechazada.');
        TestUtils::comprobar((bool) $repo->cuenta($creadorId)['requiere_reautorizar'], 'Persiste la reconexión cuando falla la consulta de un pago.');
        $errorPago = null;

        // Regresión de la fusión: el historial combina pagos e insumos sin perder
        // nombres, portadas ni el estado de la campaña incorporados desde main.
        require_once __DIR__ . '/../../app/controladores/MisDonacionesController.php';
        $pdo->prepare('INSERT INTO categoria_insumos (nombre) VALUES (?)')->execute(['Frazadas <script>insumo</script>']);
        $categoriaInsumo = (int) $pdo->lastInsertId();
        (new DonacionRepository($pdo))->save(['id_usuario' => $donanteId, 'id_camp' => $f['ropa'],
            'id_categoria_insumos' => $categoriaInsumo, 'cantidad' => '2.50']);
        $portada = 'assets/imagenes/imagenes-camp/abrigo/abrigo-01.webp';
        (new CampanaImagenesRepository($pdo))->guardarParaCampana($f['ropa'], [$portada]);
        $historialCombinado = $repo->historial($donanteId);
        $insumos = array_values(array_filter($historialCombinado, static fn ($fila) => !(bool) $fila['monetaria']));
        TestUtils::comprobar(count($insumos) === 1 && $insumos[0]['imagen_subida'] === $portada,
            'El historial combinado conserva la portada de los insumos sin duplicar la entrega.');
        $historialController = new MisDonacionesController(new DonacionRepository($pdo), $repo);
        $html = $capturar(static fn () => $historialController->mostrar($donante));
        TestUtils::comprobar(str_contains($html, '2,5 de Frazadas &lt;script&gt;insumo&lt;/script&gt;')
            && str_contains($html, 'Consultar el aporte') && !str_contains($html, '<script>insumo</script>'),
            'La misma pantalla conserva cantidades de insumos, escape HTML y enlaces al estado de pago.');
        $pdo->prepare("UPDATE camp SET estado='finalizada' WHERE id IN (?, ?)")->execute([$f['ropa'], $f['mesa']]);
        $html = $capturar(static fn () => $historialController->mostrar($donante));
        TestUtils::comprobar(str_contains($html, 'Campaña finalizada.') && !str_contains($html, '>Ver campaña</a>'),
            'El historial combinado no ofrece enlaces a campañas finalizadas.');
    } finally {
        $pdo->rollBack();
        if ($temporales) {
            foreach (['mercadopago_transacciones', 'mercadopago_notificaciones', 'mercadopago_oauth_estados'] as $tabla) $pdo->exec('DROP TEMPORARY TABLE ' . $tabla);
            $pdo->exec('DROP TEMPORARY TABLE mercadopago_pagos');
            $pdo->exec('DROP TEMPORARY TABLE mercadopago_cuentas');
        }
        $_SESSION=$sesion; $_GET=$get; $_POST=$post; $_SERVER=$server; http_response_code($codigo ?: 200);
    }
}
