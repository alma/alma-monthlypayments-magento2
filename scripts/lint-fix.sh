#!/bin/bash
docker run --rm --entrypoint /composer/vendor/bin/phpcbf -v "$(pwd):/app" -w /app lint:ac --standard=phpcs.xml ./
EXIT_CODE=$?

if [[ $EXIT_CODE -ne 0 ]]; then
    echo "Commit errors fixed by PHPcbf!"
    exit 1
fi
