# syntax=docker/dockerfile:1

# --- Stage 1: build degli asset frontend (Vite) ---
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# --- Stage 2: app PHP (nginx + php-fpm già pronti nell'immagine) ---
FROM serversideup/php:8.4-fpm-nginx AS app

# pdo_pgsql per Neon (Postgres)
USER root
RUN install-php-extensions pdo_pgsql
USER www-data

WORKDIR /var/www/html

# Dipendenze composer (layer cache: prima solo i manifest)
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Codice applicazione + asset compilati dallo stage 1
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-scripts

# serversideup/php espone nginx sulla 8080 (gira come www-data, non-root)
ENV APP_ENV=production \
    APP_DEBUG=false
EXPOSE 8080
