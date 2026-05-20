@echo off
setlocal
set LOG_DIR=%~dp0..\logs
forfiles /p "%LOG_DIR%" /m *.log /d -30 /c "cmd /c del @path"
echo Logs de mas de 30 dias eliminados.
