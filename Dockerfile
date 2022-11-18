FROM php:7.4-fpm-alpine

RUN docker-php-source extract

RUN cd /usr/src/php/ext \
    && docker-php-ext-install mysqli

