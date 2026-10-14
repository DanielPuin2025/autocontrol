#!/bin/sh
set -eu

: "${AUTOCTRL_DB_HOST:?Configura AUTOCTRL_DB_HOST en EasyPanel}"
: "${AUTOCTRL_DB_NAME:?Configura AUTOCTRL_DB_NAME en EasyPanel}"
: "${AUTOCTRL_DB_USER:?Configura AUTOCTRL_DB_USER en EasyPanel}"
: "${AUTOCTRL_DB_PASS:?Configura AUTOCTRL_DB_PASS en EasyPanel}"

exec docker-php-entrypoint "$@"
