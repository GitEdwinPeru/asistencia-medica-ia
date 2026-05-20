@echo off
setlocal
set MYSQL_BIN=C:\xampp\mysql\bin
set DB_NAME=control_asistencia
set BACKUP_DIR=%~dp0..\backups
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
for /f "tokens=1-4 delims=/ " %%a in ("%date%") do set TODAY=%%d%%b%%c
for /f "tokens=1-3 delims=:." %%a in ("%time%") do set NOW=%%a%%b%%c
set FILE=%BACKUP_DIR%\%DB_NAME%_%TODAY%_%NOW%.sql
"%MYSQL_BIN%\mysqldump.exe" -P 3307 -u root --routines --triggers %DB_NAME% > "%FILE%"
echo Backup creado: %FILE%
