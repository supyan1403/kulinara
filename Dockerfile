# Runtime Laravel di Vercel Container Runtime (FrankenPHP + Caddy).
# Runtime vercel-php community sudah rusak permanen di platform sejak
# rollout Managed Images 2026-08 (vercel-community/php#650) dan repo-nya
# sudah di-archive, sehingga jalur serverless PHP tidak lagi layak.
# Image ini juga bisa dijalankan lokal: docker build -t kulinara . && docker run -p 8080:80 kulinara

FROM dunglas/frankenphp:1-php8.4-bookworm AS base

# Ekstensi wajib proyek (composer.json: ext-pdo, ext-pdo_pgsql) + zip
# untuk instalasi composer berbasis dist.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && install-php-extensions pdo_pgsql zip

WORKDIR /app

FROM base AS dependencies

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js postcss.config.js tailwind.config.js jsconfig.json ./
COPY resources ./resources
# tailwind.config.js merujuk view pagination bawaan Laravel di vendor/
# (vendor sendiri di-exclude lewat .dockerignore). Tanpa path ini class
# CSS tombol pagination hilang dari bundle build.
COPY --from=dependencies /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

FROM base AS runtime

# Binary composer ikut dibawa: stage runtime menjalankan dump-autoload +
# package:discover setelah seluruh aplikasi tersalin (script itu butuh
# seluruh app, jadi tidak bisa dijalankan di stage dependencies).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=dependencies --chown=www-data:www-data /app/vendor ./vendor
COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts

# package:discover diakhiri `|| true` sadar: hasilnya (bootstrap/cache/packages.php)
# hanya cache — Laravel menemukan package lagi saat runtime karena
# bootstrap/cache writable oleh www-data. Build tidak boleh gagal karenanya.
RUN php artisan package:discover --ansi -vvv 2>&1 || true

RUN chown -R www-data:www-data storage bootstrap/cache

COPY --chown=www-data:www-data Caddyfile /etc/frankenphp/Caddyfile

RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /config/caddy /data/caddy

# LOG_CHANNEL=stderr supaya log terbaca Vercel runtime logs (FS container
# tidak durable). Nilai DB_*, APP_KEY, dll datang dari env Vercel saat runtime.
ENV PORT=80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_STORE=array \
    SESSION_DRIVER=cookie \
    QUEUE_CONNECTION=sync

USER www-data

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
