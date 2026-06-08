# syntax=docker/dockerfile:1

# --- Stage 1: app PHP + dipendenze composer + file Wayfinder ---
# serversideup/php include composer; qui installiamo le dipendenze e
# generiamo i file TS di Wayfinder (servono allo stage Node per il build Vite).
FROM serversideup/php:8.4-fpm-nginx AS php-build

USER root
RUN install-php-extensions pdo_pgsql
USER www-data

WORKDIR /var/www/html

# Dipendenze composer (layer cache: prima solo i manifest)
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Codice applicazione
COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# File Wayfinder (resources/js/actions|routes|wayfinder) usati dal build Vite.
# Gira senza DB: legge solo la route table.
RUN php artisan wayfinder:generate --with-form

# --- Stage 2: build degli asset frontend (Vite) ---
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
# npm install (non ci): il lockfile può divergere tra macOS e linux/alpine per
# le dipendenze native opzionali (rollup, @emnapi). install le risolve a runtime.
RUN npm install --no-audit --no-fund
COPY . .

# File Wayfinder pre-generati dallo stage PHP (qui non c'è php)
COPY --from=php-build /var/www/html/resources/js/actions ./resources/js/actions
COPY --from=php-build /var/www/html/resources/js/routes ./resources/js/routes
COPY --from=php-build /var/www/html/resources/js/wayfinder ./resources/js/wayfinder

# Salta il plugin Wayfinder nel build: i file ci sono già, php no
ENV WAYFINDER_SKIP=1
RUN npm run build

# --- Stage 3: immagine finale (nginx + php-fpm già pronti) ---
FROM serversideup/php:8.4-fpm-nginx AS app

# pdo_pgsql per Neon (Postgres)
USER root
RUN install-php-extensions pdo_pgsql
USER www-data

WORKDIR /var/www/html

# App + vendor + file Wayfinder dallo stage PHP, asset compilati dallo stage Node
COPY --from=php-build --chown=www-data:www-data /var/www/html ./
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# serversideup/php espone nginx sulla 8080 (gira come www-data, non-root)
ENV APP_ENV=production \
    APP_DEBUG=false
EXPOSE 8080
