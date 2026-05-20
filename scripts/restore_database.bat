@echo off
setlocal
if "%~1"=="" (
  echo Uso: restore_database.bat ruta\backup.sql
  exit /b 1
)
set MYSQL_BIN=C:\xampp\mysql\bin
set DB_NAME=control_asistencia
"%MYSQL_BIN%\mysql.exe" -P 3307 -u root %DB_NAME% < "%~1"
echo Restauracion completada desde: %~1
