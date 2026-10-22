<?php
require_once __DIR__ . '/RegistroDb.php';

// Lugar elegido por el usuario para recibir donaciones de insumos.
class Ubicaciones extends RegistroDb
{
    private string $provincia;
    private string $localidad;
    private float $latitud;
    private float $longitud;
    private ?string $referencia;

    // Las coordenadas provienen del punto seleccionado en el mapa.
    public function __construct(
        string $provincia,
        string $localidad,
        float $latitud,
        float $longitud,
        ?string $referencia = null
    ) {
        $this->setProvincia($provincia);
        $this->setLocalidad($localidad);
        $this->setLatitud($latitud);
        $this->setLongitud($longitud);
        $this->setReferencia($referencia);
    }

    public function setProvincia(string $provincia): void
    {
        $provincia = trim($provincia);
        if ($provincia === '' || mb_strlen($provincia, 'UTF-8') > 100) {
            throw new InvalidArgumentException('La provincia debe tener entre 1 y 100 caracteres.');
        }
        $this->provincia = $provincia;
    }

    public function getProvincia(): string
    {
        return $this->provincia;
    }

    public function setLocalidad(string $localidad): void
    {
        $localidad = trim($localidad);
        if ($localidad === '' || mb_strlen($localidad, 'UTF-8') > 100) {
            throw new InvalidArgumentException('La localidad debe tener entre 1 y 100 caracteres.');
        }
        $this->localidad = $localidad;
    }

    public function getLocalidad(): string
    {
        return $this->localidad;
    }

    public function setLatitud(float $latitud): void
    {
        if (!is_finite($latitud) || $latitud < -90 || $latitud > 90) {
            throw new InvalidArgumentException('La latitud debe ser un número entre -90 y 90.');
        }
        $this->latitud = $latitud;
    }

    public function getLatitud(): float
    {
        return $this->latitud;
    }

    public function setLongitud(float $longitud): void
    {
        if (!is_finite($longitud) || $longitud < -180 || $longitud > 180) {
            throw new InvalidArgumentException('La longitud debe ser un número entre -180 y 180.');
        }
        $this->longitud = $longitud;
    }

    public function getLongitud(): float
    {
        return $this->longitud;
    }

    public function setReferencia(?string $referencia): void
    {
        $referencia = $referencia === null ? null : trim($referencia);
        if ($referencia !== null && mb_strlen($referencia, 'UTF-8') > 150) {
            throw new InvalidArgumentException('La referencia no puede superar los 150 caracteres.');
        }
        // Un campo opcional sin completar se representa como NULL en la tabla.
        $this->referencia = $referencia === '' ? null : $referencia;
    }

    public function getReferencia(): ?string
    {
        return $this->referencia;
    }
}
