#!/usr/bin/env bash
# Runs exactly the gates CI runs, in CI order, and stops at the first failure.
#
# Usage:
#   bash scripts/check.sh              # auto: host php if present, else docker compose
#   RUNNER=host bash scripts/check.sh  # force host binaries
#   RUNNER=docker bash scripts/check.sh
#
# A gate that prints a summary without counts ("no tests collected") is a failure here
# too: the test step greps the pass count.
set -uo pipefail

cd "$(dirname "$0")/.." || exit 1

if [ -z "${RUNNER:-}" ]; then
    if command -v php >/dev/null 2>&1; then RUNNER=host; else RUNNER=docker; fi
fi

run() {
    if [ "$RUNNER" = "docker" ]; then
        docker compose exec -T app "$@"
    else
        "$@"
    fi
}

fail=0
step() { printf '\n=== %s\n' "$1"; }

step "1/5 Composer validate"
run composer validate --strict --no-check-publish || fail=1

step "2/5 Code style (Pint)"
run ./vendor/bin/pint --test || fail=1

step "3/5 Static analysis (PHPStan)"
run ./vendor/bin/phpstan analyse --no-progress || fail=1

step "4/5 Tests (Pest on MySQL)"
test_log="$(mktemp)"
run php artisan test --colors=never 2>&1 | tee "$test_log"
if ! grep -Eq "[1-9][0-9]* passed" "$test_log"; then
    echo "FAIL: no passing tests were reported (a green run with zero tests is a failure)"
    fail=1
fi
rm -f "$test_log"

step "5/5 Asset build"
if [ "$RUNNER" = "docker" ]; then
    docker run --rm -v "$PWD:/app" -w /app \
        "${NODE_IMAGE:-public.ecr.aws/docker/library/node:22-alpine}" \
        sh -c "npm ci --no-audit --no-fund && npm run build" || fail=1
else
    npm ci --no-audit --no-fund && npm run build || fail=1
fi

if [ "$fail" -ne 0 ]; then
    printf '\nRESULT: FAILED\n'
    exit 1
fi

printf '\nRESULT: all gates passed\n'
