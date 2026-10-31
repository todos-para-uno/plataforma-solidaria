<?php
require_once __DIR__ . '/../servicios/MercadoPagoServicio.php';
require_once __DIR__ . '/../modelos/Usuarios.php';
require_once __DIR__ . '/../seguridad/Csrf.php';
final class MercadoPagoController
{
    public function __construct(private MercadoPagoRepository $repositorio, private MercadoPagoServicio $servicio, private array $config, private BaneoRepository $baneos) {}

    public function atender(string $ruta, ?Usuario $usuario, string $rutaBase = '.', string $rutaAssets = './assets'): void
    {
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        if ($usuario === null) {
            header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validar($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo 'La solicitud no es válida. Recargá la página e intentá nuevamente.';
            return;
        }
        $usuarioActual = ['nombre' => $usuario->getNombre(), 'mail' => $usuario->getEmail(), 'admin' => $usuario->getAdmin()];
        $config = $this->config;
        $csrfToken = Csrf::generar();
        $mensaje = $_SESSION['mp_mensaje'] ?? null;
        unset($_SESSION['mp_mensaje']);
        try {
            if ($ruta === 'mp_conectar') {
                $url = $this->servicio->iniciarOAuth($usuario->getId(), is_string($_POST['volver'] ?? null) ? $_POST['volver'] : 'mercadopago');
                header('Location: ' . $url, true, 303);
                return;
            }
            if ($ruta === 'mp_retorno') {
                $volver = $this->servicio->terminarOAuth($usuario->getId(), $_GET);
                $_SESSION['mp_mensaje'] = 'Tu cuenta de Mercado Pago quedó conectada.';
                header('Location: ' . $rutaBase . '/index.php?ruta=' . $volver, true, 303);
                return;
            }
            if ($ruta === 'mis_donaciones') {
                $donaciones = $this->repositorio->historial($usuario->getId());
                require __DIR__ . '/../vistas/mis_donaciones.php';
                return;
            }
            if (in_array($ruta, ['pago_resultado', 'mp_verificar'], true)) {
                $ref = $ruta === 'mp_verificar' ? ($_POST['referencia'] ?? null) : ($_GET['referencia'] ?? null);
                $pago = is_string($ref) ? $this->repositorio->pago($ref) : null;
                if ($pago === null || (int) $pago['id_usuario'] !== $usuario->getId()) {
                    http_response_code(404); echo 'Aporte no encontrado.'; return;
                }
                if ($ruta === 'mp_verificar') {
                    $this->servicio->sincronizar($ref);
                    header('Location: ' . $rutaBase . '/index.php?ruta=pago_resultado&referencia=' . rawurlencode($ref), true, 303);
                    return;
                }
                require __DIR__ . '/../vistas/pago_resultado.php';
                return;
            }
            if ($ruta === 'donar') {
                $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $campana = $id === false ? null : $this->repositorio->campana($id);
                if ($campana === null || !$campana['monetaria'] || $campana['estado'] !== 'activa' || $this->baneos->estaBaneado((int) $campana['owner_id'])) {
                    http_response_code(404); echo 'Campaña no disponible para recibir aportes.'; return;
                }
                $this->servicio->verificarConfiguracion();
                $monto = '';
                $error = null;
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $referencia = $_POST['referencia'] ?? null;
                    $intento = is_string($referencia) ? ($_SESSION['mp_intentos'][$referencia] ?? null) : null;
                    if (!is_array($intento) || $intento['campana'] !== $id || $intento['usuario'] !== $usuario->getId() || time() - $intento['creado'] > 7200) {
                        http_response_code(403); echo 'El formulario venció. Abrí nuevamente la campaña.'; return;
                    }
                    $monto = is_string($_POST['monto'] ?? null) ? trim($_POST['monto']) : '';
                    try {
                        $url = $this->servicio->crearPago($usuario->getId(), $usuario->getEmail(), $id, $monto, $referencia);
                        header('Location: ' . $url, true, 303);
                        return;
                    } catch (InvalidArgumentException | RuntimeException $e) {
                        // Los errores propios nunca incluyen respuestas ni credenciales del proveedor.
                        $error = $e->getMessage();
                        http_response_code($e instanceof InvalidArgumentException ? 422 : 503);
                    }
                } else {
                    $referencia = MercadoPagoSeguridad::uuid();
                    $_SESSION['mp_intentos'] = array_slice($_SESSION['mp_intentos'] ?? [], -19, null, true);
                    $_SESSION['mp_intentos'][$referencia] = ['campana' => $id, 'usuario' => $usuario->getId(), 'creado' => time()];
                }
                require __DIR__ . '/../vistas/donar.php';
                return;
            }
            // Pantalla de vinculación: no consultar tablas nuevas mientras falta configurar el entorno.
            $configurada = false;
            $cuenta = null;
            $error = null;
            try {
                $this->servicio->verificarConfiguracion();
                $cuenta = $this->repositorio->cuenta($usuario->getId());
                $configurada = true;
            } catch (RuntimeException $e) {
                $error = 'La conexión con Mercado Pago todavía está en preparación. Podés seguir usando tu cuenta.';
            }
            require __DIR__ . '/../vistas/mercadopago.php';
        } catch (PDOException $e) {
            http_response_code(503);
            $error = 'No pudimos guardar o consultar la operación. Revisá la configuración y la migración de Mercado Pago.';
            require __DIR__ . '/../vistas/mercadopago_error.php';
        } catch (InvalidArgumentException | RuntimeException $e) {
            http_response_code($e instanceof InvalidArgumentException ? 422 : 503);
            $error = $e->getMessage();
            require __DIR__ . '/../vistas/mercadopago_error.php';
        }
    }

    public function webhook(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        try {
            $this->servicio->verificarConfiguracion();
            // PHP normaliza el punto de data.id a guion bajo al construir $_GET.
            $id = $_GET['data_id'] ?? null;
            if (!MercadoPagoSeguridad::firmaValida($id, $_SERVER['HTTP_X_SIGNATURE'] ?? null,
                $_SERVER['HTTP_X_REQUEST_ID'] ?? null, $this->config['webhook_secret'])) {
                http_response_code(401); echo '{"recibido":false}'; return;
            }
            // Solo el ID firmado elige el recurso. No acreditar importes ni estados del cuerpo recibido.
            $pago = $this->repositorio->pagoPorOrder($id);
            if ($pago === null) {
                // Puede llegar antes de que la respuesta de creación se haya guardado: pedir reintento.
                http_response_code(503); echo '{"recibido":false}'; return;
            }
            $this->servicio->sincronizar($pago['referencia']);
            echo '{"recibido":true}';
        } catch (Throwable $e) {
            // El proveedor reintentará. No registrar el cuerpo o los tokens.
            http_response_code(503);
            echo '{"recibido":false}';
        }
    }
}
