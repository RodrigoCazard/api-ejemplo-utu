FROM composer:2 AS composer_binary

FROM php:8.3-apache

ARG DEBIAN_FRONTEND=noninteractive

# unzip permite que Composer extraiga las librerías descargadas.
# pdo_mysql permite que PDO se conecte a MySQL. rewrite permite que
# Apache envíe rutas como /productos/3 al index.php mediante .htaccess.
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY --from=composer_binary /usr/bin/composer /usr/local/bin/composer
COPY docker/php-apache/app.conf /etc/apache2/conf-available/app.conf

RUN a2enconf app

# El mismo Dockerfile sirve para las dos aplicaciones. Compose decide qué
# carpeta copiar mediante APP_DIR durante la construcción de cada imagen.
ARG APP_DIR
COPY ${APP_DIR}/ /var/www/html/

# Las dependencias se reconstruyen desde composer.lock. vendor no se copia
# desde la computadora del estudiante.
RUN composer install \
        --working-dir=/var/www/html \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
