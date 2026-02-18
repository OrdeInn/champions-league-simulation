#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p \
  storage/app \
  storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Make Laravel writable dirs usable across Docker Desktop (macOS/Windows) and WSL2/Linux.
# These paths are expected to be backed by Docker named volumes in docker-compose.yml.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

exec "$@"
