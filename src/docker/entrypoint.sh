#!/usr/bin/env sh
set -e

if [ -z "${APP_KEY}" ]; then
    echo "ERROR: falta APP_KEY. Ejecute el contenedor con --env-file .env.docker" >&2
    exit 1
fi

# Cachea configuración, rutas y vistas con las variables de entorno del contenedor.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# RUN_MIGRATIONS=true aplica las migraciones al arrancar.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
