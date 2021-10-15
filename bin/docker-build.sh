#!/bin/bash
export ENV="dev"
export DOCKER_BUILDKIT=0
docker-composer up --build
exit
# TODO: Fix composer home repo.magento.com credentials => auth.json
#       Works fine with http://user:password@repo.magento.com directly
#       into /var/www/html/composer.json repository section (container context)

# run following scripts into container
composer require alma/alma-php-client
#rm -r app/code/Alma/MonthlyPayments/vendor
docker-compose exec web /usr/local/bin/install-magento
docker-compose exec web /usr/local/bin/fix-permissions
docker-compose exec web /usr/local/bin/install-sampledata

# build mangento env
php bin/magento cache:clean
php bin/magento setup:upgrade
php bin/magento setup:di:compile # compile dependence injections
if [[ x$ENV == 'xdev' ]] ; then
    php bin/magento deploy:mode:set developer # refresh compiled code on update
else
    php bin/magento setup:static-content:deploy --force # prod build
fi

bin/magento module:disable Magento_TwoFactorAuth
bin/magento setup:di:compile
