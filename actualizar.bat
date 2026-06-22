@echo off
setlocal
echo === Actualizacion SGEP ===

call "%~dp0scripts\php.cmd" --version >nul 2>&1
if %errorlevel% neq 0 (
  echo ERROR: No se encontro PHP 8.3+ en PATH ni en WAMP.
  pause
  exit /b 1
)

call "%~dp0scripts\php.cmd" database/run_migrations.php
if %errorlevel% neq 0 (
  echo ERROR: Fallaron las migraciones durante la actualizacion.
  pause
  exit /b 1
)

echo Actualizacion finalizada.
pause
