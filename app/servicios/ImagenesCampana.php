<?php
require_once __DIR__ . '/../modelos/CampanaImagen.php';

/**
 * Valida y guarda las imágenes que llegan desde el formulario de crear campaña.
 *
 * La validación no confía en lo que manda el navegador (nombre ni tipo declarado):
 * revisa el contenido real del archivo con finfo y getimagesize.
 * Los archivos se guardan con un nombre aleatorio y, si GD está disponible, se vuelven
 * a codificar: así se descartan los metadatos (por ejemplo, la ubicación GPS de una foto
 * tomada con el celular) y cualquier contenido ajeno a la imagen.
 */
final class ImagenesCampana
{
    public const MAXIMO_IMAGENES = CampanaImagen::MAXIMO_POR_CAMPANA;
    /** Tope propio de la aplicación. El efectivo puede ser menor si PHP está configurado más bajo. */
    public const TAMANO_MAXIMO_BYTES = 5 * 1024 * 1024;
    /** Tipo real del contenido => extensión con la que se guarda. */
    public const TIPOS_PERMITIDOS = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];
    /** Extensiones aceptadas en el nombre original, con el tipo que deben tener. */
    private const EXTENSIONES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];
    /** Evita decodificar imágenes gigantes (por ejemplo, 20.000 x 20.000 px) que agotan la memoria. */
    private const MAXIMO_PIXELES = 40_000_000;

    private Closure $esSubida;
    private Closure $mover;

    public function __construct(
        private string $directorioDestino,
        private string $prefijoRuta = 'subidas/campanas',
        ?Closure $esSubida = null,
        ?Closure $mover = null
    ) {
        // Se pueden sustituir en las pruebas: move_uploaded_file solo funciona con subidas reales.
        $this->esSubida = $esSubida ?? static fn (string $ruta): bool => is_uploaded_file($ruta);
        $this->mover = $mover ?? static fn (string $origen, string $destino): bool => move_uploaded_file($origen, $destino);
    }

    /** Tamaño máximo por imagen, respetando upload_max_filesize del servidor. */
    public static function tamanoMaximoPorImagen(): int
    {
        $limitePhp = self::aBytes((string) ini_get('upload_max_filesize'));
        return $limitePhp > 0 ? min(self::TAMANO_MAXIMO_BYTES, $limitePhp) : self::TAMANO_MAXIMO_BYTES;
    }

    /**
     * Tamaño máximo de todo el formulario (post_max_size), con margen para los campos de texto.
     * Devuelve 0 si PHP no impone límite.
     */
    public static function tamanoMaximoTotal(): int
    {
        $limite = self::aBytes((string) ini_get('post_max_size'));
        return $limite > 0 ? max(0, $limite - 64 * 1024) : 0;
    }

    /** Ejemplo: 5242880 => "5 MB"; 1572864 => "1,5 MB". */
    public static function formatearTamano(int $bytes): string
    {
        $mb = $bytes / 1048576;
        return ($mb == floor($mb) ? (string) (int) $mb : number_format($mb, 1, ',', '')) . ' MB';
    }

    /**
     * Revisa el campo de archivos tal como llega en $_FILES['imagenes'].
     *
     * @return array{archivos: list<array{tmp: string, nombre: string, tipo: string}>, errores: list<string>}
     */
    public function validar(mixed $campo): array
    {
        $entradas = $this->normalizar($campo);
        if ($entradas === null) {
            return ['archivos' => [], 'errores' => ['Los archivos enviados no son válidos.']];
        }

        // Un input sin archivos elegidos llega como una entrada con UPLOAD_ERR_NO_FILE.
        $entradas = array_values(array_filter(
            $entradas,
            static fn (array $entrada): bool => $entrada['error'] !== UPLOAD_ERR_NO_FILE
        ));

        if (count($entradas) > self::MAXIMO_IMAGENES) {
            return ['archivos' => [], 'errores' => [sprintf(
                'Podés subir hasta %d imágenes por campaña y elegiste %d.',
                self::MAXIMO_IMAGENES,
                count($entradas)
            )]];
        }

        $maximo = self::tamanoMaximoPorImagen();
        $archivos = [];
        $errores = [];
        foreach ($entradas as $entrada) {
            $nombre = $this->nombreVisible($entrada['name']);
            $resultado = $this->validarArchivo($entrada, $nombre, $maximo);
            if (!isset(self::TIPOS_PERMITIDOS[$resultado])) {
                $errores[] = $resultado;
                continue;
            }
            $archivos[] = ['tmp' => $entrada['tmp_name'], 'nombre' => $nombre, 'tipo' => $resultado];
        }

        return ['archivos' => $errores === [] ? $archivos : [], 'errores' => $errores];
    }

    /**
     * Guarda los archivos ya validados y devuelve sus rutas relativas, en el mismo orden.
     * Si uno falla, borra los que ya se habían guardado y lanza la excepción.
     *
     * @param list<array{tmp: string, nombre: string, tipo: string}> $archivos
     * @return list<string>
     */
    public function guardar(array $archivos): array
    {
        if ($archivos === []) {
            return [];
        }
        if (!is_dir($this->directorioDestino) && !mkdir($this->directorioDestino, 0755, true) && !is_dir($this->directorioDestino)) {
            throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
        }

        $guardadas = [];
        try {
            foreach ($archivos as $archivo) {
                $extension = self::TIPOS_PERMITIDOS[$archivo['tipo']];
                $nombreFinal = bin2hex(random_bytes(16)) . '.' . $extension;
                $destino = rtrim($this->directorioDestino, '/\\') . DIRECTORY_SEPARATOR . $nombreFinal;

                if (!$this->recodificar($archivo['tmp'], $archivo['tipo'], $destino)
                    && !($this->mover)($archivo['tmp'], $destino)) {
                    throw new RuntimeException('No se pudo guardar la imagen.');
                }
                @chmod($destino, 0644);
                $guardadas[] = trim($this->prefijoRuta, '/') . '/' . $nombreFinal;
            }
        } catch (Throwable $excepcion) {
            $this->eliminar($guardadas);
            throw $excepcion;
        }

        return $guardadas;
    }

    /** Borra archivos guardados por esta clase. Ignora rutas ajenas a la carpeta de destino. */
    public function eliminar(array $rutas): void
    {
        $prefijo = trim($this->prefijoRuta, '/') . '/';
        foreach ($rutas as $ruta) {
            if (!is_string($ruta) || !str_starts_with($ruta, $prefijo)) {
                continue;
            }
            $archivo = basename($ruta);
            if (preg_match('/^[a-f0-9]{32}\.(png|jpg|webp)$/', $archivo)) {
                @unlink(rtrim($this->directorioDestino, '/\\') . DIRECTORY_SEPARATOR . $archivo);
            }
        }
    }

    /**
     * Convierte la estructura de $_FILES (name[], tmp_name[], ...) en una lista de archivos.
     * Devuelve null si la estructura fue manipulada.
     *
     * @return list<array{name: string, tmp_name: string, error: int, size: int}>|null
     */
    private function normalizar(mixed $campo): ?array
    {
        if ($campo === null) {
            return [];
        }
        if (!is_array($campo) || !isset($campo['name'], $campo['tmp_name'], $campo['error'], $campo['size'])) {
            return null;
        }

        $claves = ['name', 'tmp_name', 'error', 'size'];
        if (!is_array($campo['name'])) {
            $campo = array_map(static fn ($valor) => [$valor], array_intersect_key($campo, array_flip($claves)));
        }

        $entradas = [];
        foreach (array_keys($campo['name']) as $indice) {
            $entrada = [];
            foreach ($claves as $clave) {
                $valor = $campo[$clave][$indice] ?? null;
                // Un nivel más de anidamiento (imagenes[a][b]) no es algo que mande nuestro formulario.
                if (!is_scalar($valor)) {
                    return null;
                }
                $entrada[$clave] = $valor;
            }
            $entradas[] = [
                'name' => (string) $entrada['name'],
                'tmp_name' => (string) $entrada['tmp_name'],
                'error' => (int) $entrada['error'],
                'size' => (int) $entrada['size'],
            ];
        }

        return $entradas;
    }

    /** Devuelve el tipo real (una clave de TIPOS_PERMITIDOS) si es válido, o el mensaje de error. */
    private function validarArchivo(array $entrada, string $nombre, int $maximo): string
    {
        $tamanoTexto = self::formatearTamano($maximo);
        switch ($entrada['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "“{$nombre}” supera el tamaño máximo de {$tamanoTexto}.";
            case UPLOAD_ERR_PARTIAL:
                return "“{$nombre}” no terminó de subirse. Probá de nuevo.";
            default:
                return "No pudimos recibir “{$nombre}”. Probá de nuevo.";
        }

        if (!($this->esSubida)($entrada['tmp_name']) || !is_file($entrada['tmp_name'])) {
            return "No pudimos recibir “{$nombre}”. Probá de nuevo.";
        }

        $tamano = filesize($entrada['tmp_name']);
        if ($tamano === false || $tamano === 0) {
            return "“{$nombre}” está vacío.";
        }
        if ($tamano > $maximo) {
            return "“{$nombre}” supera el tamaño máximo de {$tamanoTexto}.";
        }

        $extension = strtolower(pathinfo($entrada['name'], PATHINFO_EXTENSION));
        if (!isset(self::EXTENSIONES[$extension])) {
            return "“{$nombre}” no es un formato permitido. Usá PNG, JPG o WEBP.";
        }

        // El tipo se decide por el contenido, no por la extensión ni por lo que declara el navegador.
        $tipoReal = (new finfo(FILEINFO_MIME_TYPE))->file($entrada['tmp_name']);
        if (!is_string($tipoReal) || !isset(self::TIPOS_PERMITIDOS[$tipoReal])) {
            return "“{$nombre}” no es una imagen PNG, JPG o WEBP válida.";
        }
        if (self::EXTENSIONES[$extension] !== $tipoReal) {
            return "La extensión de “{$nombre}” no coincide con su contenido.";
        }

        $datos = @getimagesize($entrada['tmp_name']);
        if ($datos === false || ($datos['mime'] ?? '') !== $tipoReal || $datos[0] < 1 || $datos[1] < 1) {
            return "“{$nombre}” está dañado o no es una imagen.";
        }
        if ($datos[0] * $datos[1] > self::MAXIMO_PIXELES) {
            return "“{$nombre}” tiene una resolución demasiado grande.";
        }

        return $tipoReal;
    }

    /**
     * Vuelve a codificar la imagen con GD para quitar metadatos.
     * Devuelve false si GD no puede con este archivo; en ese caso se guarda el original.
     */
    private function recodificar(string $origen, string $tipo, string $destino): bool
    {
        if (!($this->esSubida)($origen)) {
            return false;
        }
        $lectores = ['image/png' => 'imagecreatefrompng', 'image/jpeg' => 'imagecreatefromjpeg', 'image/webp' => 'imagecreatefromwebp'];
        $escritores = ['image/png' => 'imagepng', 'image/jpeg' => 'imagejpeg', 'image/webp' => 'imagewebp'];
        if (!function_exists($lectores[$tipo]) || !function_exists($escritores[$tipo])) {
            return false;
        }
        // Sin exif no se puede leer la orientación de una foto JPG del celular:
        // recodificarla la dejaría girada, así que se guarda tal cual.
        if ($tipo === 'image/jpeg' && !function_exists('exif_read_data')) {
            return false;
        }

        $imagen = @$lectores[$tipo]($origen);
        if ($imagen === false) {
            // Por ejemplo, un WEBP animado: GD no lo abre, pero ya se validó que es una imagen.
            return false;
        }

        // Desde PHP 8 la memoria de GdImage se libera sola: no hace falta imagedestroy().
        if ($tipo === 'image/jpeg') {
            $imagen = $this->corregirOrientacion($imagen, $origen);
        } else {
            imagealphablending($imagen, false);
            imagesavealpha($imagen, true);
        }
        $resultado = match ($tipo) {
            'image/png' => imagepng($imagen, $destino, 6),
            'image/jpeg' => imagejpeg($imagen, $destino, 85),
            'image/webp' => imagewebp($imagen, $destino, 85),
        };

        if (!$resultado) {
            @unlink($destino);
            return false;
        }
        return true;
    }

    private function corregirOrientacion(GdImage $imagen, string $origen): GdImage
    {
        $exif = @exif_read_data($origen);
        $grados = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($grados === 0) {
            return $imagen;
        }
        $rotada = imagerotate($imagen, $grados, 0);
        return $rotada === false ? $imagen : $rotada;
    }

    /** Nombre corto y sin caracteres de control para los mensajes de error. */
    private function nombreVisible(string $nombre): string
    {
        $nombre = preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $nombre))) ?? '';
        if ($nombre === '' || !mb_check_encoding($nombre, 'UTF-8')) {
            return 'el archivo';
        }
        return mb_strimwidth($nombre, 0, 60, '…', 'UTF-8');
    }

    /** Convierte valores de php.ini como "8M" o "2G" a bytes. */
    private static function aBytes(string $valor): int
    {
        $valor = trim($valor);
        if ($valor === '') {
            return 0;
        }
        $numero = (int) $valor;
        return match (strtolower(substr($valor, -1))) {
            'g' => $numero * 1024 ** 3,
            'm' => $numero * 1024 ** 2,
            'k' => $numero * 1024,
            default => $numero,
        };
    }
}
