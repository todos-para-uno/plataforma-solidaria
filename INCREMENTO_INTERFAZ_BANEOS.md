# Incremento: gestión de baneos desde Administración

Se añadió el botón de banear/finalizar baneo en las filas expandidas de usuarios comunes. Se reutilizan los endpoints `banear` y `finalizar_baneo`, con CSRF y autorización del lado servidor. La consulta de usuarios devuelve `id_baneo_vigente` para finalizar el registro correcto sin alterar el historial.

No se modificaron `database/schema.sql`, `public_html/` ni otras vistas. No se ejecutaron pruebas de integración con MySQL en este entorno.

Pruebas manuales: banear usuario común con motivo; comprobar estado y bloqueo de ingreso; finalizar baneo; comprobar historial; comprobar que administradores no muestran acciones de baneo; comprobar error de fecha inválida y CSRF; repetir suite completa.
