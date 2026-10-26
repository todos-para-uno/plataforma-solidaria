<?php

final class Csrf
{
    private const CLAVE_SESION = 'csrf_token';

    public static function generar(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('La sesión no está iniciada.');
        }

        if (
            !isset($_SESSION[self::CLAVE_SESION]) ||
            !is_string($_SESSION[self::CLAVE_SESION])
        ) {
            $_SESSION[self::CLAVE_SESION] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CLAVE_SESION];
    }

    public static function validar(mixed $token): bool
    {
        if (
            session_status() !== PHP_SESSION_ACTIVE ||
            !is_string($token) ||
            !isset($_SESSION[self::CLAVE_SESION]) ||
            !is_string($_SESSION[self::CLAVE_SESION])
        ) {
            return false;
        }

        return hash_equals(
            $_SESSION[self::CLAVE_SESION],
            $token
        );
    }
}