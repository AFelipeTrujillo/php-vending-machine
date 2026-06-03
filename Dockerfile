FROM php:8.4-cli-alpine

RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --optimize-autoloader

COPY . .

RUN mkdir -p database

EXPOSE 8080

CMD ["sh", "-c", "php bin/migrate.php && php -S 0.0.0.0:8080 -t public"]
