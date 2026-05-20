# Instalacion del sistema

## Requisitos

- XAMPP con Apache, PHP y MySQL/MariaDB.
- PHP 8 o superior.
- Base de datos `control_asistencia`.
- Proyecto ubicado en `C:\xampp\htdocs\asistencia_facial`.

## Configuracion XAMPP

1. Inicie Apache desde el panel de XAMPP.
2. Inicie MySQL/MariaDB usando el puerto `3307`.
3. Verifique que MySQL responda con:

```bat
C:\xampp\mysql\bin\mysql.exe -P 3307 -u root -e "SELECT VERSION();"
```

## Archivo .env

El sistema lee la configuracion desde `.env`:

```env
APP_ENV=local
APP_KEY=clave-larga-aleatoria
DB_HOST=localhost
DB_PORT=3307
DB_NAME=control_asistencia
DB_USER=root
DB_PASS=
```

`APP_KEY` debe conservarse estable. Si se cambia, los datos sensibles cifrados con la clave anterior no se podran leer.

## Base de datos

Si instala desde cero, importe el respaldo principal o el SQL del proyecto sobre la base `control_asistencia`.

```bat
C:\xampp\mysql\bin\mysql.exe -P 3307 -u root control_asistencia < database\control_asistencia.sql
```

## Aplicar migraciones

Las migraciones estan en `database\migrations` y se ejecutan en orden por nombre:

```bat
scripts\run_migrations.bat
```

Use nombres con formato `YYYY_MM_DD_descripcion.sql` y prefiera sentencias idempotentes como `IF NOT EXISTS`.

## Verificacion rapida

Ejecute:

```bat
C:\xampp\php\php.exe tests\integrity_check.php
C:\xampp\php\php.exe tests\referential_integrity_check.php
C:\xampp\php\php.exe tests\http_endpoints_check.php
C:\xampp\php\php.exe tests\upload_audit.php
```

Abra el sistema en:

```text
http://localhost/asistencia_facial/
```

## Backup y restore

La guia esta en `BACKUP_RESTORE.md`.
