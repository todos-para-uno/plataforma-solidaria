<?php
class ValidacionesController
{
    public function __construct(
        private ValidacionesRepository $validaciones
    ) {}

    public function buscarPorNombre(string $nombre): void
    {
        //$nombre = $_POST['nombre'] ?? '';

        if ($nombre === '') {
            http_response_code(400);
            echo 'Falta el parámetro nombre';
            return;
        }

        $validaciones = $this->validaciones->findByNombre($nombre);

        // Aquí normalmente llamarías a una vista,
        // o devolverías JSON si es una API.
        echo json_encode($validaciones);
    }

        public function buscarPorNivel(int $nivel): void
    {
        //$nombre = $_POST['nombre'] ?? '';

        if ($nivel === null) {
            http_response_code(400);
            echo 'Falta el parámetro nivel';
            return;
        }

        $validaciones = $this->validaciones->findByNivel($nivel);

        // Aquí normalmente llamarías a una vista,
        // o devolverías JSON si es una API.
        echo json_encode($validaciones);
    }
}
