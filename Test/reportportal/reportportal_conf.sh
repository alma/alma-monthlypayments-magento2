#!/usr/bin/env bash

# exit if REPORT_PORTAL_API_KEY or REPORT_PORTAL_ENDPOINT is not set
if [ -z "$REPORT_PORTAL_API_KEY" ] || [ -z "$REPORT_PORTAL_ENDPOINT" ]; then
    echo "Please set REPORT_PORTAL_API_KEY and REPORT_PORTAL_ENDPOINT environment variables"
    exit 1
fi

# Remove /api/v1 or api/v2 from REPORT_PORTAL_ENDPOINT (reportportal/agent-php-PHPUnit requires only host)
export REPORT_PORTAL_HOST=${REPORT_PORTAL_ENDPOINT/\/api\/v[12]/}

# Add secrets values in Test/reportportal_phpunit_conf_template.xml
# Following environment variables are used:
# REPORT_PORTAL_API_KEY
# REPORT_PORTAL_HOST
# PHP_VERSION
envsubst < Test/reportportal/reportportal_phpunit_conf_template.xml > Test/reportportal/reportportal_phpunit_conf.xml

# Add conf for ReportPortal extension in phpunit.ci.xml
# Inserts content of file Test/reportportal_phpunit_conf.xml before </phpunit> end tag in phpunit.ci.xml
sed -i $'/<\/phpunit>/{e cat Test/reportportal/reportportal_phpunit_conf.xml\n}' Test/Unit/phpunit.ci.xml

# Add ReportPortal extension to composer.json
# reportportal/phpunit has no stable version, so we set minimum stability to dev only when running tests
composer config minimum-stability dev
# This is not supported by all versions of PHP, so we need to install it separately
composer require --dev reportportal/phpunit

# Patch reportportal/basic to make it compatible with api/v2
# Patch content:
#   * Replace api/v1 (hardcoded in reportportal/basic) by api/v2
#   * Add launchUuid to finishItem method (otherwise all tests will be marked as "interrupted")
patch vendor/reportportal/basic/src/service/ReportPortalHTTPService.php Test/reportportal/ReportPortalHTTPService.php.patch
