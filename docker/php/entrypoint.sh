#!/bin/sh
set -e

# Storage and cache directories must exist inside the bind-mounted tree as well,
# otherwise a fresh clone cannot write sessions, views or logs.
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# The image runs as www-data (see Dockerfile). A bind mount from Windows presents
# permissive permissions, so no chown is required; if a Linux host is used, run the
# ownership fix script once (scripts/fix-permissions.sh).
exec "$@"
