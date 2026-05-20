# Revision de seguridad antes de produccion

## Estado revisado

- Formularios de escritura usan `POST` y token CSRF.
- Endpoints criticos de asistencia usan token temporal y rate limit.
- Usuarios, sedes, cargos, grupos y empleados usan baja logica.
- Archivos subidos se restringen por MIME real y tamano maximo de 2 MB.
- Las vistas antiguas mas sensibles fueron revisadas para escape HTML.
- Exportaciones PDF/Excel requieren sesion.
- Auditoria registra acciones administrativas principales.
- Login bloquea temporalmente por intentos fallidos.

## Pendientes operativos

- Usar HTTPS si el sistema se accede fuera del equipo local.
- Cambiar todas las credenciales por defecto antes de entregar.
- Mantener `APP_KEY` fuera del repositorio.
- Programar backup diario de base de datos.
- Definir retencion de logs segun politica interna.
- Revisar permisos de carpetas en Windows para que Apache no pueda modificar archivos de codigo.

## Comandos de control

```bat
scripts\run_migrations.bat
C:\xampp\php\php.exe tests\integrity_check.php
C:\xampp\php\php.exe tests\referential_integrity_check.php
C:\xampp\php\php.exe tests\http_endpoints_check.php
C:\xampp\php\php.exe tests\upload_audit.php
```
