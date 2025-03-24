#!/bin/bash
docker run --rm -v "$(pwd):/app" -w /app lint:ac --standard=phpcs.xml ./
EXIT_CODE=$?

if [[ $EXIT_CODE -ne 0 ]]; then
    echo "Fix the errors before commit!"
    exit 1
fi
