ARG PHP_VERSION=8.1

FROM composer:2 as composer
FROM php:${PHP_VERSION}-fpm

ARG UID
ARG GID

# Install dependencies
RUN apt update && \
    apt install -y --no-install-recommends \
    git \
    libicu-dev \
    libpng-dev \
    libxml2-dev \
    libxslt-dev \
    libzip-dev \
    zlib1g-dev \
    && \
    # Cleanup APT
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* && \
    # Install PHP extensions
    docker-php-ext-install -j$(nproc) \
    bcmath \
    gd \
    intl \
    pdo_mysql \
    soap \
    sockets \
    xsl \
    zip

RUN pecl install xdebug-3.3.2 \
    && docker-php-ext-enable xdebug

RUN addgroup --gid "$GID" phpuser
RUN adduser --uid "$UID" --gid "$GID" --disabled-password phpuser
USER phpuser
WORKDIR /home/phpuser

ARG MAGENTO_VERSION=2.4.6-p6
RUN git clone --depth 1 --branch $MAGENTO_VERSION https://github.com/magento/magento2.git magento2

WORKDIR /home/phpuser/magento2

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer install --prefer-dist --no-progress && \
    composer require alma/alma-php-client mockery/mockery
