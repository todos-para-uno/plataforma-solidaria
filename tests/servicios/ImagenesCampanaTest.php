<?php
require_once __DIR__ . '/../../app/servicios/ImagenesCampana.php';
require_once __DIR__ . '/../../app/modelos/CampanaImagen.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';

/** No usa la base: crea imágenes temporales con GD y simula las subidas. */
function imagenesCampanaTest(): void
{
    if (!function_exists('imagecreatetruecolor')) {
        echo "OMITIDA: ImagenesCampana necesita la extensión GD para generar imágenes de prueba.\n";
        return;
    }

    $temporal = sys_get_temp_dir() . '/imagenes-campana-' . bin2hex(random_bytes(6));
    $destino = $temporal . '/destino';
    mkdir($temporal, 0700, true);

    $crear = static function (string $nombre, string $tipo) use ($temporal): string {
        $ruta = $temporal . '/' . bin2hex(random_bytes(6)) . '-' . $nombre;
        $imagen = imagecreatetruecolor(40, 30);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 47, 107, 63));
        match ($tipo) {
            'png' => imagepng($imagen, $ruta),
            'jpg' => imagejpeg($imagen, $ruta),
            'webp' => imagewebp($imagen, $ruta),
            'gif' => imagegif($imagen, $ruta),
        };
        return $ruta;
    };
    // Arma la estructura de $_FILES['imagenes'] para un input name="imagenes[]".
    $campo = static function (array $archivos): array {
        $campo = ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];
        foreach ($archivos as [$nombre, $ruta, $error]) {
            $campo['name'][] = $nombre;
            $campo['type'][] = 'image/png'; // lo que diga el navegador no se usa
            $campo['tmp_name'][] = $ruta;
            $campo['error'][] = $error ?? UPLOAD_ERR_OK;
            $campo['size'][] = is_file($ruta) ? filesize($ruta) : 0;
        }
        return $campo;
    };

    $servicio = new ImagenesCampana(
        $destino,
        'subidas/campanas',
        static fn (string $ruta): bool => str_starts_with($ruta, sys_get_temp_dir()),
        static fn (string $origen, string $destino): bool => copy($origen, $destino)
    );

    try {
        $sinCampo = $servicio->validar(null);
        TestUtils::comprobar($sinCampo['archivos'] === [] && $sinCampo['errores'] === [], 'Sin imágenes no hay errores.');

        $vacio = $servicio->validar($campo([['', '', UPLOAD_ERR_NO_FILE]]));
        TestUtils::comprobar($vacio['errores'] === [] && $vacio['archivos'] === [], 'Un input sin archivos elegidos se ignora.');

        $png = $crear('a.png', 'png');
        $jpg = $crear('b.jpg', 'jpg');
        $webp = $crear('c.webp', 'webp');
        $jpeg = $crear('d.jpeg', 'jpg');
        $validas = $servicio->validar($campo([
            ['foto.png', $png, null], ['foto.JPG', $jpg, null], ['foto.webp', $webp, null], ['foto.jpeg', $jpeg, null],
        ]));
        TestUtils::comprobar($validas['errores'] === [] && count($validas['archivos']) === 4, 'Acepta 4 imágenes PNG, JPG, JPEG y WEBP.');
        TestUtils::comprobar(
            array_column($validas['archivos'], 'tipo') === ['image/png', 'image/jpeg', 'image/webp', 'image/jpeg'],
            'El tipo se toma del contenido real.'
        );

        $cinco = $servicio->validar($campo(array_fill(0, 5, ['foto.png', $png, null])));
        TestUtils::comprobar(
            $cinco['archivos'] === [] && str_contains($cinco['errores'][0] ?? '', 'hasta 4'),
            'Rechaza más de 4 imágenes.'
        );

        $texto = $temporal . '/falsa.png';
        file_put_contents($texto, '<?php echo "hola"; ?>');
        $gif = $crear('e.gif', 'gif');
        $casos = [
            ['texto con extensión .png', ['falsa.png', $texto, null], 'no es una imagen'],
            ['GIF con su extensión', ['animada.gif', $gif, null], 'no es un formato permitido'],
            ['GIF renombrado a .png', ['animada.png', $gif, null], 'no es una imagen'],
            ['PNG renombrado a .jpg', ['foto.jpg', $png, null], 'no coincide'],
            ['archivo sin extensión', ['foto', $png, null], 'no es un formato permitido'],
            ['error de tamaño de PHP', ['grande.png', $png, UPLOAD_ERR_INI_SIZE], 'supera el tamaño'],
            ['subida incompleta', ['cortada.png', $png, UPLOAD_ERR_PARTIAL], 'no terminó'],
        ];
        foreach ($casos as [$descripcion, $archivo, $esperado]) {
            $resultado = $servicio->validar($campo([$archivo]));
            TestUtils::comprobar(
                $resultado['archivos'] === [] && str_contains($resultado['errores'][0] ?? '', $esperado),
                "Rechaza $descripcion."
            );
        }

        $grande = $temporal . '/grande.png';
        file_put_contents($grande, str_repeat("\0", ImagenesCampana::tamanoMaximoPorImagen() + 1));
        $resultado = $servicio->validar($campo([['grande.png', $grande, null]]));
        TestUtils::comprobar(str_contains($resultado['errores'][0] ?? '', 'supera el tamaño'), 'Rechaza imágenes más pesadas que el máximo.');

        $mezcla = $servicio->validar($campo([['foto.png', $png, null], ['falsa.png', $texto, null]]));
        TestUtils::comprobar($mezcla['archivos'] === [] && count($mezcla['errores']) === 1, 'Si una imagen falla no se guarda ninguna.');

        $externo = new ImagenesCampana($destino, 'subidas/campanas', static fn (): bool => false);
        $resultado = $externo->validar($campo([['foto.png', $png, null]]));
        TestUtils::comprobar($resultado['archivos'] === [], 'Rechaza archivos que no llegaron por una subida HTTP.');

        $manipulado = $servicio->validar(['name' => [['a.png']], 'tmp_name' => [[$png]], 'error' => [[0]], 'size' => [[1]]]);
        TestUtils::comprobar($manipulado['errores'] === ['Los archivos enviados no son válidos.'], 'Rechaza estructuras de $_FILES manipuladas.');

        $nombreRaro = $servicio->validar($campo([["../../<b>\x07" . str_repeat('x', 100) . '.gif', $gif, null]]));
        TestUtils::comprobar(
            !str_contains($nombreRaro['errores'][0], '..') && !str_contains($nombreRaro['errores'][0], "\x07")
                && mb_strlen($nombreRaro['errores'][0]) < 120,
            'Los mensajes muestran un nombre de archivo corto y limpio.'
        );

        $rutas = $servicio->guardar($validas['archivos']);
        TestUtils::comprobar(count($rutas) === 4, 'Guarda las 4 imágenes.');
        $todasBien = true;
        foreach ($rutas as $indice => $ruta) {
            $esperada = ['png', 'jpg', 'webp', 'jpg'][$indice];
            $archivo = $destino . '/' . basename($ruta);
            $todasBien = $todasBien
                && preg_match('#^subidas/campanas/[a-f0-9]{32}\.' . $esperada . '$#', $ruta) === 1
                && is_file($archivo)
                && getimagesize($archivo) !== false;
        }
        TestUtils::comprobar($todasBien, 'Usa nombres aleatorios con la extensión del tipo real.');
        TestUtils::comprobar(count(array_unique($rutas)) === 4, 'Cada imagen recibe un nombre distinto.');

        $servicio->eliminar(['../config/conexion.php', 'subidas/campanas/../../index.php']);
        TestUtils::comprobar(is_file(__DIR__ . '/../../config/conexion.php'), 'eliminar() ignora rutas fuera de la carpeta de imágenes.');
        $servicio->eliminar($rutas);
        TestUtils::comprobar(glob($destino . '/*') === [], 'eliminar() borra las imágenes guardadas.');

        // El controlador necesita el motivo y la ruta para mostrarlos durante el desarrollo.
        $comprobarFalloDeCarpeta = static function (ImagenesCampana $servicioFallido) use ($validas, $temporal): void {
            $excepcion = null;
            // El caso de mkdir fallido también emite un warning, que se captura solo en la prueba.
            set_error_handler(static fn (int $nivel): bool => $nivel === E_WARNING);
            try {
                $servicioFallido->guardar([$validas['archivos'][0]]);
            } catch (RuntimeException $e) {
                $excepcion = $e;
            } finally {
                restore_error_handler();
            }
            TestUtils::comprobar(
                $excepcion !== null && str_contains($excepcion->getMessage(), 'carpeta de imágenes')
                    && str_contains($excepcion->getMessage(), $temporal),
                'Un destino no disponible genera una excepción con el motivo y la ruta.'
            );
        };

        $bloqueo = $temporal . '/bloqueo';
        file_put_contents($bloqueo, 'Este archivo impide crear una carpeta debajo.');
        $comprobarFalloDeCarpeta(new ImagenesCampana($bloqueo . '/campanas'));

        chmod($destino, 0555);
        clearstatcache(true, $destino);
        try {
            if (!is_writable($destino)) {
                $comprobarFalloDeCarpeta(new ImagenesCampana($destino));
                TestUtils::comprobar(glob($destino . '/*') === [], 'Una carpeta sin escritura no deja imágenes parciales.');
            } else {
                echo "OMITIDA: El usuario actual puede escribir incluso en carpetas con permisos 0555.\n";
            }
        } finally {
            chmod($destino, 0755);
            clearstatcache(true, $destino);
        }

        TestUtils::comprobar(ImagenesCampana::formatearTamano(5 * 1048576) === '5 MB', 'Formatea tamaños enteros.');
        TestUtils::comprobar(ImagenesCampana::formatearTamano(1572864) === '1,5 MB', 'Formatea tamaños con decimales.');

        // Modelo
        $imagen = new CampanaImagen(1, 'subidas/campanas/' . str_repeat('a', 32) . '.webp', 0);
        TestUtils::comprobar($imagen->esPortada(), 'La imagen con orden 0 es la portada.');
        foreach ([[1, 'x.png', 4], [1, '../x.png', 0], [1, '/x.png', 0], [1, 'x.gif', 0], [0, 'x.png', 0]] as [$camp, $ruta, $orden]) {
            $lanzo = false;
            try {
                new CampanaImagen($camp, $ruta, $orden);
            } catch (InvalidArgumentException) {
                $lanzo = true;
            }
            TestUtils::comprobar($lanzo, "CampanaImagen rechaza ($camp, $ruta, $orden).");
        }
    } finally {
        foreach (array_merge(glob($destino . '/*') ?: [], glob($temporal . '/*') ?: []) as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
        @rmdir($destino);
        @rmdir($temporal);
    }
}
