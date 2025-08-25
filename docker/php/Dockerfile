# ─── Base image & PHP extensions ───────────────────────────────────────────
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim unzip git curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
  && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath \
  && rm -rf /var/lib/apt/lists/*

# ─── Composer CLI ───────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ─── Working directory ─────────────────────────────────────────────────────
WORKDIR /var/www/html

# ─── Copy app + install dependencies ───────────────────────────────────────
COPY src/ ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && rm -rf ~/.composer/cache \
    # Fix Laravel writable dirs
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775        storage bootstrap/cache

# ─── Supervisor ─────────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y supervisor \
  && rm -rf /var/lib/apt/lists/*
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ─── Entrypoint helper (fix perms on each start) ───────────────────────────
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# ─── Entrypoint & default command ──────────────────────────────────────────
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["sh", "-c", "php-fpm & /usr/bin/supervisord"]