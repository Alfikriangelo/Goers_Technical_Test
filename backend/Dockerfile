# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1 — Composer dependencies
# ---------------------------------------------------------------------------
# Installed in an isolated stage so application source changes do not bust
# the (slow) composer layer. Dev dependencies are kept on purpose: the image
# is also the place where `php artisan test` runs against PostgreSQL.
FROM composer:2 AS vendor

WORKDIR /app

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs \
        --no-scripts

COPY backend/ ./

RUN composer dump-autoload --optimize --no-scripts

# ---------------------------------------------------------------------------
# Stage 2 — Runtime (Apache + PHP 8.3 + pdo_pgsql)
# ---------------------------------------------------------------------------
FROM php:8.3-apache AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Document root -> Laravel's public/ directory.
COPY backend/docker/apache-laravel.conf /etc/apache2/conf-available/laravel.conf
RUN a2enconf laravel \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY backend/ ./
COPY --from=vendor /app/vendor ./vendor

# phpdotenv (and PHPUnit) warn when no .env file exists at all. Real values are
# injected by Docker Compose, which always wins over file values.
RUN printf '%s\n' 'APP_ENV=production' > .env

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY backend/docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
