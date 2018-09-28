FROM php:7.2-alpine3.8

RUN apk add --no-cache make && \
    apk add --no-cache --virtual .php-ext-deps autoconf g++ pcre-dev && \
    pecl channel-update pecl.php.net && \
    pecl install mongodb && \
    docker-php-ext-install bcmath && \
    docker-php-ext-enable mongodb && \
    docker-php-source delete && \
    apk del .php-ext-deps

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer && \
    curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig && \
    php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" && \
    php /tmp/composer-setup.php && \
    mv composer.phar /usr/local/bin/composer && \
    composer global require hirak/prestissimo --no-interaction --no-progress
