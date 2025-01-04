#!/bin/bash
set -e

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

pm2-runtime start /usr/local/bin/ecosystem.config.cjs

exec docker-php-entrypoint "$@"
