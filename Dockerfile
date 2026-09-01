FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev libzip-dev unzip git \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Easypanel puede pasar variables como build-arg; las convertimos a ENV para runtime.
ARG SUPABASE_DB_HOST
ARG SUPABASE_DB_PORT=5432
ARG SUPABASE_DB_NAME=postgres
ARG SUPABASE_DB_USER=postgres
ARG SUPABASE_DB_PASSWORD
ARG SUPABASE_URL
ARG SUPABASE_SERVICE_KEY
ARG SUPABASE_BUCKET=BIENES
ARG SUPABASE_BUCKET_PUBLIC=false

ENV SUPABASE_DB_HOST=$SUPABASE_DB_HOST \
    SUPABASE_DB_PORT=$SUPABASE_DB_PORT \
    SUPABASE_DB_NAME=$SUPABASE_DB_NAME \
    SUPABASE_DB_USER=$SUPABASE_DB_USER \
    SUPABASE_DB_PASSWORD=$SUPABASE_DB_PASSWORD \
    SUPABASE_URL=$SUPABASE_URL \
    SUPABASE_SERVICE_KEY=$SUPABASE_SERVICE_KEY \
    SUPABASE_BUCKET=$SUPABASE_BUCKET \
    SUPABASE_BUCKET_PUBLIC=$SUPABASE_BUCKET_PUBLIC

COPY composer.json composer.lock* /var/www/html/
WORKDIR /var/www/html
RUN composer install --no-dev --no-interaction --optimize-autoloader

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
