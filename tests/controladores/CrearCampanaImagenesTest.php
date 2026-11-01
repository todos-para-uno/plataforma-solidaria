<?php
require_once __DIR__ . '/../../app/controladores/CrearCampanaController.php';
require_once __DIR__ . '/../../app/controladores/CampanaController.php';
require_once __DIR__ . '/../fixtures/BuscadorFixture.php';
require_once __DIR__ . '/../../utilidades/TestUtils.php';
function crearCampanaImagenesTest(PDO $pdo): void
{
    if (!$pdo->query("SHOW TABLES LIKE 'campana_imagenes'")->fetchColumn()) {
        echo "PENDIENTE: importar database/migrations/20261101_campana_imagenes.sql para probar imágenes con MariaDB.\n"; return;
    }
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $original = [$_SESSION,$_POST,$_GET,$_FILES,$_SERVER,http_response_code()];
    $tmp = sys_get_temp_dir() . '/integracion-fotos-' . bin2hex(random_bytes(6)); mkdir($tmp,0700);
    $pdo->beginTransaction();
    try {
        $f=crearFixtureBuscador($pdo);$owner=(int)$pdo->query('SELECT owner_id FROM camp WHERE id='.$f['ropa'])->fetchColumn();
        $usuario=(new UsuariosRepository($pdo))->findById($owner);
        $stmt=$pdo->prepare('INSERT INTO categoria_insumos (nombre) VALUES (?)');$stmt->execute([$f['prefijo'].' Insumos']);$cat=(string)$pdo->lastInsertId();
        $foto=$tmp.'/foto.png';$img=imagecreatetruecolor(30,20);imagepng($img,$foto);
        $archivos=new ImagenesCampana($tmp.'/destino','subidas/campanas',static fn($ruta)=>is_file($ruta),static fn($a,$b)=>copy($a,$b));
        $controller=new CrearCampanaController($pdo,$archivos);
        $render=static function() use(&$controller,$usuario): array {http_response_code(200);ob_start();try{$controller->mostrar($usuario);return [http_response_code(),ob_get_contents()];}finally{ob_end_clean();}};
        $_SERVER['REQUEST_METHOD']='GET';$_POST=[];$_FILES=[];unset($_SERVER['CONTENT_LENGTH']);
        [$codigo,$html]=$render();preg_match('/name="clave" value="([a-f0-9]{64})"/',$html,$clave);
        TestUtils::comprobar(str_contains($html,'multipart/form-data')&&str_contains($html,'data-carga-imagenes'),'Mantiene el formulario actual y agrega la carga múltiple de fotos');
        $_POST=['csrf_token'=>Csrf::generar(),'clave'=>$clave[1],'nombre'=>$f['prefijo'].' Fotos','descripcion'=>'Dos objetivos y fotos','categoria'=>(string)$f['alimentos'],'tipo_ayuda'=>'insumos','provincia'=>'Buenos Aires','localidad'=>'La Plata','lat'=>'-34.92','lng'=>'-57.95','insumos'=>[['categoria'=>$cat,'nombre'=>'Arroz (kg)','cantidad'=>'20'],['categoria'=>$cat,'nombre'=>'Fideos','cantidad'=>'30']]];
        $datosValidos=$_POST;
        $_SERVER['REQUEST_METHOD']='POST';
        $_FILES=['imagenes'=>['name'=>['foto.png','otra.png'],'tmp_name'=>[$foto,$foto],'error'=>[0,0],'size'=>[filesize($foto),filesize($foto)],'type'=>['image/png','image/png']]];
        [$codigo]=$render();$stmt=$pdo->prepare('SELECT id FROM camp WHERE nombre=?');$stmt->execute([$_POST['nombre']]);$id=(int)$stmt->fetchColumn();
        $imagenes=new CampanaImagenesRepository($pdo);$guardadas=$imagenes->buscarPorCampana($id);
        TestUtils::comprobar($codigo===303&&count($guardadas)===2&&count((new ObjetivosRepository($pdo))->findByIdCamp($id))===2,'Guarda dos fotos y dos objetivos juntos en una campaña de insumos');
        TestUtils::comprobar($guardadas[0]->getOrden()===0&&is_file($tmp.'/destino/'.basename($guardadas[0]->getRuta())),'La primera foto queda como portada y existe físicamente');
        $detalle=new CampanaController(new CampRepository($pdo),new CatCampRepository($pdo),new UsuariosRepository($pdo),new ValidacionesRepository($pdo),new ObjetivosRepository($pdo),null,$imagenes);
        ob_start();try{$detalle->mostrar('.','./assets',(string)$id);$html=ob_get_contents();}finally{ob_end_clean();}
        TestUtils::comprobar(str_contains($html,$guardadas[0]->getRuta())&&str_contains($html,$guardadas[1]->getRuta()),'El detalle muestra la portada y las otras fotos');
        TestUtils::comprobar(($imagenes->portadas([$id])[$id]??null)===$guardadas[0]->getRuta(),'Las tarjetas recuperan la portada elegida');
        $antes=count(glob($tmp.'/destino/*'));[$codigo]=$render();
        TestUtils::comprobar($codigo===422&&count(glob($tmp.'/destino/*'))===$antes,'El doble envío no duplica imágenes ni campaña');
        $_SERVER['REQUEST_METHOD']='GET';$_POST=[];$_FILES=[];[$codigo,$html]=$render();preg_match('/name="clave" value="([a-f0-9]{64})"/',$html,$clave);
        $_SERVER['REQUEST_METHOD']='POST';$_POST=['csrf_token'=>Csrf::generar(),'clave'=>$clave[1],'nombre'=>'Inválida','descripcion'=>'Prueba','tipo_ayuda'=>'insumos'];
        $_FILES=['imagenes'=>['name'=>['script.php'],'tmp_name'=>[$foto],'error'=>[0],'size'=>[filesize($foto)]]];[$codigo]=$render();
        TestUtils::comprobar($codigo===422&&count(glob($tmp.'/destino/*'))===$antes,'Una foto inválida no genera archivos ni campañas');
        // Un fallo después de escribir la foto debe limpiar también el archivo físico.
        $controller=new CrearCampanaController($pdo,new ImagenesCampana($tmp.'/fallidas','subidas/../invalida',static fn($ruta)=>is_file($ruta),static fn($a,$b)=>copy($a,$b)));
        $_SERVER['REQUEST_METHOD']='GET';$_POST=[];$_FILES=[];[$codigo,$html]=$render();preg_match('/name="clave" value="([a-f0-9]{64})"/',$html,$clave);
        $_SERVER['REQUEST_METHOD']='POST';$_POST=$datosValidos;$_POST['clave']=$clave[1];$_POST['nombre']=$f['prefijo'].' Debe revertirse';
        $_FILES=['imagenes'=>['name'=>['foto.png'],'tmp_name'=>[$foto],'error'=>[0],'size'=>[filesize($foto)]]];
        [$codigo]=$render();$stmt=$pdo->prepare('SELECT COUNT(*) FROM camp WHERE nombre=?');$stmt->execute([$_POST['nombre']]);
        TestUtils::comprobar($codigo===422&&(int)$stmt->fetchColumn()===0&&count(glob($tmp.'/fallidas/*')?:[])===0,'Un fallo posterior a escribir la foto borra el archivo y revierte la campaña');
        $_POST=[];$_FILES=[];$_SERVER['CONTENT_LENGTH']='99999999';[$codigo,$html]=$render();
        TestUtils::comprobar($codigo===413&&str_contains($html,'fotos más livianas'),'Informa un envío demasiado grande sin confundirlo con un error CSRF');
        // Forzar un fallo al relacionar una quinta foto debe revertir toda la campaña.
        $cuentaAntes=(int)$pdo->query('SELECT COUNT(*) FROM camp')->fetchColumn();
        $entrada=['nombre'=>'Fallo atómico','descripcion'=>'Prueba','categoria'=>(string)$f['alimentos'],'tipo_ayuda'=>'dinero','objetivo'=>'100','cbu'=>'0000000000000000000001'];
        try {(new CrearCampanaRepository($pdo))->publicar($owner,$entrada,fn($nuevo)=>$imagenes->guardarParaCampana($nuevo,array_fill(0,5,'subidas/campanas/foto.png')));throw new RuntimeException('No rechazó cinco fotos');}catch(InvalidArgumentException $e){}
        TestUtils::comprobar((int)$pdo->query('SELECT COUNT(*) FROM camp')->fetchColumn()===$cuentaAntes,'Un fallo al guardar imágenes revierte la campaña y su objetivo');
    } finally {
        $pdo->rollBack();foreach(glob($tmp.'/fallidas/*')?:[] as $ruta)unlink($ruta);if(is_dir($tmp.'/fallidas'))rmdir($tmp.'/fallidas');foreach(glob($tmp.'/destino/*')?:[] as $ruta)unlink($ruta);if(is_dir($tmp.'/destino'))rmdir($tmp.'/destino');if(is_file($tmp.'/foto.png'))unlink($tmp.'/foto.png');rmdir($tmp);
        [$_SESSION,$_POST,$_GET,$_FILES,$_SERVER,$codigo]=$original;http_response_code($codigo?:200);
    }
}
