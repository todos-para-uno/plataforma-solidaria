<?php
require_once __DIR__ . '/../../app/servicios/ImagenPerfil.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

function imagenPerfilTest(): void
{
    if (!function_exists('imagejpeg') || !function_exists('exif_read_data')) {
        throw new RuntimeException('Estas pruebas requieren GD con JPEG/WebP y EXIF.');
    }
    $tmp = sys_get_temp_dir() . '/avatar-test-' . bin2hex(random_bytes(8));
    mkdir($tmp, 0700);
    $destino = $tmp . '/salida';
    $servicio = new ImagenPerfil($destino, static fn(string $ruta): bool => is_file($ruta));
    $campo = static fn(string $nombre, string $ruta, int $error = UPLOAD_ERR_OK): array => [
        'name' => $nombre, 'tmp_name' => $ruta, 'error' => $error,
        'size' => is_file($ruta) ? filesize($ruta) : 0, 'type' => 'application/octet-stream',
    ];
    try {
        $imagen = imagecreatetruecolor(400, 200);
        imagefilledrectangle($imagen, 0, 0, 199, 199, imagecolorallocate($imagen, 255, 0, 0));
        imagefilledrectangle($imagen, 200, 0, 399, 199, imagecolorallocate($imagen, 0, 0, 255));
        $jpg = $tmp . '/entrada.jpg'; imagejpeg($imagen, $jpg, 95);
        $tieneWebp = function_exists('imagewebp') && !empty(gd_info()['WebP Support']);
        if ($tieneWebp) { $webp = $tmp . '/entrada.webp'; imagewebp($imagen, $webp); }
        $png = $tmp . '/entrada.png'; imagepng($imagen, $png);
        $falso = $tmp . '/falso.jpg'; file_put_contents($falso, '<?php echo "ataque"; ?>');
        TestUtils::comprobar($servicio->validar(null)['errores'] === [], 'La foto de perfil es opcional');
        foreach (['foto.jpg', 'foto.jpeg', 'foto.JPG'] as $nombre) {
            $validada = $servicio->validar($campo($nombre, $jpg));
            TestUtils::comprobar($validada['errores'] === [], "Acepta $nombre por su contenido real, ignorando el MIME declarado");
        }
        $rutas = $servicio->guardar($servicio->validar($campo('foto.jpg', $jpg))['archivos']);
        $salida = $servicio->archivo($rutas[0]);
        $datos = getimagesize($salida);
        $tipoEsperado = $tieneWebp ? 'image/webp' : 'image/jpeg';
        TestUtils::comprobar($datos[0] === 250 && $datos[1] === 250 && $datos['mime'] === $tipoEsperado, 'Genera imagen de 250 × 250 en formato compatible');
        TestUtils::comprobar((fileperms($salida) & 0777) === 0600 && ImagenPerfil::rutaValida($rutas[0]), 'Guarda el avatar con nombre aleatorio y permisos privados');
        foreach ([['foto.png', $png], ['foto.jpg', $png], ['foto.jpg', $falso], ['foto.php.jpg', $falso], ['foto.svg', $jpg], ['foto.gif', $jpg]] as [$nombre, $ruta]) {
            TestUtils::comprobar($servicio->validar($campo($nombre, $ruta))['archivos'] === [], "Rechaza contenido o formato inválido: $nombre");
        }
        $manipulado = $campo('foto.jpg', $jpg); $manipulado['name'] = ['foto.jpg'];
        TestUtils::comprobar($servicio->validar($manipulado)['errores'] !== [], 'Rechaza múltiples archivos o campos anidados');
        TestUtils::comprobar((new ImagenPerfil($destino))->validar($campo('foto.jpg', $jpg))['errores'] !== [], 'Rechaza un archivo que no provino de una subida HTTP');
        TestUtils::comprobar($servicio->validar($campo('foto.jpg', $jpg, UPLOAD_ERR_PARTIAL))['errores'] !== [], 'Rechaza subidas incompletas');
        $grande = $tmp . '/grande.jpg'; file_put_contents($grande, str_repeat('x', ImagenPerfil::tamanoMaximoPorImagen() + 1));
        TestUtils::comprobar($servicio->validar($campo('grande.jpg', $grande))['errores'] !== [], 'Rechaza archivos que exceden el tamaño máximo real');
        // Una cabecera JPEG declara dimensiones enormes con un archivo pequeño.
        $enorme = preg_replace('/\xFF\xC0\x00\x11\x08.... /sx', "\xFF\xC0\x00\x11\x08" . pack('nn', 6000, 6000), file_get_contents($jpg), 1);
        file_put_contents($tmp . '/enorme.jpg', $enorme);
        TestUtils::comprobar($servicio->validar($campo('enorme.jpg', $tmp . '/enorme.jpg'))['errores'] !== [], 'Rechaza resoluciones excesivas antes de decodificar');
        // EXIF Orientation=6 (90° horario) y datos privados añadidos al original.
        $tiff = "II\x2a\x00\x08\x00\x00\x00\x01\x00\x12\x01\x03\x00\x01\x00\x00\x00\x06\x00\x00\x00\x00\x00\x00\x00";
        $exif = "Exif\0\0" . $tiff;
        $original = file_get_contents($jpg);
        $orientada = $tmp . '/orientada.jpg';
        file_put_contents($orientada, substr($original, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($original, 2) . 'PRIVATE-GPS-METADATA');
        $procesada = $servicio->guardar($servicio->validar($campo('foto.jpeg', $orientada))['archivos']);
        $foto = $tieneWebp ? imagecreatefromwebp($servicio->archivo($procesada[0])) : imagecreatefromjpeg($servicio->archivo($procesada[0]));
        $arriba = imagecolorsforindex($foto, imagecolorat($foto, 125, 20));
        $abajo = imagecolorsforindex($foto, imagecolorat($foto, 125, 230));
        TestUtils::comprobar($arriba['red'] > 200 && $abajo['blue'] > 200, 'Corrige la orientación EXIF antes del recorte central');
        TestUtils::comprobar(!str_contains(file_get_contents($servicio->archivo($procesada[0])), 'PRIVATE-GPS-METADATA'), 'No conserva datos anexos del archivo original');
        // Parece JPEG para finfo/getimagesize, pero no puede decodificarse.
        $danada = $tmp . '/danada.jpg';
        file_put_contents($danada, substr($original, 0, strpos($original, "\xFF\xDA")) . "\xFF\xD9");
        $validada = $servicio->validar($campo('danada.jpg', $danada));
        TestUtils::comprobar($validada['errores'] === [], 'La cabecera de prueba pasa los controles iniciales');
        $antes = glob($destino . '/*');
        try { $servicio->guardar($validada['archivos']); throw new LogicException('Aceptó una imagen que no puede recodificar.'); }
        catch (RuntimeException) {}
        TestUtils::comprobar(glob($destino . '/*') === $antes, 'Una recodificación fallida no guarda el original ni deja archivos parciales');
        foreach (['../entrada.jpg', 'usuarios/../../entrada.jpg', 'https://example.com/a.jpg'] as $ruta) {
            TestUtils::comprobar($servicio->archivo($ruta) === null, 'Rechaza rutas ajenas al almacenamiento privado');
        }
        $servicio->eliminar(['usuarios/../' . basename($rutas[0])]);
        TestUtils::comprobar(is_file($salida), 'La eliminación exige una ruta generada exacta');
        // Sin WebP, la foto debe poder guardarse como JPEG (en este entorno o en un proceso aparte).
        if (!$tieneWebp) {
            TestUtils::comprobar(str_ends_with($rutas[0], '.jpg'), 'Sin WebP utiliza extensión JPEG');
        }
    } finally {
        foreach (glob($tmp . '/*') ?: [] as $ruta) {
            if (is_dir($ruta)) { foreach (glob($ruta . '/*') ?: [] as $archivo) unlink($archivo); rmdir($ruta); }
            else unlink($ruta);
        }
        rmdir($tmp);
    }
}
