#!/bin/sh
# The workers run as nginx and create the files; the app user deletes the old ones.
set -e

mkdir -p /var/log/nginx/app
chmod 0777 /var/log/nginx/app
