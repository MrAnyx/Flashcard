#!/bin/bash
set -e

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Enable and start the worker
systemctl --user enable messenger-worker@1.service
systemctl --user start messenger-worker@1.service

# Start Apache server
echo "Starting Apache..."
exec apache2-foreground

exec docker-php-entrypoint "$@"
