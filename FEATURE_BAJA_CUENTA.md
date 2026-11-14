# Baja y anonimización de cuentas

## Instalación

1. Hacer un respaldo de la base y los archivos de usuario.
2. Ejecutar `database/migrations/20261104_baja_cuenta.sql` **una sola vez** en la base existente. En una instalación desde cero, `database/schema.sql` ya contiene las columnas.
3. Verificar permisos de lectura y borrado del usuario PHP sobre `data/usuarios/` y `public_php/subidas/campanas/`.
4. Probar en una base de desarrollo antes de publicar.

## Reglas

- Baja simple: `usuarios.estado_cuenta = desactivada`; el próximo ingreso correcto solicita reactivación y solo entonces abre sesión.
- Baja con eliminación: `usuarios.estado_cuenta = eliminada`; correo aleatorio con dominio `.invalid`, contraseña invalidada, datos básicos retirados y cierre de campañas activas (`camp.estado = cerrada`, `motivo_cierre = usuario_eliminado`).
- Se conservan ID, campañas, objetivos y donaciones para el historial. Las campañas cerradas dejan de aparecer en búsqueda y no permiten registrar insumos, porque los repositorios existentes comprueban `estado = activa`.
- Se retiran imágenes subidas de campañas del creador y avatar. Se vacían referencias a ubicación, CBU y descripción de sus campañas; se anonimizan contenidos de mensajes relacionados y consultas de contacto asociadas a su cuenta.

## Límites y revisión necesaria

**Esta implementación no garantiza el borrado total de datos personales**: los nombres de campañas, textos de otros usuarios, motivos de baneos, logs, respaldos, adjuntos externos y copias en sistemas de terceros pueden contener información identificatoria. Revisar esas fuentes y establecer una política de retención antes de prometer eliminación integral.

Las operaciones SQL son transaccionales, pero los borrados del sistema de archivos se hacen después del `COMMIT`; un fallo de disco/permisos puede dejar archivos huérfanos (se registra el error). Para producción, incorporar una cola persistente de borrados con reintentos y auditoría.

La migración no se probó contra una instancia MySQL/MariaDB en este entorno. Tampoco se ejecutó la suite de pruebas porque el PHP CLI disponible carece de `mbstring` y drivers PDO.
