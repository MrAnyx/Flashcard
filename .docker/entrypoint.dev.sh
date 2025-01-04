#!/bin/bash
set -e

# Update and start supervisor service
echo "Starting supervisor..."
service supervisor start
supervisorctl reread
supervisorctl update
supervisorctl restart all

exec docker-php-entrypoint "$@"
