<?php
require_once __DIR__ . "/RegistroDb.php";
enum TipoDocumento: string {
    case DNI = 'DNI';
    case Pasaporte = 'Pasaporte';
}
class Usuarios extends RegistroDb
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
        $this->nombre = $miNombre;
        $this->mail = $miMail;
  
        $this->pass = $hashPassword; // $this->pass = password_hash($miPass, PASSWORD_DEFAULT);
        $this->telefono = $miTelefono;

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

    
    function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }
    function getNombre()
    {
        return $this->nombre;
    }
    
    function setEmail($email)
    {
        $this->mail = $email;
    }
    function getEmail()
    {
        return $this->mail;
    }
    function setPassword($password)
    {
        $this->pass = password_hash($password, PASSWORD_DEFAULT);
    }
    function getPassword()
    {
        return $this->pass;
    }
    function setTelefono($telefono)
    {
        $this->telefono = $telefono;
    }
    function getTelefono()
    {
        return $this->telefono;
    }
    function getTipoDocumento()
    {
        return $this->tipo_documento;
    }
    function getDocumento()
    {
        return $this->documento;
    }
    function getVerificado()
    {
        return $this->verificado;
    }
    function getAdmin()
    {
        return $this->admin;
    }
    function getValidacion()
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