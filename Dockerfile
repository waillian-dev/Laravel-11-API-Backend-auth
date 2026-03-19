FROM php:8.2-apache

# System dependencies များ သွင်းခြင်း
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libzip-dev

# PHP Extensions (MySQL/TiDB အတွက် pdo_mysql ပါရပါမယ်)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Apache rewrite engine ကို ဖွင့်ခြင်း
RUN a2enmod rewrite

# Apache root ကို public folder သို့ ညွှန်ခြင်း
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY . .

# Composer သွင်းပြီး dependencies များ သွင်းခြင်း
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Permissions ပေးခြင်း
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Render ၏ Dynamic Port ကို သုံးရန် ပြင်ဆင်ခြင်း
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

CMD ["apache2-foreground"]