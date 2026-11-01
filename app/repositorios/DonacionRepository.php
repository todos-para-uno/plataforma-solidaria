<?php
require_once __DIR__ . '/CrudRepository.php';
require_once __DIR__ . '/../modelos/Donacion.php';
require_once __DIR__ . '/CampanaImagenesRepository.php';
final class DonacionRepository extends CrudRepository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct(
            $pdo,
            table: 'donacion',
            primaryKey: 'id',
            fields: ['id_usuario', 'id_camp', 'id_categoria_insumos', 
            'id_sub_categoria_insumos', 'cantidad', 'fecha_hora']
        );
    }

    protected function hidratar(array $fila): Donacion
    {
        $donacion = new Donacion((int) $fila['id_usuario'], (int) $fila['id_camp'], (float) $fila['cantidad'],
            isset($fila['id_categoria_insumos']) ? (int) $fila['id_categoria_insumos'] : null,
            isset($fila['id_sub_categoria_insumos']) ? (int) $fila['id_sub_categoria_insumos'] : null,
            $fila['fecha_hora']);
        $donacion->setId((int) $fila['id']);
        return $donacion;
    }

    public function findByIdUsuario(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `donacion` WHERE `id_usuario` = :idUsuario' 
        );
        $stmt->execute(['idUsuario' => $idUsuario]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Historial privado del donante: cada fila corresponde a una entrega registrada. */
    public function historialPorUsuario(int $usuario): array
    {
        $stmt = $this->pdo->prepare('SELECT d.id, d.id_camp, d.cantidad, d.fecha_hora,
            c.nombre AS campaña_nombre, c.monetaria, c.estado, c.imagen,
            COALESCE(s.nombre, ci.nombre, "Insumos") AS insumo
            FROM donacion d JOIN camp c ON c.id = d.id_camp
            LEFT JOIN categoria_insumos ci ON ci.id = d.id_categoria_insumos
            LEFT JOIN sub_categoria_insumos s ON s.id = d.id_sub_categoria_insumos
            WHERE d.id_usuario = ? ORDER BY d.fecha_hora DESC, d.id DESC');
        $stmt->execute([$usuario]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $portadas = (new CampanaImagenesRepository($this->pdo))->portadas(array_column($filas, 'id_camp'));
        foreach ($filas as &$fila) $fila['imagen_subida'] = $portadas[(int) $fila['id_camp']] ?? null;
        unset($fila);
        return $filas;
    }

    public function findByIdCamp(int $idCamp): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `donacion` WHERE `id_camp` = :idCamp' 
        );
        $stmt->execute(['idCamp' => $idCamp]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Solo el creador acredita insumos efectivamente recibidos. Ambas escrituras son atómicas. */
    public function registrarRecepcion(int $actor, int $campana, int $objetivo, string $correo, mixed $cantidad): void
    {
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Ingresá el correo de la cuenta del donante.');
        if (!is_string($cantidad) || !preg_match('/^(?:0|[1-9][0-9]{0,9})(?:[.,][0-9]{1,2})?$/D', trim($cantidad))) {
            throw new InvalidArgumentException('Ingresá una cantidad positiva con hasta dos decimales.');
        }
        $cantidad = str_replace(',', '.', trim($cantidad));
        if ((float) $cantidad <= 0) throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        $externa = $this->pdo->inTransaction();
        if ($externa) $this->pdo->exec('SAVEPOINT recepcion_insumos');
        else $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM camp WHERE id = ? FOR UPDATE');
            $stmt->execute([$campana]); $camp = $stmt->fetch();
            if (!$camp || (int) $camp['owner_id'] !== $actor || $camp['monetaria'] || $camp['estado'] !== 'activa') {
                throw new InvalidArgumentException('Solo el creador puede registrar recepciones de una campaña activa de insumos.');
            }
            $stmt = $this->pdo->prepare('SELECT * FROM objetivos WHERE id = ? AND id_camp = ? FOR UPDATE');
            $stmt->execute([$objetivo, $campana]); $meta = $stmt->fetch();
            if (!$meta || !$meta['id_categoria_insumos']) throw new InvalidArgumentException('Elegí uno de los insumos pedidos en esta campaña.');
            $stmt = $this->pdo->prepare('SELECT id FROM usuarios WHERE mail = ?');
            $stmt->execute([$correo]); $donante = $stmt->fetchColumn();
            if (!$donante) throw new InvalidArgumentException('El donante debe tener una cuenta registrada con ese correo.');
            if ((int) $donante === $actor) throw new InvalidArgumentException('El donante debe ser otra persona.');
            $stmt = $this->pdo->prepare('UPDATE objetivos SET parcial = COALESCE(parcial, 0) + ? WHERE id = ? AND COALESCE(parcial, 0) <= 9999999999.99 - ?');
            $stmt->execute([$cantidad, $objetivo, $cantidad]);
            if ($stmt->rowCount() !== 1) throw new InvalidArgumentException('La cantidad supera el límite admitido para este objetivo.');
            $stmt = $this->pdo->prepare('INSERT INTO donacion (id_usuario, id_camp, id_categoria_insumos, id_sub_categoria_insumos, cantidad) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$donante, $campana, $meta['id_categoria_insumos'], $meta['id_sub_categoria_insumos'], $cantidad]);
            if ($externa) $this->pdo->exec('RELEASE SAVEPOINT recepcion_insumos');
            else $this->pdo->commit();
        } catch (Throwable $e) {
            if ($externa) $this->pdo->exec('ROLLBACK TO SAVEPOINT recepcion_insumos');
            else $this->pdo->rollBack();
            throw $e;
        }
    }

    public function recepcionesPorCampana(int $campana, int $actor): array
    {
        $stmt = $this->pdo->prepare('SELECT d.cantidad, d.fecha_hora, u.nombre AS donante,
            COALESCE(s.nombre, ci.nombre, "Insumos") AS insumo
            FROM donacion d JOIN camp c ON c.id = d.id_camp JOIN usuarios u ON u.id = d.id_usuario
            LEFT JOIN categoria_insumos ci ON ci.id = d.id_categoria_insumos
            LEFT JOIN sub_categoria_insumos s ON s.id = d.id_sub_categoria_insumos
            WHERE d.id_camp = ? AND c.owner_id = ? AND c.monetaria = 0 ORDER BY d.id DESC');
        $stmt->execute([$campana, $actor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
