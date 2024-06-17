ARG PHP_VERSION=8.1
# ARG MAGENTO_VERSION=2.4.6-p6

FROM composer:2 as composer
FROM php:${PHP_VERSION}-fpm

# Install dependencies
RUN apt update && \
    apt install -y --no-install-recommends \
    git \
    && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

WORKDIR /home

RUN git clone https://github.com/magento/magento2.git magento2 && \
    cd magento2 && \
    git checkout 2.4.6-p6

WORKDIR /home/magento2

RUN apt update && \
    apt install -y --no-install-recommends \
    git \
    iputils-ping \
    libcurl4-gnutls-dev \
    libfreetype6-dev \
    libgmp-dev \
    libicu-dev \
    libjpeg-dev \
    libjpeg62-turbo-dev \
    libmagick++-dev \
    libmagickwand-dev \
    libonig-dev \
    libpng-dev \
    libpq-dev \
    libxml2-dev \
    libxslt-dev \
    libzip-dev \
    libwebp-dev \
    default-mysql-client \
    unzip \
    zip \
    zlib1g-dev \
    zsh

RUN docker-php-ext-install bcmath && \
    docker-php-ext-install gd && \
    docker-php-ext-install intl && \
    docker-php-ext-install pdo_mysql && \
    docker-php-ext-install soap && \
    docker-php-ext-install sockets && \
    docker-php-ext-install xsl && \
    docker-php-ext-install zip

COPY --from=composer /usr/bin/composer /usr/bin/composer
# RUN composer install --prefer-dist --no-progress && \
#     composer require alma/alma-php-client mockery/mockery
