#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ "$SCRIPT_DIR" == */scripts/macos ]]; then
  PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
else
  PROJECT_DIR="$SCRIPT_DIR"
fi
cd "$PROJECT_DIR"

echo "=== Actualizacion SGEP (macOS / MAMP) ==="

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

PHP_BIN="$(find_mamp_php || true)"
if [[ -z "$PHP_BIN" ]]; then
  echo "ERROR: No se encontro PHP de MAMP. Inicie MAMP (Start)."
  exit 1
fi

"$PHP_BIN" database/run_migrations.php || {
  echo "ERROR: Fallaron las migraciones."
  exit 1
}

echo "Actualizacion finalizada."
