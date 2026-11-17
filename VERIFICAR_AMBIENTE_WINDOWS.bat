@echo off
setlocal
cd /d "%~dp0"
title Hospital IT Command Center - Verificacao
set "PS=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
if not exist "%PS%" (
 echo Windows PowerShell nao encontrado.
 pause
 exit /b 1
)
"%PS%" -NoLogo -NoProfile -NoExit -ExecutionPolicy Bypass -File "%~dp0scripts\diagnostico.ps1"
echo Abra VERIFICACAO_AMBIENTE.log para ver o diagnostico.
pause
