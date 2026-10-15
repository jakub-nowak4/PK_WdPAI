FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        libjpeg-turbo \
        libpng \
        libzip \
        postgresql-libs \
        zlib \
    && apk add --no-cache --virtual .build-deps \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        postgresql-dev \
        zlib-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        opcache \
        pdo_pgsql \
        pgsql \
        zip \
    && apk del .build-deps

RUN { \
      echo 'upload_max_filesize = 512M'; \
      echo 'post_max_size = 512M'; \
      echo 'max_file_uploads = 20'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app