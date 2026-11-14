<?php
require_once __DIR__ . '/../../app/controladores/CrearCampanaController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
function crearCampanaControllerTest(PDO $pdo): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $original = [$_SESSION,$_POST,$_SERVER,$_GET,http_response_code()];
    $pdo->beginTransaction();
    try {
        $f = crearFixtureBuscador($pdo);
        $owner = (int)$pdo->query('SELECT owner_id FROM camp WHERE id = '.$f['ropa'])->fetchColumn();
        $usuarios = new UsuariosRepository($pdo); $usuario = $usuarios->findById($owner);
        $stmt = $pdo->prepare('INSERT INTO categoria_insumos (nombre) VALUES (?)'); $stmt->execute([$f['prefijo'].' Cat']);
        $cat = (string)$pdo->lastInsertId();
        $entrada = ['nombre'=>$f['prefijo'].' Campaña creada','descripcion'=>'Descripción <script>texto</script>','categoria'=>(string)$f['alimentos'],'tipo_ayuda'=>'insumos',
            'insumos'=>[['categoria'=>$cat,'nombre'=>'Arroz (kg)','cantidad'=>'20'],['categoria'=>$cat,'nombre'=>'Leche (litros)','cantidad'=>'12.50']],
            'provincia'=>'Buenos Aires','localidad'=>'La Plata','lat'=>'-34.920500','lng'=>'-57.950100','ubicacion_texto'=>'Punto de prueba'];
        $repo = new CrearCampanaRepository($pdo);
        $controller = new CrearCampanaController($pdo);
        $render = static function (?Usuario $actor) use ($controller): array {
            http_response_code(200); ob_start();
            try { $controller->mostrar($actor); return [http_response_code(),ob_get_contents()]; }
            finally { ob_end_clean(); }
        };
        $_SERVER['REQUEST_METHOD']='GET'; $_POST=[];
        [$codigo]=$render(null); TestUtils::comprobar($codigo===303,'Crear campaña requiere sesión');
        [$codigo,$html]=$render($usuario);
        preg_match('/name="clave" value="([a-f0-9]{64})"/',$html,$m);
        TestUtils::comprobar($codigo===200 && isset($m[1]) && str_contains($html,'Agregar otro insumo') && str_contains($html,'insumos[0][cantidad]'),'Formulario real con filas de insumos y cantidades');
        $originales = (int)$pdo->query('SELECT COUNT(*) FROM camp')->fetchColumn();
        $_SERVER['REQUEST_METHOD']='POST'; $_POST=$entrada+['csrf_token'=>'incorrecto','clave'=>$m[1]];
        [$codigo]=$render($usuario); TestUtils::comprobar($codigo===403,'CSRF incorrecto no permite publicar');
        $_SESSION['usuario_id']=$owner; $_GET=['ruta'=>'crear_campana'];
        ob_start();
        try { (static function (): void { require __DIR__ . '/../../public_php/index.php'; })(); $respuesta=ob_get_contents(); }
        finally { ob_end_clean(); }
        TestUtils::comprobar(http_response_code()===403 && !str_contains($respuesta,'Método no permitido'),'La ruta pública admite POST y lo entrega al controlador');

        $_POST['csrf_token']=Csrf::generar(); $_POST['owner_id']='999999';
        [$codigo]=$render($usuario);
        $stmt=$pdo->prepare('SELECT * FROM camp WHERE nombre = ?');$stmt->execute([$entrada['nombre']]);$guardada=$stmt->fetch();
        $id=(int)$guardada['id'];
        $objetivos=(new ObjetivosRepository($pdo))->buscarDetallePorCampana($id);
        TestUtils::comprobar($codigo===303 && (int)$guardada['owner_id']===$owner && !$guardada['monetaria'] && count($objetivos)===2,'Publica con el dueño de la sesión y guarda los dos objetivos');
        TestUtils::comprobar((float)$objetivos[0]['objetivo']===20.0 && (float)$objetivos[1]['objetivo']===12.5 && (float)$objetivos[1]['parcial']===0.0,'Conserva cantidades independientes y progreso inicial cero');
        TestUtils::comprobar($guardada['id_ubicacion']!==null && $objetivos[0]['subcategoria_nombre']==='Arroz (kg)','Guarda un punto único y nombres de insumos reutilizables en el catálogo');
        [$codigo]=$render($usuario);
        TestUtils::comprobar($codigo===422 && (int)$pdo->query('SELECT COUNT(*) FROM camp')->fetchColumn()===$originales+1,'Reenviar el formulario no duplica la campaña');
        $rechaza=static function(array $datos) use($repo,$owner): void {
            try { $repo->publicar($owner,$datos); } catch(InvalidArgumentException $e) {return;}
            throw new RuntimeException('No rechazó entrada inválida');
        };
        foreach (['0','-1','1e3','1.001',['3']] as $cantidad) { $datos=$entrada;$datos['insumos'][1]['cantidad']=$cantidad;$rechaza($datos); }
        $datos=$entrada;$datos['insumos'][1]=$datos['insumos'][0];$rechaza($datos);
        $datos=$entrada;$datos['insumos'][1]['categoria']='999999999';$rechaza($datos);
        $datos=$entrada;$datos['lat']='91';$rechaza($datos);
        $datos=$entrada;$datos['localidad']='Inexistente';$rechaza($datos);
        TestUtils::comprobar((int)$pdo->query('SELECT COUNT(*) FROM camp')->fetchColumn()===$originales+1,'Rechaza cantidades, duplicados, categorías y ubicaciones inválidas sin campañas parciales');
        $_POST=$entrada+['csrf_token'=>Csrf::generar(),'clave'=>array_key_last($_SESSION['crear_campana_claves'])];
        $_POST['insumos'][1]['cantidad']='0';
        [$codigo,$html]=$render($usuario);
        TestUtils::comprobar($codigo===422 && str_contains($html,'value="Arroz (kg)"') && str_contains($html,'value="Leche (litros)"') && str_contains($html,'&lt;script&gt;texto&lt;/script&gt;'),'Si hay errores conserva todas las filas y escapa el contenido');
        $dinero=$entrada;$dinero['tipo_ayuda']='dinero';$dinero['nombre']=$f['prefijo'].' Dinero';$dinero['objetivo']='1234.56';$dinero['cbu']='0000000000000000000001';
        $idDinero=$repo->publicar($owner,$dinero);
        $fila=(new CampRepository($pdo))->findRowById($idDinero);
        TestUtils::comprobar((bool)$fila['monetaria'] && $fila['id_ubicacion']===null && (float)(new ObjetivosRepository($pdo))->findByIdCamp($idDinero)[0]['objetivo']===1234.56,'Las campañas monetarias conservan su propio monto y no reciben objetivos de insumos');
        // Forzar un fallo después de insertar la ubicación prueba la reversión integral.
        $ubicacionesAntes=(int)$pdo->query('SELECT COUNT(*) FROM ubicaciones')->fetchColumn();
        try { $repo->publicar(2147483647,$entrada); throw new RuntimeException('No se produjo el fallo esperado'); }
        catch(PDOException $e) {}
        TestUtils::comprobar((int)$pdo->query('SELECT COUNT(*) FROM ubicaciones')->fetchColumn()===$ubicacionesAntes,'Si falla el guardado no deja una ubicación huérfana');
    } finally {
        $pdo->rollBack(); [$_SESSION,$_POST,$_SERVER,$_GET,$codigo]=$original;http_response_code($codigo?:200);
    }
}
