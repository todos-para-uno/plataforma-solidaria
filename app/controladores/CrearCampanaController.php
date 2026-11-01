<?php
require_once __DIR__ . '/../repositorios/CampRepository.php';
require_once __DIR__ . '/../repositorios/CatCampRepository.php';
require_once __DIR__ . '/../repositorios/ObjetivosRepository.php';
require_once __DIR__ . '/../repositorios/CampanaImagenesRepository.php';
require_once __DIR__ . '/../servicios/ImagenesCampana.php';
require_once __DIR__ . '/../modelos/Usuarios.php';
require_once __DIR__ . '/../seguridad/Csrf.php';

/**
 * Muestra y procesa el formulario de crear campaña.
 * La campaña, su objetivo y sus imágenes se guardan juntos: si algo falla, no queda nada a medias.
 */
final class CrearCampanaController
{
    private const CAMPOS_TEXTO = [
        'nombre', 'descripcion', 'categoria', 'tipo_ayuda', 'objetivo', 'cbu',
        'insumo', 'fecha_limite', 'ubicacion_texto', 'lat', 'lng',
    ];
    private const LARGO_NOMBRE = 100;
    private const LARGO_DESCRIPCION = 5000;
    private const MONTO_MAXIMO = 9_999_999_999; // DECIMAL(12,2) en objetivos.objetivo

    public function __construct(
        private PDO $pdo,
        private CampRepository $campanas,
        private CatCampRepository $categorias,
        private ObjetivosRepository $objetivos,
        private CampanaImagenesRepository $imagenes,
        private ImagenesCampana $archivos
    ) {}

    public function mostrarFormulario(string $rutaBase, string $rutaAssets, ?Usuario $usuario): void
    {
        if ($usuario === null || !$usuario->tieneId()) {
            $this->redirigirAlIngreso($rutaBase);
            return;
        }
        $this->renderizar($rutaBase, $rutaAssets, $usuario, $this->valoresVacios(), []);
    }

    public function procesarCreacion(string $rutaBase, string $rutaAssets, ?Usuario $usuario): void
    {
        if ($usuario === null || !$usuario->tieneId()) {
            $this->redirigirAlIngreso($rutaBase);
            return;
        }

        // Si el envío supera post_max_size, PHP descarta todo el cuerpo: $_POST y $_FILES llegan vacíos.
        // Se avisa antes de revisar el CSRF, que también se perdió.
        $largo = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($largo > 0 && $_POST === [] && $_FILES === []) {
            http_response_code(413);
            $total = ImagenesCampana::tamanoMaximoTotal();
            $this->renderizar($rutaBase, $rutaAssets, $usuario, $this->valoresVacios(), [
                'Las imágenes superan el tamaño total permitido'
                . ($total > 0 ? ' (' . ImagenesCampana::formatearTamano($total) . ')' : '')
                . '. Elegí imágenes más livianas y volvé a completar el formulario.',
            ]);
            return;
        }

        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo 'La solicitud no es válida. Recargá el formulario e intentá nuevamente.';
            return;
        }

        $valores = $this->valoresVacios();
        foreach (self::CAMPOS_TEXTO as $campo) {
            $valor = $_POST[$campo] ?? '';
            if (!is_string($valor)) {
                http_response_code(400);
                $this->renderizar($rutaBase, $rutaAssets, $usuario, $valores, ['Los datos enviados no son válidos.']);
                return;
            }
            $valores[$campo] = trim($valor);
        }

        $categorias = $this->listarCategorias();
        $errores = $this->validarCampos($valores, $categorias);
        $resultadoImagenes = $this->archivos->validar($_FILES['imagenes'] ?? null);
        $errores = [...$errores, ...$resultadoImagenes['errores']];

        if ($errores === [] && $valores['tipo_ayuda'] === 'insumos') {
            // ubicaciones exige provincia y localidad del catálogo, y objetivos exige un insumo del catálogo.
            // El formulario todavía no pide esos datos, así que no se puede guardar sin inventarlos.
            $errores[] = 'Por ahora solo se pueden publicar campañas de dinero. '
                . 'Las de insumos van a estar disponibles cuando el formulario pida provincia, localidad y tipo de insumo.';
        }

        $camp = null;
        if ($errores === []) {
            try {
                // El modelo aplica sus propias reglas (por ejemplo, CBU obligatorio en campañas de dinero).
                $camp = new Camp(
                    owner_id: $usuario->getId(),
                    nombre: $valores['nombre'],
                    monetaria: true,
                    categoria: (int) $valores['categoria'],
                    descripcion: $valores['descripcion'],
                    cbu: $valores['cbu'],
                );
            } catch (InvalidArgumentException $excepcion) {
                $errores[] = $excepcion->getMessage();
            }
        }

        if ($errores !== []) {
            if ($this->seEnviaronImagenes()) {
                // Los navegadores no permiten volver a cargar archivos en un input: hay que elegirlos otra vez.
                $errores[] = 'Por seguridad, volvé a seleccionar las imágenes antes de publicar.';
            }
            http_response_code(422);
            $this->renderizar($rutaBase, $rutaAssets, $usuario, $valores, $errores, $categorias);
            return;
        }

        $rutasGuardadas = [];
        try {
            $this->pdo->beginTransaction();
            $idCamp = (int) $this->campanas->save([
                'owner_id' => $camp->getOwnerId(),
                'nombre' => $camp->getNombre(),
                'monetaria' => 1,
                'categoria' => $camp->getCategoria(),
                'descripcion' => $camp->getDescripcion(),
                'cbu' => $camp->getCbu(),
                'estado' => $camp->getEstado()->value,
                'id_ubicacion' => null,
            ]);
            $this->objetivos->save(['id_camp' => $idCamp, 'objetivo' => (int) $valores['objetivo']]);
            // Los archivos se mueven recién acá, cuando todo lo demás ya se validó.
            $rutasGuardadas = $this->archivos->guardar($resultadoImagenes['archivos']);
            $this->imagenes->guardarParaCampana($idCamp, $rutasGuardadas);
            $this->pdo->commit();
        } catch (Throwable $excepcion) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->archivos->eliminar($rutasGuardadas);
            error_log('Crear campaña: no se pudo guardar. ' . $excepcion->getMessage());
            http_response_code(500);
            $errores = ['No pudimos publicar la campaña. Intentá nuevamente más tarde.'];
            if ($this->seEnviaronImagenes()) {
                $errores[] = 'Por seguridad, volvé a seleccionar las imágenes antes de publicar.';
            }
            $this->renderizar($rutaBase, $rutaAssets, $usuario, $valores, $errores, $categorias);
            return;
        }

        $rutas = require __DIR__ . '/../vistas/parciales/rutas.php';
        header('Location: ' . $rutas['campana'] . '&id=' . $idCamp, true, 303);
    }

    /** @return list<string> */
    private function validarCampos(array $valores, array $categorias): array
    {
        $errores = [];

        $largoNombre = mb_strlen($valores['nombre'], 'UTF-8');
        if ($largoNombre === 0 || $largoNombre > self::LARGO_NOMBRE) {
            $errores[] = 'El nombre debe tener entre 1 y ' . self::LARGO_NOMBRE . ' caracteres.';
        }
        $largoDescripcion = mb_strlen($valores['descripcion'], 'UTF-8');
        if ($largoDescripcion === 0 || $largoDescripcion > self::LARGO_DESCRIPCION) {
            $errores[] = 'La descripción es obligatoria y puede tener hasta ' . self::LARGO_DESCRIPCION . ' caracteres.';
        }
        if (!ctype_digit($valores['categoria']) || !isset($categorias[(int) $valores['categoria']])) {
            $errores[] = 'Elegí una categoría de la lista.';
        }

        if ($valores['tipo_ayuda'] === 'dinero') {
            $monto = $valores['objetivo'];
            if (!ctype_digit($monto) || (int) $monto < 1 || strlen($monto) > 10 || (int) $monto > self::MONTO_MAXIMO) {
                $errores[] = 'El objetivo a recaudar debe ser un monto entero mayor a cero.';
            }
            if (strlen($valores['cbu']) !== 22 || !ctype_digit($valores['cbu'])) {
                $errores[] = 'El CBU debe tener 22 números.';
            }
        } elseif ($valores['tipo_ayuda'] === 'insumos') {
            $largoInsumo = mb_strlen($valores['insumo'], 'UTF-8');
            if ($largoInsumo === 0 || $largoInsumo > 150) {
                $errores[] = 'Indicá qué insumo necesitás (hasta 150 caracteres).';
            }
        } else {
            $errores[] = 'Elegí si necesitás dinero o insumos.';
        }

        if ($valores['fecha_limite'] !== '') {
            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valores['fecha_limite']);
            if ($fecha === false || $fecha->format('Y-m-d') !== $valores['fecha_limite']) {
                $errores[] = 'La fecha límite no es válida.';
            } elseif ($fecha < new DateTimeImmutable('today')) {
                $errores[] = 'La fecha límite no puede ser anterior a hoy.';
            }
        }

        foreach (['lat' => 90, 'lng' => 180] as $campo => $limite) {
            $valor = $valores[$campo];
            if ($valor !== '' && (!is_numeric($valor) || abs((float) $valor) > $limite)) {
                $errores[] = 'Las coordenadas del mapa no son válidas. Volvé a marcar la ubicación.';
                break;
            }
        }

        return $errores;
    }

    private function seEnviaronImagenes(): bool
    {
        $errores = $_FILES['imagenes']['error'] ?? [];
        foreach ((array) $errores as $error) {
            if (is_int($error) && $error !== UPLOAD_ERR_NO_FILE) {
                return true;
            }
        }
        return false;
    }

    /** @return array<int, string> id => nombre */
    private function listarCategorias(): array
    {
        $categorias = [];
        foreach ($this->categorias->listarParaBuscador() as $fila) {
            $categorias[(int) $fila['id']] = $fila['nombre'];
        }
        return $categorias;
    }

    private function valoresVacios(): array
    {
        $valores = array_fill_keys(self::CAMPOS_TEXTO, '');
        $valores['tipo_ayuda'] = 'dinero';
        return $valores;
    }

    private function renderizar(
        string $rutaBase,
        string $rutaAssets,
        Usuario $usuario,
        array $valores,
        array $errores,
        ?array $categorias = null
    ): void {
        $csrfToken = Csrf::generar();
        $usuarioActual = ['nombre' => $usuario->getNombre(), 'admin' => $usuario->getAdmin()];
        $categorias = $categorias ?? $this->listarCategorias();
        $maxImagenes = ImagenesCampana::MAXIMO_IMAGENES;
        $tamanoMaximoImagen = ImagenesCampana::tamanoMaximoPorImagen();
        $tamanoMaximoTotal = ImagenesCampana::tamanoMaximoTotal();

        $nombre = $valores['nombre'];
        $descripcion = $valores['descripcion'];
        $categoria = $valores['categoria'];
        $tipoAyuda = $valores['tipo_ayuda'];
        $objetivo = $valores['objetivo'];
        $cbu = $valores['cbu'];
        $insumo = $valores['insumo'];
        $fechaLimite = $valores['fecha_limite'];
        $ubicacionTexto = $valores['ubicacion_texto'];
        $lat = $valores['lat'];
        $lng = $valores['lng'];

        require __DIR__ . '/../vistas/crear_campana.php';
    }

    private function redirigirAlIngreso(string $rutaBase): void
    {
        $rutas = require __DIR__ . '/../vistas/parciales/rutas.php';
        header('Location: ' . $rutas['ingresar'], true, 303);
    }
}
