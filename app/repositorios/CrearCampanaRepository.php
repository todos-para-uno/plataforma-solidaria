<?php
require_once __DIR__ . '/CampRepository.php';
require_once __DIR__ . '/ObjetivosRepository.php';
require_once __DIR__ . '/../modelos/Ubicaciones.php';
final class CrearCampanaRepository
{
    public function __construct(private PDO $pdo) {}
    private function consulta(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt;
    }
    private function texto(array $entrada, string $campo, int $max): string
    {
        $valor = $entrada[$campo] ?? null;
        if (!is_string($valor) || trim($valor) === '' || mb_strlen(trim($valor)) > $max) throw new InvalidArgumentException('Completá ' . $campo . ' con hasta ' . $max . ' caracteres.');
        return trim($valor);
    }
    private function cantidad(mixed $valor): string
    {
        if (!is_string($valor) || !preg_match('/^(?:0|[1-9][0-9]{0,9})(?:[.,][0-9]{1,2})?$/D', trim($valor))) throw new InvalidArgumentException('Cada objetivo debe tener una cantidad positiva con hasta dos decimales.');
        $valor = str_replace(',', '.', trim($valor));
        if ((float) $valor <= 0) throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        return $valor;
    }
    private function id(mixed $valor): int
    {
        $id = is_string($valor) ? filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if (!$id) throw new InvalidArgumentException('Seleccioná una categoría válida.');
        return $id;
    }
    public function publicar(int $actor, array $entrada): int
    {
        $nombre = $this->texto($entrada, 'nombre', 100);
        $descripcion = $this->texto($entrada, 'descripcion', 5000);
        $categoria = $this->id($entrada['categoria'] ?? null);
        if (!$this->consulta('SELECT id FROM cat_camp WHERE id = ?', [$categoria])->fetchColumn()) throw new InvalidArgumentException('La categoría de campaña no existe.');
        $tipo = $entrada['tipo_ayuda'] ?? null;
        if (!in_array($tipo, ['dinero', 'insumos'], true)) throw new InvalidArgumentException('Elegí dinero o insumos.');
        $dinero = $tipo === 'dinero';
        $filas = []; $ubicacion = null;
        if ($dinero) {
            $monto = $this->cantidad($entrada['objetivo'] ?? null);
            $cbu = $this->texto($entrada, 'cbu', 22);
            // Reutilizar las reglas del modelo antes de escribir datos.
            new Camp($actor, $nombre, true, $categoria, $descripcion, $cbu);
        } else {
            if (!is_array($entrada['insumos'] ?? null) || count($entrada['insumos']) < 1 || count($entrada['insumos']) > 20) throw new InvalidArgumentException('Agregá entre 1 y 20 insumos con su cantidad.');
            $repetidos = [];
            foreach ($entrada['insumos'] as $fila) {
                if (!is_array($fila)) throw new InvalidArgumentException('Insumo no válido.');
                $cat = $this->id($fila['categoria'] ?? null);
                if (!$this->consulta('SELECT id FROM categoria_insumos WHERE id = ?', [$cat])->fetchColumn()) throw new InvalidArgumentException('Seleccioná una categoría de insumos existente.');
                $insumo = $this->texto($fila, 'nombre', 100);
                $cantidad = $this->cantidad($fila['cantidad'] ?? null);
                $clave = $cat . ':' . mb_strtolower($insumo);
                if (isset($repetidos[$clave])) throw new InvalidArgumentException('Un insumo está repetido. Unificá su cantidad en una sola fila.');
                $repetidos[$clave] = true; $filas[] = [$cat, $insumo, $cantidad];
            }
            if (!is_string($entrada['lat'] ?? null) || !is_numeric($entrada['lat']) || !is_string($entrada['lng'] ?? null) || !is_numeric($entrada['lng'])) throw new InvalidArgumentException('Buscá y seleccioná el lugar de entrega o marcá un punto en el mapa.');
            $referencia = $entrada['ubicacion_texto'] ?? '';
            if (!is_string($referencia)) throw new InvalidArgumentException('La referencia no es válida.');
            $ubicacion = new Ubicaciones($this->texto($entrada, 'provincia', 100), $this->texto($entrada, 'localidad', 100), (float) $entrada['lat'], (float) $entrada['lng'], $referencia);
        }
        $externa = $this->pdo->inTransaction();
        if ($externa) $this->pdo->exec('SAVEPOINT crear_campana'); else $this->pdo->beginTransaction();
        try {
            $idUbicacion = null;
            if ($ubicacion) {
                $this->consulta('INSERT INTO ubicaciones (provincia,localidad,latitud,longitud,referencia) VALUES (?,?,?,?,?)', [$ubicacion->getProvincia(),$ubicacion->getLocalidad(),$ubicacion->getLatitud(),$ubicacion->getLongitud(),$ubicacion->getReferencia()]);
                $idUbicacion = (int) $this->pdo->lastInsertId();
            }
            $id = (int) (new CampRepository($this->pdo))->save(['owner_id'=>$actor,'nombre'=>$nombre,'descripcion'=>$descripcion,'categoria'=>$categoria,'monetaria'=>(int)$dinero,'cbu'=>$dinero ? $cbu : null,'estado'=>'activa','logrado'=>0,'id_ubicacion'=>$idUbicacion]);
            $objetivos = new ObjetivosRepository($this->pdo);
            if ($dinero) $objetivos->save(['id_camp'=>$id,'objetivo'=>$monto]);
            else foreach ($filas as [$cat,$insumo,$cantidad]) {
                // Reutiliza el catálogo, incluso si otro usuario agrega el mismo insumo a la vez.
                $this->consulta('INSERT INTO sub_categoria_insumos (id_categoria,nombre) VALUES (?,?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)', [$cat,$insumo]);
                $sub = (int) $this->pdo->lastInsertId();
                $objetivos->save(['id_camp'=>$id,'id_categoria_insumos'=>$cat,'id_sub_categoria_insumos'=>$sub,'objetivo'=>$cantidad,'parcial'=>0]);
            }
            if ($externa) $this->pdo->exec('RELEASE SAVEPOINT crear_campana'); else $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($externa) $this->pdo->exec('ROLLBACK TO SAVEPOINT crear_campana'); else $this->pdo->rollBack();
            throw $e;
        }
    }
}
