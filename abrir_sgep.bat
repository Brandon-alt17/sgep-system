@echo off
setlocal
rem URL por defecto (WAMP en C:\wamp64\www\sgep). Ajuste si cambio APP_URL en .env
set "SGEP_URL=http://localhost/sgep/"

php --version >nul 2>&1
if %errorlevel% neq 0 (
  echo.
  echo AVISO: PHP no esta en PATH. Verifique que WAMP este en verde.
  echo Se abrira el navegador de todos modos...
  echo.
  timeout /t 2 >nul
)

start "" "%SGEP_URL%"
