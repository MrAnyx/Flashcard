#!/bin/bash

# Original supervisord configuration template
SUPERVISORD_CONF="/etc/supervisor/conf.d/supervisord.conf"

# Get all host environment variables and format them for supervisord
ENV_VARS=$(printenv | sed 's/^\(.*\)$/\1/' | tr '\n' ',' | sed 's/,$//')

# Create the final supervisord configuration
echo "[supervisord]" >> $SUPERVISORD_CONF
echo "nodaemon=true" >> $SUPERVISORD_CONF
echo "logfile=/dev/null" >> $SUPERVISORD_CONF
echo "logfile_maxbytes=0" >> $SUPERVISORD_CONF
echo "environment=${ENV_VARS}" >> $SUPERVISORD_CONF
