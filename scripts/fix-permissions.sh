#!/usr/bin/env bash
# One-shot ownership fix for Linux/macOS hosts (Windows bind mounts do not need it).
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose exec -T -u root app chown -R www-data:www-data storage bootstrap/cache
echo "storage and bootstrap/cache are owned by www-data"
