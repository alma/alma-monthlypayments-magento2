#!/bin/bash
set -Eeuo pipefail

if [[ ! -e /var/www/html/app/etc/config.php ]] ; then
    /usr/local/bin/mage-install \
    && /usr/local/bin/mage-fix-perms \
    && /usr/local/bin/mage-fixtures \
    && /usr/local/bin/mage-configure

fi

/sbin/my_init
