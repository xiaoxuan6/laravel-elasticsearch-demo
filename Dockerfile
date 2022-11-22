FROM composer:latest as composer

COPY database/ /app/database/
COPY composer.json composer.lock /app/

RUN set -x ; cd /app \
      && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/ \
      && composer clear-cache \
      && composer install \
           --ignore-platform-reqs \
           --no-interaction \
           --no-plugins \
           --no-scripts \
           --prefer-dist

FROM php:7.4-apache-buster as laravel

# Install cron
#RUN apt-get update \
#    && apt-get install -y vim cron libmagickwand-dev imagemagick

ARG LARAVEL_PATH=/var/www/html

COPY --from=composer /app/vendor/ ${LARAVEL_PATH}/vendor/
COPY . ${LARAVEL_PATH}

RUN set -x ; cd ${LARAVEL_PATH} \
      && chmod -R 777 storage \
      && php artisan package:discover

COPY default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
COPY entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint"]

