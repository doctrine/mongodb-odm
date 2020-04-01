ARG TRAVIS_PHP_VERSION="7.2"

#FROM composer:${COMPOSER_VERSION}
FROM php:${TRAVIS_PHP_VERSION}-cli
ARG COMPOSER_VERSION="1.8"
ARG ADAPTER_VERSION="^1.0.0"
ARG DRIVER_VERSION="stable"

#COPY --from=composer /usr/bin/composer /usr/local/bin/composer

ENV DRIVER_VERSION $DRIVER_VERSION
ENV ADAPTER_VERSION $ADAPTER_VERSION
ENV TRAVIS_PHP_VERSION $TRAVIS_PHP_VERSION

RUN apt-get update && \
    apt-get install -y --fix-missing autoconf pkg-config libssl-dev git libzip-dev zlib1g-dev zip unzip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN pecl -q install -f mongodb-$DRIVER_VERSION && docker-php-ext-enable mongodb

COPY . /code
WORKDIR /code
CMD ["/bin/bash", ".travis/setup.sh"]
ENTRYPOINT ["/bin/sh", "-c"]

