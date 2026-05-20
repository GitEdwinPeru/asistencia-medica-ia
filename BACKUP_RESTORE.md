# Backup y restauracion

## Crear backup

Ejecute:

```bat
scripts\backup_database.bat
```

El archivo se guarda en `backups/` con fecha y hora.

## Restaurar backup

Ejecute:

```bat
scripts\restore_database.bat backups\control_asistencia_YYYYMMDD_HHMMSS.sql
```

Antes de restaurar, confirme que el backup corresponde a la version correcta de la base `control_asistencia`.

## Limpieza de logs

Para limpiar logs antiguos:

```bat
scripts\cleanup_logs.bat
```
