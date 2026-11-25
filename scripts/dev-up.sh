#!/usr/bin/env bash
# One-command local start: builds the images if needed, waits for MySQL, runs
# migrations and prints the URLs.
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    cp .env.example .env
    echo "created .env from .env.example"
fi

docker compose up -d --build

printf 'waiting for MySQL'
for _ in $(seq 1 60); do
    if docker compose exec -T app php -r 'exit(@fsockopen("mysql",3306) ? 0 : 1);' >/dev/null 2>&1; then
        printf '\nMySQL is reachable\n'
        break
    fi
    printf '.'
    sleep 2
done

docker compose exec -T app php artisan migrate --seed --force
docker compose exec -T app php artisan storage:link || true

cat <<'EOF'

Dekolife is up:
  storefront / panels : http://localhost:8080
  mail catcher        : http://localhost:8025
  mysql               : localhost:3307 (user dekolife / db dekolife)

Stop with: docker compose down
Shell     : docker compose exec app bash
EOF
