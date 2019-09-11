#!/bin/sh

echo "Establishing connection to database..."

while ! nc -z ${DB_HOST} ${DB_PORT}; do
  sleep 5 # wait for 1/10 of the second before check again
done

echo "Discovering laravel packages..."
composer dump-autoload

if php artisan migrate:status | grep -q 'No migrations found'; then

   echo "No migrations found, migrating and seeding..."
   php artisan migrate --step --seed

else

    if "$REFRESH_DB" = true; then
        echo "Remove logs and cache..."
        php artisan cache:clear
        php artisan config:clear
        php artisan view:clear
        echo "Refreshing database..."
        php artisan migrate:refresh --step --seed
    else
        echo "Migration started for first time"
        php artisan migrate --step --seed
    fi
fi
