<?php

/**
 * Valida y recodifica subidas HTTP. Las especializaciones definen formatos y límites.
 *
 * La validación no confía en lo que manda el navegador (nombre ni tipo declarado):
 * revisa el contenido real del archivo con finfo y getimagesize.
 * Los archivos se guardan con un nombre aleatorio y siempre se vuelven
 * a codificar: así se descartan los metadatos (por ejemplo, la ubicación GPS de una foto
 * tomada con el celular) y cualquier contenido ajeno a la imagen.
 */
abstract class ImagenesSubidas
{
    public const MAXIMO_IMAGENES = 4;
    protected const FORMATOS_TEXTO = 'PNG, JPG o WEBP';
    protected const LADO_SALIDA = null;
    protected const TIPO_SALIDA = null;
    protected const PERMISOS_ARCHIVO = 0644;
    protected const PERMISOS_CARPETA = 0755;
    /** Tope propio de la aplicación. El efectivo puede ser menor si PHP está configurado más bajo. */
    public const TAMANO_MAXIMO_BYTES = 5 * 1024 * 1024;
    /** Tipo real del contenido => extensión con la que se guarda. */
    public const TIPOS_PERMITIDOS = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];
    /** Extensiones aceptadas en el nombre original, con el tipo que deben tener. */
    protected const EXTENSIONES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];
    /** Evita decodificar imágenes gigantes (por ejemplo, 20.000 x 20.000 px) que agotan la memoria. */
    protected const MAXIMO_PIXELES = 40_000_000;

    private Closure $esSubida;

    public function __construct(
        private string $directorioDestino,
        private string $prefijoRuta = 'subidas/campanas',
        ?Closure $esSubida = null
    ) {
        // Las pruebas sustituyen únicamente la comprobación del origen HTTP.
        $this->esSubida = $esSubida ?? static fn (string $ruta): bool => is_uploaded_file($ruta);
    }

    protected function directorio(): string
    {
        return rtrim($this->directorioDestino, '/\\');
    }

    /** Tamaño máximo por imagen, respetando upload_max_filesize del servidor. */
    public static function tamanoMaximoPorImagen(): int
    {
        $limitePhp = static::aBytes((string) ini_get('upload_max_filesize'));
        return $limitePhp > 0 ? min(static::TAMANO_MAXIMO_BYTES, $limitePhp) : static::TAMANO_MAXIMO_BYTES;
    }

    /**
     * Tamaño máximo de todo el formulario (post_max_size), con margen para los campos de texto.
     * Devuelve 0 si PHP no impone límite.
     */
    public static function tamanoMaximoTotal(): int
    {
        $limite = static::aBytes((string) ini_get('post_max_size'));
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

        if (count($entradas) > static::MAXIMO_IMAGENES) {
            return ['archivos' => [], 'errores' => [sprintf(
                'Podés subir hasta %d imágenes y elegiste %d.',
                static::MAXIMO_IMAGENES,
                count($entradas)
            )]];
        }

        $maximo = static::tamanoMaximoPorImagen();
        $archivos = [];
        $errores = [];
        foreach ($entradas as $entrada) {
            $nombre = $this->nombreVisible($entrada['name']);
            $resultado = $this->validarArchivo($entrada, $nombre, $maximo);
            if (!isset(static::TIPOS_PERMITIDOS[$resultado])) {
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
        if (!is_dir($this->directorioDestino) && !mkdir($this->directorioDestino, static::PERMISOS_CARPETA, true) && !is_dir($this->directorioDestino)) {
            throw new RuntimeException('No se pudo crear la carpeta de imágenes: ' . $this->directorioDestino);
        }
        if (!is_writable($this->directorioDestino)) {
            throw new RuntimeException('PHP no tiene permiso de escritura en la carpeta de imágenes: ' . $this->directorioDestino);
        }

        $guardadas = [];
        try {
            foreach ($archivos as $archivo) {
                $tipoSalida = static::TIPO_SALIDA ?? $archivo['tipo'];
                $extension = static::TIPOS_PERMITIDOS[$tipoSalida];
                $nombreFinal = bin2hex(random_bytes(16)) . '.' . $extension;
                $destino = rtrim($this->directorioDestino, '/\\') . DIRECTORY_SEPARATOR . $nombreFinal;
                // Nunca sobrescribir un archivo existente ni seguir enlaces simbólicos.
                $salida = @fopen($destino, 'xb');
                if ($salida === false) throw new RuntimeException('No se pudo crear la imagen procesada.');
                $guardadas[] = trim($this->prefijoRuta, '/') . '/' . $nombreFinal;
                try {
                    if (!@chmod($destino, static::PERMISOS_ARCHIVO)
                        || !$this->recodificar($archivo['tmp'], $archivo['tipo'], $salida)) {
                        throw new RuntimeException('No se pudo procesar la imagen. El archivo original no se guardó.');
                    }
                } finally {
                    fclose($salida);
                }
                clearstatcache(true, $destino);
                $datos = @getimagesize($destino);
                if (!$datos || ($datos['mime'] ?? '') !== $tipoSalida || filesize($destino) < 1
                    || (static::LADO_SALIDA !== null && ($datos[0] !== static::LADO_SALIDA || $datos[1] !== static::LADO_SALIDA))) {
                    throw new RuntimeException('No se pudo verificar la imagen procesada.');
                }
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
            if ($ruta === $prefijo . $archivo && preg_match('/^[a-f0-9]{32}\.(png|jpg|webp)$/D', $archivo)) {
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
        $tamanoTexto = static::formatearTamano($maximo);
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
        if (!isset(static::EXTENSIONES[$extension])) {
            return "“{$nombre}” no es un formato permitido. Usá " . static::FORMATOS_TEXTO . ".";
        }

        // El tipo se decide por el contenido, no por la extensión ni por lo que declara el navegador.
        $tipoReal = (new finfo(FILEINFO_MIME_TYPE))->file($entrada['tmp_name']);
        if (!is_string($tipoReal) || !isset(static::TIPOS_PERMITIDOS[$tipoReal])) {
            return "“{$nombre}” no es una imagen " . static::FORMATOS_TEXTO . " válida.";
        }
        if (static::EXTENSIONES[$extension] !== $tipoReal) {
            return "La extensión de “{$nombre}” no coincide con su contenido.";
        }

        $datos = @getimagesize($entrada['tmp_name']);
        if ($datos === false || ($datos['mime'] ?? '') !== $tipoReal || $datos[0] < 1 || $datos[1] < 1) {
            return "“{$nombre}” está dañado o no es una imagen.";
        }
        if ($datos[0] * $datos[1] > static::MAXIMO_PIXELES) {
            return "“{$nombre}” tiene una resolución demasiado grande.";
        }

        // Presupuesto conservador para decodificar, orientar y redimensionar.
        // El límite absoluto también protege instalaciones con memory_limit=-1 o GD externo.
        $necesaria = $datos[0] * $datos[1] * 12 + 16 * 1024 * 1024;
        $limite = static::aBytes((string) ini_get('memory_limit'));
        $disponible = $limite > 0 ? $limite - memory_get_usage(true) : PHP_INT_MAX;
        if ($datos[0] > 12000 || $datos[1] > 12000 || $necesaria > min(256 * 1024 * 1024, $disponible)) {
            return "“{$nombre}” tiene una resolución demasiado grande para procesarla.";
        }
        return $tipoReal;
    }

    /** Recodificación obligatoria. Nunca se copia el original, incluso si falta GD o EXIF. */
    private function recodificar(string $origen, string $tipo, mixed $salida): bool
    {
        if (!($this->esSubida)($origen)) return false;
        $lectores = ['image/png' => 'imagecreatefrompng', 'image/jpeg' => 'imagecreatefromjpeg', 'image/webp' => 'imagecreatefromwebp'];
        $escritores = ['image/png' => 'imagepng', 'image/jpeg' => 'imagejpeg', 'image/webp' => 'imagewebp'];
        $tipoSalida = static::TIPO_SALIDA ?? $tipo;
        if (!isset($lectores[$tipo], $escritores[$tipoSalida])
            || !function_exists($lectores[$tipo]) || !function_exists($escritores[$tipoSalida])
            || ($tipo === 'image/jpeg' && !function_exists('exif_read_data'))) return false;

        $imagen = @$lectores[$tipo]($origen);
        if ($imagen === false) return false;
        if ($tipo === 'image/jpeg') $imagen = $this->corregirOrientacion($imagen, $origen);
        if ($imagen === false) return false;
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);
        if (static::LADO_SALIDA !== null) {
            // Recorte central cuadrado, sin deformar la foto.
            $lado = min(imagesx($imagen), imagesy($imagen));
            $miniatura = imagecreatetruecolor(static::LADO_SALIDA, static::LADO_SALIDA);
            if ($miniatura === false) return false;
            imagealphablending($miniatura, false);
            imagesavealpha($miniatura, true);
            if (!imagecopyresampled($miniatura, $imagen, 0, 0,
                (int) ((imagesx($imagen) - $lado) / 2), (int) ((imagesy($imagen) - $lado) / 2),
                static::LADO_SALIDA, static::LADO_SALIDA, $lado, $lado)) return false;
            $imagen = $miniatura;
        }
        // Un lienzo nuevo y el codificador de GD no copian EXIF, GPS ni otros metadatos del original.
        return match ($tipoSalida) {
            'image/png' => @imagepng($imagen, $salida, 6),
            'image/jpeg' => @imagejpeg($imagen, $salida, 85),
            'image/webp' => @imagewebp($imagen, $salida, 85),
        };
    }

    private function corregirOrientacion(GdImage $imagen, string $origen): GdImage|false
    {
        $exif = @exif_read_data($origen);
        $orientacion = (int) ($exif['Orientation'] ?? 1);
        // Incluye las orientaciones reflejadas de EXIF, no solo las rotaciones.
        if (in_array($orientacion, [2, 4, 5, 7], true) && !imageflip($imagen, IMG_FLIP_HORIZONTAL)) return false;
        $grados = match ($orientacion) {
            3, 4 => 180,
            6, 7 => -90,
            5, 8 => 90,
            default => 0,
        };
        return $grados === 0 ? $imagen : imagerotate($imagen, $grados, 0);
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
