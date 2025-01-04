#!/bin/bash
set -e

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Generate Supervisor configuration
echo "Generating Supervisor configuration..."
SUPERVISORD_CONF="/etc/supervisor/conf.d/supervisord.conf"
ENV_VARS=$(printenv | awk -F= '{print $1"=\""$2"\""}' | tr '\n' ',' | sed 's/,$//')
ESCAPED_ENV_VARS=$(echo "$ENV_VARS" | sed 's/"/\\"/g')
echo "" >> $SUPERVISORD_CONF
echo "[supervisord]" >> $SUPERVISORD_CONF
echo "nodaemon=true" >> $SUPERVISORD_CONF
echo "logfile=/dev/null" >> $SUPERVISORD_CONF
echo "logfile_maxbytes=0" >> $SUPERVISORD_CONF
echo "environment=${ESCAPED_ENV_VARS}" >> $SUPERVISORD_CONF

# Update and start supervisor service
echo "Starting supervisor..."
service supervisor start
supervisorctl reread
supervisorctl update
supervisorctl restart all
