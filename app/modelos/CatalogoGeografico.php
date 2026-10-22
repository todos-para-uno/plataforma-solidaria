<?php

class CatalogoGeografico
{
    private static ?array $localidadesPorProvincia = null;

    public static function getLocalidadesPorProvincia(): array
    {
        if (self::$localidadesPorProvincia === null) {
            $archivo = __DIR__ . '/../../data/geografia/localidades-por-provincia.json';
            self::$localidadesPorProvincia = json_decode(
                file_get_contents($archivo),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return self::$localidadesPorProvincia;
    }
}
