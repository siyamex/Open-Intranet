FROM php:8.3-apache

# PHP extensions required by OpenIntranet
RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev libpng-dev libjpeg-dev libwebp-dev \
        libzip-dev libsodium-dev unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql curl gd zip sodium \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Sane php.ini
RUN { \
        echo "upload_max_filesize = 25M"; \
        echo "post_max_size = 26M"; \
        echo "memory_limit = 256M"; \
        echo "expose_php = Off"; \
        echo "session.use_strict_mode = 1"; \
    } > /usr/local/etc/php/conf.d/openintranet.ini

# Apache: docroot = public/, allow .htaccess
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && a2enmod rewrite headers

COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/themes /var/www/html/public/assets

EXPOSE 80
