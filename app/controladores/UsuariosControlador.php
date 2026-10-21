<?php
class UserController
{
    public function __construct(
        private UserRepository $users
    ) {}

    public function buscarPorNombre(string $nombre): void
    {
        //$nombre = $_POST['nombre'] ?? '';

        if ($nombre === '') {
            http_response_code(400);
            echo 'Falta el parámetro nombre';
            return;
        }

        $usuarios = $this->users->findByNombre($nombre);

        // Aquí normalmente llamarías a una vista,
        // o devolverías JSON si es una API.
        echo json_encode($usuarios);
    }
}
