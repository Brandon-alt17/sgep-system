#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ "$SCRIPT_DIR" == */scripts/macos ]]; then
  PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
else
  PROJECT_DIR="$SCRIPT_DIR"
fi
cd "$PROJECT_DIR"

echo "=== Instalador SGEP (macOS / MAMP) ==="
echo "Carpeta del proyecto: $PROJECT_DIR"
echo

find_mamp_php() {
  local candidate
  if [[ -d /Applications/MAMP/bin/php ]]; then
    while IFS= read -r candidate; do
      if [[ -x "$candidate" ]] && "$candidate" --version >/dev/null 2>&1; then
        echo "$candidate"
        return 0
      fi
    done < <(find /Applications/MAMP/bin/php -path '*/bin/php' -type f 2>/dev/null | sort -r)
  fi
  if command -v php >/dev/null 2>&1 && php --version >/dev/null 2>&1; then
    command -v php
    return 0
  fi
  return 1
}

configure_env_for_mamp() {
  if [[ ! -f .env ]]; then
    cp .env.example .env
    echo "Archivo .env creado desde .env.example"
  fi
  # MAMP: Apache 8888, MySQL 8889, password root
  if [[ "$(uname)" == "Darwin" ]]; then
    sed -i '' 's|^APP_URL=.*|APP_URL=http://localhost:8888/sgep|' .env 2>/dev/null \
      || sed -i 's|^APP_URL=.*|APP_URL=http://localhost:8888/sgep|' .env
    sed -i '' 's|^DB_PORT=.*|DB_PORT=8889|' .env 2>/dev/null \
      || sed -i 's|^DB_PORT=.*|DB_PORT=8889|' .env
    sed -i '' 's|^DB_PASSWORD=.*|DB_PASSWORD=root|' .env 2>/dev/null \
      || sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD=root|' .env
    echo "Configuracion .env ajustada para MAMP (puerto MySQL 8889)."
  fi
}

PHP_BIN="$(find_mamp_php || true)"
if [[ -z "$PHP_BIN" ]]; then
  echo "ERROR: No se encontro PHP de MAMP."
  echo "  1. Abra la app MAMP y pulse Start"
  echo "  2. Verifique que exista: /Applications/MAMP/bin/php/"
  exit 1
fi
echo "PHP encontrado: $PHP_BIN"

MYSQL_BIN=""
MYSQL_PORT="8889"
for candidate in \
  /Applications/MAMP/Library/bin/mysql \
  /Applications/MAMP/bin/mysql/bin/mysql \
  mysql; do
  if [[ -x "$candidate" ]] || command -v "$candidate" >/dev/null 2>&1; then
    if "$candidate" --version >/dev/null 2>&1; then
      MYSQL_BIN="$candidate"
      break
    fi
  fi
done

configure_env_for_mamp

read -r -s -p "Contrasena MySQL (Enter = root en MAMP): " DBPASS
echo
if [[ -z "$DBPASS" ]]; then
  DBPASS="root"
fi
if [[ "$DBPASS" != "root" ]]; then
  sed -i '' "s|^DB_PASSWORD=.*|DB_PASSWORD=$DBPASS|" .env 2>/dev/null \
    || sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DBPASS|" .env
fi

"$PHP_BIN" -r "require 'vendor/autoload.php';" || {
  echo "ERROR: No se pudo cargar vendor/autoload.php."
  exit 1
}

echo "Creando base de datos sgep (si no existe)..."
"$PHP_BIN" database/ensure_database.php || {
  echo "ERROR: No se pudo crear la base de datos."
  echo "Verifique que MAMP tenga MySQL en marcha (Start)."
  exit 1
}

"$PHP_BIN" database/run_migrations.php || {
  echo "ERROR: Fallaron las migraciones."
  exit 1
}

if [[ -f database/seeds/aprendices_sample.sql && -n "$MYSQL_BIN" ]]; then
  "$MYSQL_BIN" -u root -p"$DBPASS" -P "$MYSQL_PORT" -h 127.0.0.1 sgep \
    < database/seeds/aprendices_sample.sql \
    || echo "AVISO: No se pudo cargar el seed opcional."
fi

SGEP_URL="http://localhost:8888/sgep/"
echo
echo "Instalacion finalizada."
echo "Abra: $SGEP_URL"
echo
read -r -p "Abrir en el navegador ahora? (s/N): " OPEN
OPEN_LC="$(printf '%s' "$OPEN" | tr '[:upper:]' '[:lower:]')"
if [[ "$OPEN_LC" == "s" ]]; then
  open "$SGEP_URL"
fi
