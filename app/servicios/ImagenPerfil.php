<?php
require_once __DIR__ . '/ImagenesSubidas.php';

/** Una foto por cuenta. Solo conserva un WebP de 250 × 250 fuera de public_php. */
final class ImagenPerfil extends ImagenesSubidas
{
    public const MAXIMO_IMAGENES = 1;
    public const TAMANO_MAXIMO_BYTES = 5 * 1024 * 1024;
    public const TIPOS_PERMITIDOS = ['image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    protected const EXTENSIONES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];
    protected const FORMATOS_TEXTO = 'JPG, JPEG o WEBP';
    protected const MAXIMO_PIXELES = 12_000_000;
    protected const LADO_SALIDA = 250;
    protected const TIPO_SALIDA = 'image/webp';
    protected const PERMISOS_ARCHIVO = 0600;
    protected const PERMISOS_CARPETA = 0700;

    public function __construct(?string $directorio = null, ?Closure $esSubida = null)
    {
        parent::__construct($directorio ?? __DIR__ . '/../../data/usuarios', 'usuarios', $esSubida);
    }

    public function validar(mixed $campo): array
    {
        if ($campo !== null && (!is_array($campo) || !is_string($campo['name'] ?? null)
            || !is_string($campo['tmp_name'] ?? null) || !is_int($campo['error'] ?? null)
            || !is_int($campo['size'] ?? null) || str_contains($campo['tmp_name'], "\0"))) {
            return ['archivos' => [], 'errores' => ['Elegí un único archivo de imagen válido.']];
        }
        return parent::validar($campo);
    }

    /** Ruta interna generada por el servidor; nunca acepta URLs ni rutas del navegador. */
    public static function rutaValida(?string $ruta): bool
    {
        return $ruta !== null && preg_match('#^usuarios/[a-f0-9]{32}\.webp$#D', $ruta) === 1;
    }

    public function archivo(?string $ruta): ?string
    {
        if (!self::rutaValida($ruta)) return null;
        $archivo = $this->directorio() . '/' . basename($ruta);
        return is_file($archivo) && !is_link($archivo) ? $archivo : null;
    }
}
