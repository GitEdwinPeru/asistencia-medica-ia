# Checklist de despliegue

## Antes de publicar

- Confirmar que `.env` existe en el servidor y no se sube al repositorio.
- Confirmar `APP_ENV=production`.
- Confirmar que `APP_KEY` es largo, aleatorio y estable.
- Confirmar credenciales MySQL correctas en `.env`.
- Ejecutar `scripts\run_migrations.bat`.
- Ejecutar pruebas:

```bat
C:\xampp\php\php.exe tests\integrity_check.php
C:\xampp\php\php.exe tests\referential_integrity_check.php
C:\xampp\php\php.exe tests\http_endpoints_check.php
C:\xampp\php\php.exe tests\upload_audit.php
```

## Seguridad

- Cambiar o desactivar credenciales de prueba.
- Verificar que Apache no liste directorios.
- Verificar que `uploads/fotos/.htaccess` bloquee ejecucion de scripts.
- Confirmar HTTPS si el sistema se publica fuera de la red local.
- Revisar permisos de usuarios y dejar solo administradores necesarios.
- Confirmar que backups se almacenan fuera del directorio publico.
- Confirmar que `vendor/`, `.env`, `logs/` y `backups/` no sean accesibles publicamente.

## Operacion

- Crear backup antes de migrar.
- Probar login administrador.
- Probar registro/edicion de empleado.
- Probar marcacion facial con camara real.
- Probar exportacion Excel/PDF de asistencia y empleados.
- Revisar `logs/` despues de la primera jornada de uso.
