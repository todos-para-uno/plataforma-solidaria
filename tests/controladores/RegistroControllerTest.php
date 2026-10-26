<?php

require_once __DIR__ . '/../../app/repositorios/UsuariosRepository.php';
require_once __DIR__ . '/../../app/repositorios/ValidacionesRepository.php';
require_once __DIR__ . '/../../app/controladores/RegistroController.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
require_once __DIR__ . '/../../utilidades/ManejoDeCadenas.php';

function registroControllerTest(PDO $pdo): void
{
    $usuarios = new UsuariosRepository($pdo);
    $validaciones = new ValidacionesRepository($pdo);

    $controlador = new RegistroController(
        $usuarios,
        $validaciones
    );

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $csrfToken = Csrf::generar();

    $postOriginal = $_POST;
    $codigoHttpOriginal = http_response_code();

    $mail = 'registro-test-' . bin2hex(random_bytes(8))
        . '@example.com';

    $password = 'Password123';

    $pdo->beginTransaction();

    try {
        $_POST = [
            'nombre' => 'Usuario de prueba',
            'mail' => $mail,
            'pass' => $password,
            'confirmar_pass' => $password,
            'csrf_token' => $csrfToken,
        ];
        http_response_code(200);

        ob_start();

        try {
            $controlador->procesarRegistro('.', './assets');
        } finally {
            $respuesta = ob_get_clean();
        }

        $usuario = $usuarios->findByMail($mail);

        TestUtils::comprobar(
            $usuario instanceof Usuario,
            'El controlador registra un usuario nuevo' . eol()
        );

        TestUtils::comprobar(
            $usuario !== null
                && $usuario->verificarPassword($password),
            'La contraseña registrada se verifica correctamente' . eol()
        );

        TestUtils::comprobar(
            $usuario !== null
                && !$usuario->getVerificado()
                && !$usuario->getAdmin(),
            'El usuario nuevo no está verificado ni es administrador'
                . eol()
        );

        $validacionInicial = $validaciones->findByNivel(
            NivelValidacion::DEFAULT_NIVEL_USUARIO
        );

        TestUtils::comprobar(
            $usuario !== null
                && $validacionInicial !== null
                && $usuario->getIdValidacion() === $validacionInicial->getId(),
            'El usuario recibe el ID real de la validación inicial'
                . eol()
        );

        TestUtils::comprobar(
            http_response_code() === 200,
            'El registro exitoso devuelve HTTP 200' . eol()
        );

        TestUtils::comprobar(
            str_contains($respuesta, 'Cuenta creada correctamente'),
            'El registro exitoso muestra la vista de confirmación' . eol()
        );

        // Rechazar contraseñas que no coinciden.
        $mailInvalido = 'registro-invalido-'
            . bin2hex(random_bytes(8))
            . '@example.com';

        $_POST = [
            'nombre' => 'Usuario inválido',
            'mail' => $mailInvalido,
            'pass' => 'Password123',
            'confirmar_pass' => 'Password456',
            'csrf_token' => $csrfToken,
        ];

        http_response_code(200);

        ob_start();

        try {
            $controlador->procesarRegistro('.', './assets');
        } finally {
            $respuesta = ob_get_clean();
        }

        TestUtils::comprobar(
            http_response_code() === 422,
            'El controlador devuelve HTTP 422 si las contraseñas no coinciden'
                . eol()
        );

        TestUtils::comprobar(
            str_contains($respuesta, 'Las contraseñas no coinciden.'),
            'El formulario informa que las contraseñas no coinciden'
                . eol()
        );

        TestUtils::comprobar(
            $usuarios->findByMail($mailInvalido) === null,
            'No se inserta un usuario con contraseñas diferentes'
                . eol()
        );

        // Rechazar un correo electrónico ya registrado.
        $_POST = [
            'nombre' => 'Otro usuario',
            'mail' => $mail,
            'pass' => 'OtraPassword123',
            'confirmar_pass' => 'OtraPassword123',
            'csrf_token' => $csrfToken,
        ];

        http_response_code(200);

        ob_start();

        try {
            $controlador->procesarRegistro('.', './assets');
        } finally {
            $respuesta = ob_get_clean();
        }

        TestUtils::comprobar(
            http_response_code() === 422,
            'El controlador devuelve HTTP 422 si el correo ya existe'
                . eol()
        );

        TestUtils::comprobar(
            str_contains(
                $respuesta,
                'Ya existe una cuenta con ese correo electrónico.'
            ),
            'El formulario informa que el correo ya está registrado'
                . eol()
        );

        $usuarioPosterior = $usuarios->findByMail($mail);

        TestUtils::comprobar(
            $usuarioPosterior !== null
                && $usuarioPosterior->getId() === $usuario->getId()
                && $usuarioPosterior->getNombre() === 'Usuario de prueba',
            'El intento de registro duplicado no modifica el usuario existente'
                . eol()
        );

        // Verificar el rechazo de datos inválidos.
        $casosInvalidos = [
            [
                'descripcion' => 'nombre vacío',
                'cambios' => ['nombre' => '   '],
                'codigo' => 422,
                'mensaje' => 'El nombre debe tener entre 1 y 100 caracteres.',
            ],
            [
                'descripcion' => 'correo inválido',
                'cambios' => ['mail' => 'correo-invalido'],
                'codigo' => 422,
                'mensaje' => 'Ingresá un correo electrónico válido.',
            ],
            [
                'descripcion' => 'contraseña demasiado corta',
                'cambios' => [
                    'pass' => '123',
                    'confirmar_pass' => '123',
                ],
                'codigo' => 422,
                'mensaje' => 'La contraseña debe tener al menos 8 caracteres.',
            ],
            [
                'descripcion' => 'datos enviados como arrays',
                'cambios' => ['nombre' => ['Nombre inválido']],
                'codigo' => 400,
                'mensaje' => 'Los datos enviados no son válidos.',
            ],
        ];

        // Verificar el rechazo de solicitudes con CSRF inválido.
        $casosCsrf = [
            [
                'descripcion' => 'token ausente',
                'token' => null,
            ],
            [
                'descripcion' => 'token incorrecto',
                'token' => str_repeat('0', 64),
            ],
            [
                'descripcion' => 'token enviado como array',
                'token' => ['token-invalido'],
            ],
        ];

        foreach ($casosCsrf as $caso) {
            $mailCaso = 'registro-csrf-'
                . bin2hex(random_bytes(8))
                . '@example.com';

            $_POST = [
                'nombre' => 'Usuario CSRF',
                'mail' => $mailCaso,
                'pass' => 'Password123',
                'confirmar_pass' => 'Password123',
            ];

            if ($caso['token'] !== null) {
                $_POST['csrf_token'] = $caso['token'];
            }

            http_response_code(200);

            ob_start();

            try {
                $controlador->procesarRegistro('.', './assets');
            } finally {
                $respuesta = ob_get_clean();
            }

            TestUtils::comprobar(
                http_response_code() === 403,
                'HTTP 403 para ' . $caso['descripcion'] . eol()
            );

            TestUtils::comprobar(
                str_contains($respuesta, 'La solicitud no es válida.'),
                'Mensaje de rechazo para ' . $caso['descripcion'] . eol()
            );

            TestUtils::comprobar(
                $usuarios->findByMail($mailCaso) === null,
                'No se registra un usuario con ' . $caso['descripcion']
                    . eol()
            );
        }

        foreach ($casosInvalidos as $caso) {
            $mailCaso = 'registro-invalido-'
                . bin2hex(random_bytes(8))
                . '@example.com';

            $_POST = array_replace(
                [
                    'nombre' => 'Usuario de prueba',
                    'mail' => $mailCaso,
                    'pass' => 'Password123',
                    'confirmar_pass' => 'Password123',
                    'csrf_token' => $csrfToken,
                ],
                $caso['cambios']
            );

            http_response_code(200);

            ob_start();

            try {
                $controlador->procesarRegistro('.', './assets');
            } finally {
                $respuesta = ob_get_clean();
            }

            TestUtils::comprobar(
                http_response_code() === $caso['codigo'],
                'Código HTTP correcto para ' . $caso['descripcion'] . eol()
            );

            TestUtils::comprobar(
                str_contains($respuesta, $caso['mensaje']),
                'Mensaje correcto para ' . $caso['descripcion'] . eol()
            );

            TestUtils::comprobar(
                $usuarios->findByMail($mailCaso) === null,
                'No se registra un usuario con ' . $caso['descripcion'] . eol()
            );
        }

    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_POST = $postOriginal;
        http_response_code($codigoHttpOriginal ?: 200);
    }
}