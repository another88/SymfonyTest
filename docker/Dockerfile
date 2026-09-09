FROM php:8.4-fpm-alpine

# Системные зависимости, нужные для сборки расширений
RUN apk update \
    && apk upgrade --available \
    && apk add --no-cache \
        autoconf \
        build-base \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        linux-headers \
        git \
        bash

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP-расширения:
#   intl      — локали и форматирование
#   bcmath    — точная арифметика
#   pdo_pgsql — работа с PostgreSQL
#   sockets   — нужен транспортам Messenger
#   zip       — распаковка пакетов composer
RUN docker-php-ext-install -j"$(nproc)" \
        intl \
        bcmath \
        pdo_pgsql \
        sockets \
        zip

WORKDIR /app
