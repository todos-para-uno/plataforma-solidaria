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
        if ($nivel < 1) {
            http_response_code(400);
            echo 'El nivel debe ser un entero positivo.';
            return;
        }

        $validacion = $this->validaciones->findByNivel($nivel);

        if ($validacion === null) {
            http_response_code(404);
            echo 'No existe una validación con ese nivel.';
            return;
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'id' => $validacion->getId(),
            'nivel' => $validacion->getNivel(),
            'nombre' => $validacion->getNombre(),
        ]);
    }
}
