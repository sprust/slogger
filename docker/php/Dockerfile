FROM php:8.2-cli

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
        libssl-dev \
        supervisor

RUN pecl install \
        redis \
        xdebug \
        imagick \
        mongodb \
        zstd \
        zip

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install \
        gd \
        pdo \
        pdo_mysql \
        bcmath \
        exif \
        sockets

RUN docker-php-ext-enable \
        redis \
        xdebug \
        imagick \
        zstd \
        bcmath \
        exif \
        sockets \
        mongodb \
        zip

# Install workdir rules
RUN chown -R www-data:www-data /var/www/

# Install composer
RUN curl -sS https://getcomposer.org/installer  \
    | php -- --install-dir=/usr/local/bin --filename=composer

# Container user & group
ARG USER_ID
ARG GROUP_ID

# Check if user and group doesn't exist before creating
RUN getent group "$GROUP_ID" || addgroup --gid "$GROUP_ID" user
RUN getent passwd "$USER_ID" || adduser --disabled-password --gecos '' --uid "$USER_ID" --gid "$GROUP_ID" user

RUN chmod 777 -R /var/log/supervisor

USER "$USER_ID"
