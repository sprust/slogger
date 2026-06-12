FROM php:8.4.15-fpm AS php_base

WORKDIR /app

RUN apt-get update \
    && apt-get install -y \
        zsh \
        git \
        curl \
        zip \
        unzip \
        wget \
        libzip-dev \
        libpng-dev \
        libmagickwand-dev \
        libcurl4-openssl-dev \
        pkg-config \
        libssl-dev

# PIE (PHP Installer for Extensions) — replacement for PECL
RUN curl -fSL --connect-timeout 10 --retry 3 \
        https://github.com/php/pie/releases/latest/download/pie.phar \
        -o /usr/local/bin/pie \
    && chmod +x /usr/local/bin/pie

# Third-party extensions via PIE (installs and enables them)
RUN pie install phpredis/phpredis \
    && pie install imagick/imagick \
    && pie install mongodb/mongodb-extension:^1.21 \
    && pie install msgpack/msgpack-php \
    && pie install kjdev/zstd

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Bundled (core) extensions
RUN docker-php-ext-install \
        gd \
        pdo \
        pdo_mysql \
        bcmath \
        exif \
        sockets \
        zip \
        pcntl

# sconcur
ARG BUILD_TIME=1
RUN curl -fSL --connect-timeout 10 --retry 3 -4 \
    https://github.com/sprust/sconcur/releases/latest/download/sconcur.so \
    -o $(php-config --extension-dir)/sconcur.so \
    && echo "extension=sconcur.so" > /usr/local/etc/php/conf.d/docker-php-ext-sconcur.ini

EXPOSE 9000

FROM php_base AS php_app

ARG APP_ENV

RUN if [ "$APP_ENV" = "local" ] ; then \
    pie install xdebug/xdebug:^3.5 \
; fi

# Install composer
RUN curl -sS https://getcomposer.org/installer  \
    | php -- --install-dir=/usr/local/bin --filename=composer

# Container user & group
ARG USER_ID
ARG GROUP_ID

# Check if user and group doesn't exist before creating
RUN getent group "$GROUP_ID" || addgroup --gid "$GROUP_ID" user
RUN getent passwd "$USER_ID" || adduser --disabled-password --gecos '' --uid "$USER_ID" --gid "$GROUP_ID" user

USER "$USER_ID"

FROM php_base AS php_workers

RUN apt-get update && apt-get install -y supervisor

RUN chmod 777 -R /var/log/supervisor

# Container user & group
ARG USER_ID
ARG GROUP_ID

# Check if user and group doesn't exist before creating
RUN getent group "$GROUP_ID" || addgroup --gid "$GROUP_ID" user
RUN getent passwd "$USER_ID" || adduser --disabled-password --gecos '' --uid "$USER_ID" --gid "$GROUP_ID" user

USER "$USER_ID"
