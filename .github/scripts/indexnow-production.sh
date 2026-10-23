#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 3 ]]; then
    echo "Usage: indexnow-production.sh <root-name> <current-link> <verify-key|submit-existing>" >&2
    exit 2
fi
root_name="$1"
current_name="$2"
operation="$3"
for path_part in "$root_name" "$current_name"; do
    [[ "$path_part" =~ ^[A-Za-z0-9._-]+$ ]] || { echo "Invalid deployment path configuration." >&2; exit 2; }
done
case "$operation" in
    verify-key)
        artisan_args=(indexnow:submit-existing --verify-key)
        ;;
    submit-existing)
        artisan_args=(indexnow:submit-existing --execute --batch=100 --max-batches=100)
        ;;
    *)
        echo "Unsupported IndexNow operation; choose verify-key or submit-existing." >&2
        exit 2
        ;;
esac

cd "$HOME/$root_name/$current_name"
printf 'Starting reusable production IndexNow operation: %s\n' "$operation"
php artisan "${artisan_args[@]}"
echo "Production IndexNow operation completed: $operation"
