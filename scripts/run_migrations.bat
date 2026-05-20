@echo off
setlocal

set MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe
set DB_NAME=control_asistencia
set DB_USER=root
set DB_PORT=3307
set MIGRATIONS_DIR=%~dp0..\database\migrations

if not exist "%MIGRATIONS_DIR%" (
    echo No existe la carpeta de migraciones: %MIGRATIONS_DIR%
    exit /b 1
)

echo Aplicando migraciones de %MIGRATIONS_DIR%
for %%F in ("%MIGRATIONS_DIR%\*.sql") do (
    echo.
    echo Ejecutando %%~nxF
    "%MYSQL_BIN%" -P %DB_PORT% -u %DB_USER% %DB_NAME% < "%%F"
    if errorlevel 1 (
        echo Error aplicando %%~nxF
        exit /b 1
    )
)

echo.
echo Migraciones aplicadas correctamente.
endlocal
