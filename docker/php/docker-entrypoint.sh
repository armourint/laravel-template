#!/bin/sh
set -e

# fix perms on Laravel's writeable folders
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775        /var/www/html/storage /var/www/html/bootstrap/cache

# exec the original command (php-fpm + supervisord)
exec "$@"