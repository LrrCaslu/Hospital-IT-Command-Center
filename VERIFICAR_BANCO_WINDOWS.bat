@echo off
setlocal
cd /d "%~dp0"
title Hospital IT NOC - Verificacao do banco
set "PS=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
if not exist "%PS%" (
  echo [ERRO] Windows PowerShell nao encontrado.
  pause
  exit /b 1
)
"%PS%" -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\verificar_banco.ps1"
set "RESULT=%ERRORLEVEL%"
echo.
if "%RESULT%"=="0" (
  echo [OK] Comando finalizado com sucesso.
) else (
  echo [ERRO] Falha detectada. Consulte o arquivo LOG desta pasta.
)
echo.
echo Pressione qualquer tecla para fechar esta janela.
pause >nul
exit /b %RESULT%
