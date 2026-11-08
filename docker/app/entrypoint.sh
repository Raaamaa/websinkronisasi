#!/bin/sh
set -e

if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
    echo "Direktori vendor kosong akibat bind-mount. Menjalankan composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Periksa isi .env: generate hanya jika APP_KEY kosong atau masih placeholder awal
if [ -f "/var/www/html/.env" ]; then
    CURRENT_KEY=$(grep -E '^APP_KEY=' /var/www/html/.env | cut -d '=' -f2- | tr -d ' "\r\n')
    if [ -z "$CURRENT_KEY" ] || [ "$CURRENT_KEY" = "base64:PLACEHOLDER_JALANKAN_PHP_ARTISAN_KEY_GENERATE" ]; then
        echo "APP_KEY kosong atau placeholder. Melakukan generate APP_KEY..."
        php artisan key:generate --force
    fi
fi

exec php artisan serve --host=0.0.0.0 --port=8000

