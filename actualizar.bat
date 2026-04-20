@echo off
setlocal
echo === Actualizacion SGEP ===

php --version >nul 2>&1
if %errorlevel% neq 0 (
  echo ERROR: No se encontro PHP en PATH.
  pause
  exit /b 1
)

php database/run_migrations.php
if %errorlevel% neq 0 (
  echo ERROR: Fallaron las migraciones durante la actualizacion.
  pause
  exit /b 1
)

echo Actualizacion finalizada.
pause
