# Incremento: campañas y donaciones por usuario

Rama sugerida: `feature/admin-campanas-donaciones`.

## Archivos
- `app/repositorios/AdministracionActividadRepository.php`: consultas de solo lectura, resumen y listas.
- `app/controladores/AdministracionUsuariosController.php`: endpoint `actividad()` con autorización en BD y validación.
- `public_php/index.php`: ruta GET `admin_actividad`.
- `app/vistas/administracion.php`: modal de actividad.
- `public_php/assets/js/administracion.js`: carga bajo demanda, resumen y modal.
- `public_php/assets/css/administracion.css`: estilos adaptables.
- `tests/controladores/AdministracionUsuariosControllerTest.php`: casos de acceso y validación.

## Verificación local
Ejecutar `php tests/run_tests.php` con la base de pruebas configurada. Este paquete solo fue validado mediante chequeos de sintaxis PHP/JS; la integración MySQL y las pruebas visuales deben hacerse localmente.

En el panel, expandir un usuario y comprobar el resumen, luego abrir campañas y donaciones. Probar también usuarios sin actividad, usuarios con varias campañas y donaciones monetarias/de insumos. No se suman importes y unidades de insumos entre sí.

## Nota
Las consultas no exponen CBU, contraseña, ni datos bancarios. No se modificó `database/schema.sql`, `public_html/` ni otras vistas.
