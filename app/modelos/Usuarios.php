<?php
require_once __DIR__ . "/RegistroDb.php";
enum TipoDocumento: string
{
    case DNI = 'DNI';
    case Pasaporte = 'Pasaporte';
}
class Usuario extends RegistroDb
{
    private string $nombre;
    private string $mail;
    private string $pass;
    private ?string $telefono;

    private ?TipoDocumento $tipo_documento;
    private ?string $documento;
    private bool $verificado;
    private bool $admin;
    private int $validacion;
    
    function __construct(
        string $miNombre, 
        string $miMail, 
        string $hashPassword, 
        ?string $miTelefono = null, 
    ){
        $this->setNombre($miNombre);
        $this->setEmail($miMail);
  
        $this->setPassword($hashPassword); // $this->pass = password_hash($miPass, PASSWORD_DEFAULT);
        $this->setTelefono($miTelefono);

        $this->tipo_documento = null;
        $this->documento = null;

        $this->verificado = false;
        $this->admin = false;
        $this->validacion = 1;
    }

    public static function crear (
        string $nombre,
        string $mail,
        string $password,
        ?string $telefono = null,
        ?TipoDocumento $tipoDocumento = null,
        ?string $documento = null
    ): self 
    {
            $usuario = new self(
            $nombre,
            $mail,
            password_hash($password, PASSWORD_DEFAULT),
            $telefono
            );
            if ($tipoDocumento !== null || $documento !== null) {
                if ($tipoDocumento === null || $documento === null) {
                    throw new InvalidArgumentException(
                        'Debés indicar el tipo y número de documento.'
                    );
                }

                $usuario->agregarDocumento(
                    $tipoDocumento,
                    $documento
                );
            }
        return $usuario;            
    }
    /**
     * 
     * @param array<int,mixed> $filaUsuario
     */
    public static function desdePersistencia(array $filaUsuario): self
    {
        $usuario = new self(
            $filaUsuario['nombre'],
            $filaUsuario['mail'],
            $filaUsuario['pass'],
            $filaUsuario['telefono'] ?? null
        );

        $usuario->setId((int) $filaUsuario['id']);
        
        $usuario->verificado = (bool) $filaUsuario['verificado'];
        $usuario->admin = (bool) $filaUsuario['admin'];
        $usuario->validacion = (int) $filaUsuario['validacion'];

        if (
            !empty($filaUsuario['tipo_documento']) &&
            !empty($filaUsuario['documento'])
        ) {
            $usuario->agregarDocumento(
                TipoDocumento::from($filaUsuario['tipo_documento']),
                $filaUsuario['documento']
            );
        }

        return $usuario;
    }

    
    public function setNombre(string $nombre): void
    {
        $nombre = trim($nombre);
        if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 100) {
            throw new InvalidArgumentException('El nombre debe tener entre 1 y 100 caracteres.');
        }
        $this->nombre = $nombre;
    }
    public function getNombre(): string
    {
        return $this->nombre;
    }
    
    public function setEmail(string $email): void
    {
        $email = trim($email);
        if (mb_strlen($email, 'UTF-8') > 150) {
            throw new InvalidArgumentException('El correo no puede superar los 150 caracteres.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Ingresá un correo válido, como nombre@ejemplo.com.');
        }
        $this->mail = $email;
    }
    public function getEmail(): string
    {
        return $this->mail;
    }
    public function setPassword(string $password): void
    {
        if (trim($password) === '') {
            throw new InvalidArgumentException('La contraseña no puede estar vacía.');
        }
        if (mb_strlen($password, 'UTF-8') < 8) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
        }
        // Bcrypt solo utiliza los primeros 72 bytes y no admite bytes nulos.
        if (strlen($password) > 72 || strpos($password, "\0") !== false) {
            throw new InvalidArgumentException(
                'La contraseña debe ocupar como máximo 72 bytes y no contener bytes nulos.'
            );
        }
        $this->pass = $password;// password_hash($password, PASSWORD_DEFAULT);
    }
    public function getPassword(): string
    {
        return $this->pass;
    }
    public function setTelefono(?string $telefono): void
    {
        $this->telefono = $telefono;
    }
    public function getTelefono(): ?string
    {
        return $this->telefono;
    }
    public function getTipoDocumento(): ?TipoDocumento
    {
        return $this->tipo_documento;
    }
    public function getDocumento(): ?string
    {
        return $this->documento;
    }
    public function getVerificado(): bool
    {
        return $this->verificado;
    }
    public function getAdmin(): bool
    {
        return $this->admin;
    }
    public function getValidacion(): int
    {
        return $this->validacion;
    }

    public function verificarPassword(string $password): bool
    {
        return password_verify($password, $this->pass);
    }

    public function agregarDocumento(TipoDocumento $tipo, string $numero): void
    {
        if (trim($numero) === '') {
            throw new InvalidArgumentException(
                "El campo documento no puede estar vacío"
            );
        }
        $this->tipo_documento = $tipo;
        $this->documento = trim($numero); 
    }


}
?>
