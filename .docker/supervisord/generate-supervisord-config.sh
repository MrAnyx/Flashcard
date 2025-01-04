#!/bin/bash

# Generate Supervisor configuration
echo "Generating Supervisor configuration..."
SUPERVISORD_CONF="/etc/supervisor/conf.d/supervisord.conf"
ENV_VARS=$(printenv | awk -F= '{print $1"=\""$2"\","}' | tr -d '\n' | sed 's/,$//')
echo "" >> $SUPERVISORD_CONF
echo "[supervisord]" >> $SUPERVISORD_CONF
echo "nodaemon=true" >> $SUPERVISORD_CONF
echo "logfile=/dev/null" >> $SUPERVISORD_CONF
echo "logfile_maxbytes=0" >> $SUPERVISORD_CONF
echo "environment=${ENV_VARS}" >> $SUPERVISORD_CONF
