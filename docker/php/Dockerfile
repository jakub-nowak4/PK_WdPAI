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
RUN { \
      echo 'file_uploads = On'; \
      echo 'upload_max_filesize = 5M'; \
      echo 'post_max_size = 45M'; \
      echo 'max_file_uploads = 8'; \
      echo 'memory_limit = 256M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app