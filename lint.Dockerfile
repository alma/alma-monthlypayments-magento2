ARG PHP_IMG_TAG=7.1-alpine
FROM php:${PHP_IMG_TAG} AS production

WORKDIR /composer

RUN apk add --no-cache composer
RUN composer self-update
RUN composer init -n --name="alma/php-cs" --description="php-cs" --type="library"

RUN composer config --no-interaction --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
RUN composer require phpcsstandards/phpcsutils --no-interaction
RUN composer require phpcsstandards/phpcsextra --no-interaction
RUN composer require squizlabs/php_codesniffer --no-interaction
RUN composer require magento/magento-coding-standard --no-interaction
RUN composer require phpcompatibility/php-compatibility --no-interaction
RUN composer require phpcompatibility/phpcompatibility-paragonie:"*" --no-interaction

RUN /composer/vendor/bin/phpcbf --config-set installed_paths /composer/vendor/phpcsstandards/phpcsutils,/composer/vendor/phpcsstandards/phpcsextra,/composer/vendor/squizlabs/php_codesniffer,/composer/vendor/magento/magento-coding-standard,/composer/vendor/phpcompatibility/php-compatibility,/composer/vendor/phpcompatibility/phpcompatibility-paragonie
RUN /composer/vendor/bin/phpcs --config-set installed_paths /composer/vendor/phpcsstandards/phpcsutils,/composer/vendor/phpcsstandards/phpcsextra,/composer/vendor/squizlabs/php_codesniffer,/composer/vendor/magento/magento-coding-standard,/composer/vendor/phpcompatibility/php-compatibility,/composer/vendor/phpcompatibility/phpcompatibility-paragonie

WORKDIR /appq

ENTRYPOINT ["/composer/vendor/bin/phpcs"]
CMD ["--version"]