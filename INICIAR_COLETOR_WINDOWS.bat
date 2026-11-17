@echo off
setlocal
cd /d "%~dp0"
title Hospital IT NOC - collector
set "PS=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
if not exist "%PS%" (
 echo PowerShell nao encontrado.
 pause
 exit /b 1
)
"%PS%" -NoLogo -NoProfile -NoExit -ExecutionPolicy Bypass -File "%~dp0scripts\executar.ps1" -Mode collector
pause
