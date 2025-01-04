#!/bin/bash
set -e

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

cat /etc/environment \
    | sed -e 's/"//g' \
    | awk -F= '{key=$1}{value=$2} BEGIN {printf "environment="} NR>1 {printf ","} {printf key"=\42"value"\42"} END {printf "\n"}' \
    >> /etc/supervisor/conf.d/supervisord.conf

# Update and start supervisor service
echo "Starting supervisor..."
service supervisor start
supervisorctl reread
supervisorctl update
supervisorctl restart all

exec docker-php-entrypoint "$@"
