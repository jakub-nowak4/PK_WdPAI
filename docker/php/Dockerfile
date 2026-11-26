FROM php:8.3-fpm-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache \
        libjpeg-turbo \
        libpng \
        libwebp \
        libzip \
        postgresql-libs \
        zlib \
    && apk add --no-cache --virtual .build-deps \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        postgresql-dev \
        zlib-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        opcache \
        pdo_pgsql \
        pgsql \
        zip \
    && apk del .build-deps

# Limity uploadu zgodne z regula 9 i docs/api.md 2.5: do 8 zdjec po max 5 MB.
# post_max_size = 8 x 5 MB + zapas na pola formularza i naglowki multipart.
# Nginx ma client_max_body_size rowny post_max_size (docker/nginx/nginx.conf): wiekszego ciala PHP-FPM w ogole nie dostaje (T-110).
# max_file_uploads jest celowo wiekszy niz 8: limit 8 zdjec pilnuje AuctionFormValidator (422 upload.too_many).
# Przy limicie PHP rownym 8 dziewiaty plik znikalby po cichu, zanim trafi do $_FILES.
RUN { \
      echo 'file_uploads = On'; \
      echo 'upload_max_filesize = 5M'; \
      echo 'post_max_size = 45M'; \
      echo 'max_file_uploads = 20'; \
      echo 'memory_limit = 256M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# Bledy trafiaja do logow kontenera (docker compose logs php), nie do przegladarki.
RUN { \
      echo 'display_errors = Off'; \
      echo 'display_startup_errors = Off'; \
      echo 'log_errors = On'; \
      echo 'error_log = /proc/self/fd/2'; \
    } > /usr/local/etc/php/conf.d/errors.ini

WORKDIR /app