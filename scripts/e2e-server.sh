#!/usr/bin/env bash
set -euo pipefail

cleanup() {
  if [[ -n "${DB_DATABASE:-}" && -f "$DB_DATABASE" ]]; then
    rm -f -- "$DB_DATABASE"
  fi
}

trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
php artisan migrate --force --no-interaction
php artisan serve --host=127.0.0.1 --port=8787
