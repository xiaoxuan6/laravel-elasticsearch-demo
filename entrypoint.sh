#!/usr/bin/env bash

set -e

cd /var/www/html

echo 'generate key'
php artisan key:generate

echo 'migrate'
php artisan migrations

#echo "queue"
#php artisan queue:work --queue={default} --verbose --tries=3 --timeout=90 &
#php artisan queue:work --tries=3 --timeout=90

echo 'http'
exec apache2-foreground
