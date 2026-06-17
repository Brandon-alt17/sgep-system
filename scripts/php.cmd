@echo off
setlocal EnableDelayedExpansion
set "PHP_EXE="

where php >nul 2>&1 && (
  php -r "exit(version_compare(PHP_VERSION,'8.3.0','>=')?0:1);" >nul 2>&1
  if !errorlevel! equ 0 set "PHP_EXE=php"
)

if not defined PHP_EXE if exist "C:\wamp64\bin\php" (
  for /f "delims=" %%V in ('dir /b /ad /o-n "C:\wamp64\bin\php\php8.*" 2^>nul') do (
    if not defined PHP_EXE if exist "C:\wamp64\bin\php\%%V\php.exe" (
      "C:\wamp64\bin\php\%%V\php.exe" -r "exit(version_compare(PHP_VERSION,'8.3.0','>=')?0:1);" >nul 2>&1
      if !errorlevel! equ 0 set "PHP_EXE=C:\wamp64\bin\php\%%V\php.exe"
    )
  )
)

if not defined PHP_EXE (
  echo ERROR: No se encontro PHP 8.3 o superior en PATH ni en C:\wamp64\bin\php
  exit /b 1
)

"%PHP_EXE%" %*
