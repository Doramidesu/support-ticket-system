FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm install 
COPY . .
RUN npm run build

FROM php:8.5-cli-alpine AS php-builder
WORKDIR /var/www
# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# Install minimal build dependencies
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev
# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl
# Cleanup build dependencies
RUN apk del .build-deps
# Copy and install PHP dependencies
COPY composer*.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
COPY . .
RUN composer dump-autoload --optimize

FROM php:8.5-cli-alpine AS production
WORKDIR /var/www
# Install only runtime dependencies
RUN apk add --no-cache \
    libpng \
    libzip \
    oniguruma \
    libxml2 \
    icu-libs \
    mysql-client \
    bash
# Install PHP extensions
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    libpng-dev libzip-dev oniguruma-dev libxml2-dev icu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apk del .build-deps
# Copy application files from builder (exclude node_modules)
COPY --from=php-builder /var/www/vendor /var/www/vendor
COPY --from=php-builder /var/www/composer.json /var/www/composer.lock /var/www/
COPY --from=node-builder /app/public/build /var/www/public/build
COPY . .
COPY /.env.example .env
# Create storage and cache directories if they don't exist
RUN mkdir -p /var/www/storage/app \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache
# Set proper permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache
# Copy entrypoint script
COPY --chmod=755 entrypoint.sh /entrypoint.sh
# Expose port 8000
EXPOSE 8000
# Use entrypoint script
ENTRYPOINT ["/entrypoint.sh"]
