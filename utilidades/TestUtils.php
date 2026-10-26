<?php
class TestUtils{

static public function comprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException(
            "Prueba fallida: $mensaje"
        );
    }

    echo "OK: $mensaje\n";
}

}
