#!/bin/sh
set -eu

: "${AUTOCTRL_BASIC_AUTH_USER:?Configura AUTOCTRL_BASIC_AUTH_USER en EasyPanel}"
: "${AUTOCTRL_BASIC_AUTH_PASSWORD:?Configura AUTOCTRL_BASIC_AUTH_PASSWORD en EasyPanel}"
: "${AUTOCTRL_DB_HOST:?Configura AUTOCTRL_DB_HOST en EasyPanel}"
: "${AUTOCTRL_DB_NAME:?Configura AUTOCTRL_DB_NAME en EasyPanel}"
: "${AUTOCTRL_DB_USER:?Configura AUTOCTRL_DB_USER en EasyPanel}"
: "${AUTOCTRL_DB_PASS:?Configura AUTOCTRL_DB_PASS en EasyPanel}"

umask 077
printf '%s\n' "$AUTOCTRL_BASIC_AUTH_PASSWORD" \
  | htpasswd -i -B -n "$AUTOCTRL_BASIC_AUTH_USER" > /etc/apache2/autocontrol.htpasswd
chown root:www-data /etc/apache2/autocontrol.htpasswd
chmod 640 /etc/apache2/autocontrol.htpasswd

exec docker-php-entrypoint "$@"
