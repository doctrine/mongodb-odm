ARG TRAVIS_PHP_VERSION=7.2
ARG COMPOSER_VERSION=1.8
ARG ADAPTER_VERSION="^1.0.0"
ARG DRIVER_VERSION="stable"

FROM composer:${COMPOSER_VERSION}
FROM php:${TRAVIS_PHP_VERSION}-cli

COPY --from=composer /usr/bin/composer /usr/local/bin/composer


RUN apt-get update && \
    apt-get install -y --fix-missing autoconf pkg-config libssl-dev git libzip-dev zlib1g-dev zip unzip

RUN docker-php-ext-install zip

RUN pecl -q install -f mongodb && docker-php-ext-enable mongodb

COPY . /code
WORKDIR /code

RUN if [[ $(${TRAVIS_PHP_VERSION:0:2}) == "5." ]]; \
    then yes '' | pecl -q install -f mongo; \
    fi
RUN if [[ $(${TRAVIS_PHP_VERSION:0:2})  == "7." ]]; \
    then pecl install -f mongodb; \
    fi

RUN if [[ $(${TRAVIS_PHP_VERSION:0:2}) == "7." ]]; \
    then composer config "platform.ext-mongo" "1.6.16" \
    && composer  require alcaeus/mongo-php-adapter; \
    fi
RUN composer config "platform.ext-mongo" "1.6.16" \
    && composer require "alcaeus/mongo-php-adapter=^1.0.0" \
    && composer update


