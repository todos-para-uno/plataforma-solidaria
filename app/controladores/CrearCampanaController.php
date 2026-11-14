<?php
require_once __DIR__ . '/../repositorios/CrearCampanaRepository.php';
require_once __DIR__ . '/../repositorios/CatCampRepository.php';
require_once __DIR__ . '/../modelos/Usuarios.php';
require_once __DIR__ . '/../seguridad/Csrf.php';
require_once __DIR__ . '/../servicios/ImagenesCampana.php';
require_once __DIR__ . '/../repositorios/CampanaImagenesRepository.php';
final class CrearCampanaController
{
    private ImagenesCampana $archivos;
    public function __construct(private PDO $pdo, ?ImagenesCampana $archivos = null)
    {
        $this->archivos = $archivos ?? new ImagenesCampana(__DIR__ . '/../../public_php/subidas/campanas');
    }
    public function mostrar(?Usuario $usuario, string $rutaBase = '.', string $rutaAssets = './assets'): void
    {
        if (!$usuario) { header('Location: ' . $rutaBase . '/index.php?ruta=ingresar', true, 303); return; }
        header('Cache-Control: no-store');
        $errores = []; $entrada = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sobreLimite = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && $_POST === [] && $_FILES === [];
            if ($sobreLimite) {
                http_response_code(413);
                $errores[] = 'El envío supera el tamaño permitido. Elegí fotos más livianas y volvé a completar el formulario.';
            } else {
                if (!Csrf::validar($_POST['csrf_token'] ?? null)) { http_response_code(403); echo 'El formulario venció. Recargá la página.'; return; }
                $rutasGuardadas = [];
                try {
                    $clave = $_POST['clave'] ?? null;
                    if (!is_string($clave) || !isset($_SESSION['crear_campana_claves'][$clave])) throw new InvalidArgumentException('El formulario ya se usó o venció. Revisá Mis campañas antes de volver a publicar.');
                    $resultado = $this->archivos->validar($_FILES['imagenes'] ?? null);
                    if ($resultado['errores']) throw new InvalidArgumentException(implode(' ', $resultado['errores']));
                    $id = (new CrearCampanaRepository($this->pdo))->publicar($usuario->getId(), $entrada,
                        function (int $id) use ($resultado, &$rutasGuardadas): void {
                            $rutasGuardadas = $this->archivos->guardar($resultado['archivos']);
                            if ($rutasGuardadas) (new CampanaImagenesRepository($this->pdo))->guardarParaCampana($id, $rutasGuardadas);
                        });
                    unset($_SESSION['crear_campana_claves'][$clave]);
                    header('Location: ' . $rutaBase . '/index.php?ruta=campana&id=' . $id, true, 303); return;
                } catch (InvalidArgumentException $e) { http_response_code(422); $errores[] = $e->getMessage(); }
                catch (Throwable $e) {
                    error_log('No se pudo publicar la campaña: ' . $e->getMessage());
                    http_response_code(503);
                    $errores[] = 'No pudimos guardar la campaña y sus imágenes. Intentá nuevamente.';
                    $errores[] = 'Detalle técnico (desarrollo): ' . $e->getMessage();
                }
                $this->archivos->eliminar($rutasGuardadas);
                if (!empty($_FILES['imagenes'])) $errores[] = 'Volvé a seleccionar las fotos antes de publicar.';
            }
        }
        $categorias = (new CatCampRepository($this->pdo))->listarParaBuscador();
        $categoriasInsumos = $this->pdo->query('SELECT id,nombre FROM categoria_insumos ORDER BY nombre')->fetchAll();
        $sugerencias = $this->pdo->query('SELECT DISTINCT nombre FROM sub_categoria_insumos ORDER BY nombre')->fetchAll(PDO::FETCH_COLUMN);
        $catalogo = CatalogoGeografico::getLocalidadesPorProvincia();
        $valor = static fn(string $campo, string $default = '') => is_string($entrada[$campo] ?? null) ? $entrada[$campo] : $default;
        $tipoAyuda = in_array($valor('tipo_ayuda'), ['dinero','insumos'], true) ? $valor('tipo_ayuda') : 'insumos';
        $filas = is_array($entrada['insumos'] ?? null) ? array_slice(array_values($entrada['insumos']),0,20) : [['categoria'=>'','nombre'=>'','cantidad'=>'']];
        $filas = array_map(static fn($f) => is_array($f) ? array_filter($f,'is_string') : [], $filas);
        $clave = bin2hex(random_bytes(32));
        $_SESSION['crear_campana_claves'] = array_slice($_SESSION['crear_campana_claves'] ?? [],-19,null,true);
        $_SESSION['crear_campana_claves'][$clave] = true;
        $csrfToken = Csrf::generar(); $usuarioActual = ['nombre'=>$usuario->getNombre(),'admin'=>$usuario->getAdmin()];
        $maxImagenes = ImagenesCampana::MAXIMO_IMAGENES;
        $tamanoMaximoImagen = ImagenesCampana::tamanoMaximoPorImagen();
        $tamanoMaximoTotal = ImagenesCampana::tamanoMaximoTotal();
        $textoTamanoImagen = ImagenesCampana::formatearTamano($tamanoMaximoImagen);
        require __DIR__ . '/../vistas/crear_campana.php';
    }
}
