<?php
/** Lectura administrativa de consultas recibidas por el formulario de contacto. */
final class AdministracionMensajesRepository
{
    public function __construct(private PDO $pdo) {}

    public function contarRemitentes(string $busqueda): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM (SELECT mail FROM contactos
            WHERE nombre LIKE :q1 OR mail LIKE :q2 OR asunto LIKE :q3
            GROUP BY mail) AS remitentes');
        $q = '%' . $busqueda . '%';
        $stmt->execute(['q1'=>$q, 'q2'=>$q, 'q3'=>$q]);
        return (int) $stmt->fetchColumn();
    }

    public function remitentes(string $busqueda, int $limite, int $offset, string $orden): array
    {
        // Solo expresiones SQL internas: nunca concatenar una columna recibida del cliente.
        $ordenes = [
            'recientes' => 'ultima_fecha DESC, mail ASC',
            'nombre_asc' => 'nombre ASC, mail ASC',
            'nombre_desc' => 'nombre DESC, mail ASC',
            'correo_asc' => 'mail ASC',
            'correo_desc' => 'mail DESC',
        ];
        $orderBy = $ordenes[$orden] ?? $ordenes['recientes'];
        $stmt = $this->pdo->prepare('SELECT mail, MAX(nombre) AS nombre, COUNT(*) AS cantidad,
            MAX(fecha_hora) AS ultima_fecha FROM contactos
            WHERE nombre LIKE :q1 OR mail LIKE :q2 OR asunto LIKE :q3
            GROUP BY mail ORDER BY ' . $orderBy . ' LIMIT :limite OFFSET :offset');
        $q = '%' . $busqueda . '%';
        foreach (['q1','q2','q3'] as $key) $stmt->bindValue(':' . $key, $q);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function mensajes(string $mail): array
    {
        $stmt = $this->pdo->prepare('SELECT id, id_usuario, nombre, mail, asunto, contenido,
            fecha_hora, correo_enviado FROM contactos WHERE mail = :mail
            ORDER BY fecha_hora DESC, id DESC');
        $stmt->execute(['mail'=>$mail]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
