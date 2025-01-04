#!/bin/bash
set -e

# Generate Supervisor configuration
echo "Generating Supervisor configuration..."
exec /usr/local/bin/generate-supervisord-config.sh

# Update and start supervisor service
echo "Starting supervisor..."
service supervisor start
supervisorctl reread
supervisorctl update
supervisorctl restart all
