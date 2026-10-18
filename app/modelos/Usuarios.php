<?php
enum TipoDocumento: string {
    case DNI = 'DNI';
    case Pasaporte = 'Pasaporte';
}
class Usuarios
{
    private ?int $id = null;
    private string $nombre;
    private string $mail;
    private string $pass;
    private ?string $telefono;

    private ?TipoDocumento $tipo_documento;
    private ?string $documento;
    private bool $verificado;
    private bool $admin;
    private int $validacion;

    function __construct(string $miNombre, string $miMail, string $miPass, ?string $miTelefono = null, ?TipoDocumento $miTipoDoc = null, ?string $miDocumento = null)
    {
        $this->nombre = $miNombre;
        $this->mail = $miMail;
        $this->pass = password_hash($miPass, PASSWORD_DEFAULT);
        $this->telefono = $miTelefono;
        $this->tipo_documento = null;
        $this->documento = null;
        $this->verificado = false;
        $this->admin = false;
        $this->validacion = 1;

        if ($miTipoDoc !== null || $miDocumento !== null) {
            if ($miTipoDoc === null || $miDocumento === null) {
                throw new InvalidArgumentException("Debés indicar el tipo y número de documento.");
            }
            $this->agregarDocumento($miTipoDoc, $miDocumento);
        }
    }

    function setId($id)
    {
        $this->id = $id;
    }
    function getId()
    {
        return $this->id;
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

    public function agregarDocumento(TipoDocumento $tipo, string $numero): void
    {
        if (trim($numero) === '') {
            throw new InvalidArgumentException(
                "El campo documento no puede estar vacío"
            );
        }
        $this->tipo_documento = $tipo;
        $this->documento = trim($numero); //por si el usuario copypastea su dni y se generan espacios antes
    }
}
?>