# Pin to a patched patch-release. 8.3.36 fixes CVE-2026-109618 / CVE-2026-109619
# (php2shell, unauthenticated RCE, CVSS 9.2). Do not revert to the floating
# 8.3-fpm tag: pinning makes rebuilds reproducible.
FROM php:8.3.36-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP-FPM config
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html
