<?php
require_once __DIR__ . '/../../app/modelos/Usuarios.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';



function usuariosTest(){
    $usuarioNuevo = Usuario::crear(
        'Ana',
        'ana@example.com',
        'MiPasswordSegura123'
    );
    $filaUsuario = [
        'id' => 5,
        'nombre' => $usuarioNuevo->getNombre(),
        'mail' => $usuarioNuevo->getEmail(),
        'pass' => $usuarioNuevo->getPassword(),
        'telefono' => null,
        'tipo_documento' => null,
        'documento' => null,
        'verificado' => 1,
        'admin' => 0,
        'validacion' => 3
    ];

    $usuarioRecuperado = Usuario::desdePersistencia($filaUsuario);
    TestUtils::comprobar(
        $usuarioRecuperado->getId() === 5,
        'Se recuperó correctamente el ID' . eol()
    );

    TestUtils::comprobar(
        $usuarioRecuperado->verificarPassword('MiPasswordSegura123'),
        'La contraseña se verifica correctamente' . eol()
    );

    TestUtils::comprobar(
        $usuarioRecuperado->getPassword() === $usuarioNuevo->getPassword(),
        'El hash original se conservó sin modificaciones' . eol()
    );

    echo "Prueba para /../../app/modelos/Usuarios.php completada correctamente." . eol();
    echo '<hr>';
}

// Permite ejecutar este test solo y también incluirlo desde un ejecutor de pruebas.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    usuariosTest();
}

//UsuariosTest propuesto por Lakhezis, tome el consejo de realpath.
//Lo dejo aqui documentado porque puede haber mas cosas interesantes
//para tener en cuenta!.
/* 
<?php
require_once __DIR__ . '/../../app/modelos/Usuarios.php';

function usuariosComprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException('FALLÓ: ' . $mensaje);
    }
    echo 'OK: ' . $mensaje . PHP_EOL;
}

function usuariosConstructorInvalido(string $nombre, string $correo, string $clave): void
{
    try {
        new Usuarios($nombre, $correo, $clave);
    } catch (InvalidArgumentException $excepcion) {
        return;
    }
    throw new RuntimeException('FALLÓ: el constructor aceptó datos inválidos.');
}

function usuariosConstructorTest(): void
{
    $usuario = new Usuarios('  Lola Pérez  ', '  Lola.Perez@example.com  ', 'clave-segura');
    usuariosComprobar($usuario->getNombre() === 'Lola Pérez', 'El constructor recorta el nombre.');
    usuariosComprobar($usuario->getEmail() === 'Lola.Perez@example.com', 'El constructor recorta el correo sin cambiar sus letras.');
    usuariosComprobar($usuario->getPassword() !== 'clave-segura'
        && password_verify('clave-segura', $usuario->getPassword()), 'La contraseña se guarda como hash verificable.');
    usuariosComprobar($usuario->getTelefono() === null
        && $usuario->getTipoDocumento() === null
        && $usuario->getDocumento() === null, 'Los datos opcionales siguen admitiendo null.');
    usuariosComprobar($usuario->getVerificado() === false
        && $usuario->getAdmin() === false
        && $usuario->getValidacion() === 1
        && $usuario->getId() === null, 'Se conservan los estados iniciales del usuario.');

    // Los límites se prueban con tildes para contar caracteres, no bytes.
    $nombreLimite = str_repeat('á', 100);
    $correoLimite = str_repeat('a', 64) . '@' . str_repeat('b', 63) . '.' . str_repeat('c', 17) . '.com';
    $correoLargo = str_repeat('a', 64) . '@' . str_repeat('b', 63) . '.' . str_repeat('c', 18) . '.com';
    $usuarioLimite = new Usuarios('  ' . $nombreLimite . '  ', '  ' . $correoLimite . '  ', '12345678');
    usuariosComprobar($usuarioLimite->getNombre() === $nombreLimite
        && $usuarioLimite->getEmail() === $correoLimite
        && password_verify('12345678', $usuarioLimite->getPassword()), 'El constructor acepta los límites de nombre, correo y contraseña.');

    $nombresInvalidos = ['', '   ', str_repeat('á', 101)];
    foreach ($nombresInvalidos as $nombre) {
        usuariosConstructorInvalido($nombre, 'lola@example.com', 'clave-segura');
    }
    usuariosComprobar(true, 'El constructor rechaza nombres vacíos y de más de 100 caracteres.');

    $correosInvalidos = ['', '   ', 'correo-invalido', 'lola@', 'lola perez@example.com', $correoLargo];
    foreach ($correosInvalidos as $correo) {
        usuariosConstructorInvalido('Lola', $correo, 'clave-segura');
    }
    usuariosComprobar(true, 'El constructor rechaza correos vacíos, inválidos y de más de 150 caracteres.');

    $clavesInvalidas = ['', '        ', '1234567', str_repeat('á', 4), str_repeat('a', 73), str_repeat('á', 37), "clave\0segura"];
    foreach ($clavesInvalidas as $clave) {
        usuariosConstructorInvalido('Lola', 'lola@example.com', $clave);
    }
    usuariosComprobar(true, 'El constructor rechaza contraseñas vacías, cortas, de más de 72 bytes o con bytes nulos.');
}

function usuariosNombreTest(): void
{
    $usuario = new Usuarios('Lola', 'lola@example.com', 'clave-segura');
    $nombreLimite = str_repeat('á', 100);
    $nombresInvalidos = ['', '   ', str_repeat('á', 101)];
    $usuario->setNombre('  Pepe López  ');
    usuariosComprobar($usuario->getNombre() === 'Pepe López', 'El setter recorta el nombre.');
    $usuario->setNombre('  ' . $nombreLimite . '  ');
    usuariosComprobar($usuario->getNombre() === $nombreLimite, 'El setter admite 100 caracteres con tildes.');
    foreach ($nombresInvalidos as $nombre) {
        $rechazado = false;
        try {
            $usuario->setNombre($nombre);
        } catch (InvalidArgumentException $excepcion) {
            $rechazado = true;
        }
        usuariosComprobar($rechazado && $usuario->getNombre() === $nombreLimite, 'Un nombre inválido no reemplaza el nombre anterior.');
    }
}

function usuariosCorreoTest(): void
{
    $usuario = new Usuarios('Lola', 'lola@example.com', 'clave-segura');
    $correoLimite = str_repeat('a', 64) . '@' . str_repeat('b', 63) . '.' . str_repeat('c', 17) . '.com';
    $correoLargo = str_repeat('a', 64) . '@' . str_repeat('b', 63) . '.' . str_repeat('c', 18) . '.com';
    $correosInvalidos = ['', '   ', 'correo-invalido', 'lola@', 'lola perez@example.com', $correoLargo];
    $usuario->setEmail('  pepe@example.com  ');
    usuariosComprobar($usuario->getEmail() === 'pepe@example.com', 'El setter recorta el correo.');
    $usuario->setEmail('  ' . $correoLimite . '  ');
    usuariosComprobar($usuario->getEmail() === $correoLimite, 'El setter admite un correo válido de 150 caracteres.');
    foreach ($correosInvalidos as $correo) {
        $rechazado = false;
        try {
            $usuario->setEmail($correo);
        } catch (InvalidArgumentException $excepcion) {
            $rechazado = true;
        }
        usuariosComprobar($rechazado && $usuario->getEmail() === $correoLimite, 'Un correo inválido no reemplaza el correo anterior.');
    }
}

function usuariosPasswordTest(): void
{
    $usuario = new Usuarios('Lola', 'lola@example.com', 'clave-segura');
    $clavesInvalidas = ['', '        ', '1234567', str_repeat('á', 4), str_repeat('a', 73), str_repeat('á', 37), "clave\0segura"];
    $hashAnterior = $usuario->getPassword();
    $usuario->setPassword('  otra clave segura  ');
    usuariosComprobar($usuario->getPassword() !== $hashAnterior
        && password_verify('  otra clave segura  ', $usuario->getPassword())
        && !password_verify('otra clave segura', $usuario->getPassword()), 'El setter cambia el hash y conserva los espacios de la contraseña.');
    foreach (['12345678', str_repeat('a', 72), str_repeat('á', 36)] as $clave) {
        $usuario->setPassword($clave);
        usuariosComprobar(password_verify($clave, $usuario->getPassword()), 'El setter admite los límites de caracteres y bytes de la contraseña.');
    }
    $hashAnterior = $usuario->getPassword();
    foreach ($clavesInvalidas as $clave) {
        $rechazado = false;
        try {
            $usuario->setPassword($clave);
        } catch (InvalidArgumentException $excepcion) {
            $rechazado = true;
        }
        usuariosComprobar($rechazado && $usuario->getPassword() === $hashAnterior, 'Una contraseña inválida no reemplaza el hash anterior.');
    }
}

function usuariosOpcionalesTest(): void
{
    $usuarioConDocumento = new Usuarios(
        'Lola',
        'lola@example.com',
        'clave-segura',
        '5322334455',
        TipoDocumento::DNI,
        '  35123456  '
    );
    usuariosComprobar($usuarioConDocumento->getTelefono() === '5322334455'
        && $usuarioConDocumento->getTipoDocumento() === TipoDocumento::DNI
        && $usuarioConDocumento->getDocumento() === '35123456', 'El constructor conserva sus parámetros opcionales y el tratamiento del documento.');
    $usuarioConDocumento->setTelefono(null);
    usuariosComprobar($usuarioConDocumento->getTelefono() === null, 'El setter de teléfono sigue admitiendo null.');
    $usuarioConDocumento->setTelefono('1199887766');
    usuariosComprobar($usuarioConDocumento->getTelefono() === '1199887766', 'El setter de teléfono sigue admitiendo texto.');
}

function usuariosTest(): void
{
    echo 'INICIANDO TEST: app/modelos/Usuarios.php' . PHP_EOL;
    usuariosConstructorTest();
    usuariosNombreTest();
    usuariosCorreoTest();
    usuariosPasswordTest();
    usuariosOpcionalesTest();
    echo 'Todas las pruebas de Usuarios pasaron.' . PHP_EOL;
}

// Permite ejecutar este test solo y también incluirlo desde un ejecutor de pruebas.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    usuariosTest();
}


*/