<?php
require_once __DIR__ . '/CrudRepository.php';
require_once __DIR__ . '/../modelos/Contacto.php';

final class ContactoRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'contactos',
            primaryKey: 'id',
            fields: ['id_usuario', 'nombre', 'mail', 'asunto', 'contenido', 'correo_enviado']
        );
    }

    protected function hidratar(array $fila): Contacto
    {
        $contacto = new Contacto(
            $fila['nombre'],
            $fila['mail'],
            $fila['asunto'],
            $fila['contenido'],
            $fila['id_usuario'] === null ? null : (int) $fila['id_usuario'],
            $fila['fecha_hora'],
            (bool) $fila['correo_enviado']
        );
        $contacto->setId((int) $fila['id']);

        return $contacto;
    }

    public function insertarContacto(Contacto $contacto): int|string
    {
        if ($contacto->tieneId()) {
            throw new InvalidArgumentException('No se puede insertar un contacto que ya tiene ID.');
        }

        return $this->save([
            'id_usuario' => $contacto->getIdUsuario(),
            'nombre' => $contacto->getNombre(),
            'mail' => $contacto->getMail(),
            'asunto' => $contacto->getAsunto(),
            'contenido' => $contacto->getContenido(),
            'correo_enviado' => 0,
        ]);
    }
}
