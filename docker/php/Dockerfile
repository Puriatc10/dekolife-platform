# syntax is intentionally omitted: no BuildKit-only feature is used, and a missing
# frontend image would add a failure point before the first FROM.
#
# Base images are Docker Official Images served through the public ECR mirror
# (public.ecr.aws/docker/library/*). Docker Hub is blocked on some development hosts,
# while public ECR is reachable from CI as well, so local and CI resolve the same
# tags. To use Docker Hub instead, rebuild with:
#   --build-arg PHP_IMAGE=php:8.4-fpm --build-arg NODE_IMAGE=node:22-alpine ...

ARG PHP_IMAGE=public.ecr.aws/docker/library/php:8.4-fpm
ARG NODE_IMAGE=public.ecr.aws/docker/library/node:22-alpine
ARG COMPOSER_IMAGE=public.ecr.aws/docker/library/composer:2

# ---------------------------------------------------------------------------
# Stage 1: front-end assets (Vite + Tailwind)
# ---------------------------------------------------------------------------
FROM ${NODE_IMAGE} AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2: PHP dependencies
# ---------------------------------------------------------------------------
FROM ${COMPOSER_IMAGE} AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
ARG INSTALL_DEV=false
RUN if [ "$INSTALL_DEV" = "true" ]; then \
        composer install --no-interaction --no-scripts --prefer-dist; \
    else \
        composer install --no-dev --no-interaction --no-scripts --prefer-dist; \
    fi

COPY . .
RUN composer dump-autoload --optimize --no-scripts

# ---------------------------------------------------------------------------
# Stage 3: runtime (php-fpm)
# ---------------------------------------------------------------------------
FROM ${PHP_IMAGE} AS runtime

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libonig-dev libxml2-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl zip gd exif pcntl opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Composer is copied in so `docker compose exec app composer …` works for developers and
# the CI gates (validate, audit) can run inside the same image as the application.
COPY --from=vendor /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

RUN mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data

EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
