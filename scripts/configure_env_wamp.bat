@echo off
setlocal EnableDelayedExpansion
set "DBPASS=%~1"
set "ENV_FILE=%~dp0..\.env"

if not exist "%ENV_FILE%" (
  copy "%~dp0..\.env.example" "%ENV_FILE%" >nul
)

powershell -NoProfile -Command ^
  "$envFile = '%ENV_FILE%';" ^
  "$pass = '%DBPASS%';" ^
  "$lines = Get-Content $envFile;" ^
  "$lines = $lines | ForEach-Object { if ($_ -match '^DB_USERNAME=') { 'DB_USERNAME=root' } elseif ($_ -match '^DB_PASSWORD=') { 'DB_PASSWORD=' + $pass } else { $_ } };" ^
  "if (-not ($lines -match '^DB_USERNAME=')) { $lines += 'DB_USERNAME=root' };" ^
  "if (-not ($lines -match '^DB_PASSWORD=')) { $lines += ('DB_PASSWORD=' + $pass) };" ^
  "$lines | Set-Content -Encoding UTF8 $envFile"

set "DB_PORT=3306"
set "SGEP_DB_PASS=%DBPASS%"
call "%~dp0php.cmd" "%~dp0detect_wamp_db_port.php" > "%TEMP%\sgep_db_port.txt" 2>nul
set "SGEP_DB_PASS="
if exist "%TEMP%\sgep_db_port.txt" (
  set /p DB_PORT=<"%TEMP%\sgep_db_port.txt"
  del "%TEMP%\sgep_db_port.txt" >nul 2>&1
)

powershell -NoProfile -Command ^
  "$envFile = '%ENV_FILE%';" ^
  "$port = '%DB_PORT%';" ^
  "$lines = Get-Content $envFile;" ^
  "$lines = $lines | ForEach-Object { if ($_ -match '^DB_PORT=') { 'DB_PORT=' + $port } else { $_ } };" ^
  "if (-not ($lines -match '^DB_PORT=')) { $lines += ('DB_PORT=' + $port) };" ^
  "$lines | Set-Content -Encoding UTF8 $envFile"

echo Configuracion .env ajustada (usuario root, puerto %DB_PORT%).

exit /b 0
