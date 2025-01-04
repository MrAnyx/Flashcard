#!/bin/bash
set -e


exec docker-php-entrypoint "$@"
