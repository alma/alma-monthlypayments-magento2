#!/bin/bash
export ENV="dev"
export DOCKER_BUILDKIT=0
docker-composer up --build
exit
docker-compose exec web /bin/bash
# run following scripts into container

# Maybe useless
# TODO: Fix composer home repo.magento.com credentials => auth.json
#       Works fine with http://user:password@repo.magento.com directly
#       into /var/www/html/composer.json repository section (container context)

composer require alma/alma-php-client
#rm -r app/code/Alma/MonthlyPayments/vendor
/usr/local/bin/install-magento
/usr/local/bin/fix-permissions
/usr/local/bin/install-sampledata

# build magento env as www-data user (todo: move this instructions under /usr/local/bin/script like 3 previous ones)
su www-data
bin/magento cache:clean
bin/magento setup:upgrade
bin/magento setup:di:compile # compile dependence injections
if [[ x$ENV == 'xdev' ]] ; then
    php bin/magento deploy:mode:set developer # refresh compiled code on update
else
    php bin/magento setup:static-content:deploy --force # prod build
fi

bin/magento module:disable Magento_TwoFactorAuth
bin/magento setup:di:compile
