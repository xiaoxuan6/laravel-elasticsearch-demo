FROM php:7.4-fpm-alpine

RUN docker-php-source extract

RUN curl -L -o /tmp/reids.tar.gz https://codeload.github.com/phpredis/phpredis/tar.gz/5.0.2

RUN cd /tmp \
    && tar -xzf reids.tar.gz \
    && mv phpredis-5.0.2 /usr/src/php/ext/phpredis \
    && docker-php-ext-install phpredis mysqli pdo pdo_mysql
