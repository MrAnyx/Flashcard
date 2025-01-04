#!/bin/bash

ENV_FILE="$1"
shift

set -o allexport
source $ENV_FILE
set +o allexport

exec "$@"
