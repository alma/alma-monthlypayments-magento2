#!/bin/bash
docker run --rm -v "$(pwd):/app" -v "$(pwd)/.php_cs.cache:/app/.php_cs.cache" --entrypoint /composer/vendor/bin/phpcs \
  php-compatibility:ac -p . --standard=PHPCompatibility -s --runtime-set testVersion 7.1-8.4 --ignore=\*/vendor/\*,build/*,node_modules/*,coverage/*
EXIT_CODE=$?

if [[ $EXIT_CODE -ne 0 ]]; then
    echo "Check PHP code compatibility before commit!"
    exit 1
fi
