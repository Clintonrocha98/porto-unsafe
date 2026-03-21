FROM php:8.4-cli-alpine

WORKDIR /var/www

RUN apk add --no-cache \
    git \
    curl \
    unzip \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    linux-headers \
    icu-dev \
    $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    zip \
    pcntl \
    intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./
RUN composer install --optimize-autoloader --no-scripts

COPY . .

EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8000"]
