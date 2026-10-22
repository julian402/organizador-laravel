#!/usr/bin/env bash
# EC2 #1 — Servidor de aplicación (Ubuntu Server 24.04 LTS): Nginx + PHP-FPM + Laravel
# Uso:  sudo REPO_URL=https://github.com/usuario/organizador.git bash app-setup.sh
set -euo pipefail

: "${REPO_URL:?Defina REPO_URL (repositorio Git del proyecto)}"
APP_DIR="${APP_DIR:-/var/www/organizador}"
PHP_VERSION="${PHP_VERSION:-8.3}"

apt-get update
apt-get install -y nginx git unzip curl \
    php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-pgsql php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml php${PHP_VERSION}-curl php${PHP_VERSION}-zip php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath postgresql-client

# Composer
if ! command -v composer >/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Código
if [ ! -d "${APP_DIR}" ]; then
    git clone "${REPO_URL}" "${APP_DIR}"
else
    git -C "${APP_DIR}" pull
fi

cd "${APP_DIR}/src"
composer install --no-dev --optimize-autoloader --no-interaction

if [ ! -f .env ]; then
    cp ../infra/aws/env.production.example .env
    php artisan key:generate --force
    echo ">> Edite ${APP_DIR}/src/.env (DB_HOST, DB_PASSWORD, APP_URL) y vuelva a ejecutar este script."
    exit 0
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Nginx
sed "s#__APP_DIR__#${APP_DIR}#g; s#__PHP_VERSION__#${PHP_VERSION}#g" \
    "${APP_DIR}/infra/aws/nginx/organizador.conf" > /etc/nginx/sites-available/organizador
ln -sf /etc/nginx/sites-available/organizador /etc/nginx/sites-enabled/organizador
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable nginx php${PHP_VERSION}-fpm
systemctl reload nginx

echo "App desplegada. Pruebe: curl http://localhost/salud"
