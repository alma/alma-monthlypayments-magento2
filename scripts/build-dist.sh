#!/bin/bash

rm -rf dist/ vendor/
mkdir -p ./dist
zip -9 -r "dist/almapay-monthlypayments-magento2.zip" \
    Api/ \
    Block/ \
    Controller/ \
    Cron/ \
    CustomerData/ \
    etc/ \
    Gateway/ \
    Helpers/ \
    i18n/ \
    Model/ \
    Observer/ \
    Plugin/ \
    Services/ \
    Setup/ \
    Ui/ \
    view/ \
    CHANGELOG.md \
    composer.json \
    LICENSE.txt \
    README.md \
    registration.php
