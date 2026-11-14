<?php
/** Conserva solo códigos conocidos: nunca el cuerpo, credenciales o mensajes del proveedor. */
final class MercadoPagoErrorApi extends RuntimeException
{
    public readonly string $errorOAuth;

    public function __construct(public readonly int $estadoHttp, mixed $error = null)
    {
        $this->errorOAuth = is_string($error) && in_array($error, [
            'invalid_grant', 'unauthorized_client', 'invalid_client', 'invalid_scope',
            'invalid_request', 'unsupported_grant_type', 'local_rate_limited',
        ], true) ? $error : 'desconocido';
        parent::__construct('Mercado Pago no pudo completar la operación. Intentá nuevamente.');
    }

    public function requiereReautorizar(bool $renovacion): bool
    {
        return $renovacion
            ? ($this->estadoHttp === 400 && in_array($this->errorOAuth, ['invalid_grant', 'unauthorized_client'], true))
            : $this->estadoHttp === 401;
    }
}
