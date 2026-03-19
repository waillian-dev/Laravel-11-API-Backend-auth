FROM php:8.2-fpm

# System dependencies များ သွင်းခြင်း
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl nginx

# PHP extensions သွင်းခြင်း
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

WORKDIR /var/www
COPY . .

# Composer dependencies သွင်းခြင်း
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Permissions ပေးခြင်း
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Port 80 ကို သုံးမည်
EXPOSE 80

# Start script (Migration run ဖို့နဲ့ Web server တက်ဖို့)
CMD php artisan migrate --force && php artisan config:cache && php artisan serve --host=0.0.0.0 --port=80

# CA Certificates သွင်းပေးခြင်း
RUN apt-get update && apt-get install -y ca-certificates && update-ca-certificates