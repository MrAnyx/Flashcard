#!/bin/bash
set -e

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# # Generate Supervisor configuration
# echo "Generating Supervisor configuration..."
# exec /usr/local/bin/generate-supervisord-config.sh

# # Update and start supervisor service
# echo "Starting supervisor..."
# service supervisor start
# supervisorctl reread
# supervisorctl update
# supervisorctl restart all

echo "Starting apache..."
exec apache2-foreground

exec docker-php-entrypoint "$@"
