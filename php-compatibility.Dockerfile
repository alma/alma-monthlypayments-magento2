ARG PHP_IMG_TAG=8.5-alpine
FROM php:${PHP_IMG_TAG} AS production

WORKDIR /composer

RUN apk add --no-cache \
    git \
    unzip \
    libxml2-dev

RUN docker-php-ext-install \
    simplexml \
    xmlwriter

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN php -m | grep -E 'SimpleXML|tokenizer|xmlwriter'

RUN composer init -n \
    --name="alma/php-cs" \
    --description="php-cs" \
    --type="library"

RUN composer config minimum-stability alpha \
 && composer config prefer-stable true \
 && composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true

RUN composer require --no-interaction \
    squizlabs/php_codesniffer:^3.13 \
    phpcompatibility/php-compatibility:10.0.0-alpha2

RUN /composer/vendor/bin/phpcs --config-set installed_paths \
    /composer/vendor/phpcompatibility/php-compatibility

WORKDIR /app

ENTRYPOINT ["/composer/vendor/bin/phpcs"]
CMD ["--version"]