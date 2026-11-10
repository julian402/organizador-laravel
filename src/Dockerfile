# Contenedor de la aplicación Organizador (Laravel 12 + PHP 8.3 + Apache)
# La BD NO va en este contenedor: se conecta a PostgreSQL por DB_HOST (en AWS, la EC2 de BD).
#
#   docker build -t organizador-app .
#   docker run -d -p 80:80 --env-file .env.docker organizador-app

# --- Etapa 1: dependencias PHP ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev

# --- Etapa 2: imagen final ---
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql pgsql intl zip bcmath opcache \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# Apache sirve desde public/ y permite las URLs amigables de Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# No revelar versiones de Apache ni de PHP (las cabeceras de seguridad las pone la app)
RUN sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -ri 's/^expose_php = .*/expose_php = Off/' "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html
# --chown: en macOS/OneDrive los archivos llegan con permisos 700 y Apache (www-data) no podría leerlos
COPY --from=vendor --chown=www-data:www-data /app /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s CMD curl -fs http://localhost/up || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
