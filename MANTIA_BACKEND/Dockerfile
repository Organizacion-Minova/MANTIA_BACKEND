# Etapa 1: instalar dependencias de PHP
FROM php:8.4-cli AS backend

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        bcmath \
        gd \
        zip \
        pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar el proyecto completo
COPY . .

# Instalar dependencias de Composer
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Permisos para Laravel
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Script de inicio: revisa APP_KEY y aplica migraciones.
# sed quita los saltos de línea de Windows por si Git los convirtió.
COPY docker/entrypoint.sh /usr/local/bin/mantia-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/mantia-entrypoint && chmod +x /usr/local/bin/mantia-entrypoint

EXPOSE 8000

ENTRYPOINT ["mantia-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
