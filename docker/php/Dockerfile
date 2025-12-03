FROM php:8.3-fpm

# Instalar extensiones necesarias para Laravel + Filament (intl)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-install pdo_mysql zip gd mbstring intl opcache

# Instalar Composer dentro de la imagen
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar todo el proyecto al contenedor
COPY . .

# Instalar dependencias de Laravel
RUN composer install --no-interaction --prefer-dist

# Permisos para storage y cache
RUN chmod -R 775 storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]
