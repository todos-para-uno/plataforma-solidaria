<?php
require_once __DIR__ . '/ImagenesSubidas.php';
require_once __DIR__ . '/../modelos/CampanaImagen.php';

/** Fotos de campañas: conserva formatos y límites, con recodificación obligatoria. */
final class ImagenesCampana extends ImagenesSubidas
{
    public const MAXIMO_IMAGENES = CampanaImagen::MAXIMO_POR_CAMPANA;
}
