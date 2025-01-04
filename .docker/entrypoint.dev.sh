#!/bin/bash
set -e

pm2-runtime start /usr/local/bin/ecosystem.config.cjs

exec docker-php-entrypoint "$@"
