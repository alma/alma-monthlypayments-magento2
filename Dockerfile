ARG PHP_VERSION=8.1

FROM composer:2 as composer
FROM php:${PHP_VERSION}-fpm

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

RUN useradd -ms /bin/bash phpuser
USER phpuser
WORKDIR /home/phpuser

ARG MAGENTO_VERSION=2.4.6-p6
RUN git clone https://github.com/magento/magento2.git magento2 && \
    cd magento2 && \
    git checkout $MAGENTO_VERSION

WORKDIR /home/phpuser/magento2

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer install --prefer-dist --no-progress && \
    composer require alma/alma-php-client mockery/mockery
