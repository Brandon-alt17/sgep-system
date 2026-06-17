#!/usr/bin/env bash
set -euo pipefail

# Ajuste si cambio APP_URL en .env (MAMP usa puerto 8888 por defecto)
SGEP_URL="${SGEP_URL:-http://localhost:8888/sgep/}"

open "$SGEP_URL"
