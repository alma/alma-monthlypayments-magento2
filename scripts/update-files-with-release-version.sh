#!/usr/bin/env bash

###############################################################################
# README
###############################################################################
# Description :
#     Script to be used in the release process to update the version
#     number in the files
# Usage :
#     ./scripts/update-files-with-release-version.sh <version>
#     (Example: ./scripts/update-files-with-release-version.sh 1.2.3)
###############################################################################

if [ $# -lt 1 ]; then
  echo 1>&2 "$0: Missing argument. Please specify a version number."
  exit 2
fi

####################
# Sanitize version (remove the 'v' prefix if present)
####################
version=`echo ${1#v}`


####################
# Update file ./composer.json
####################
filepath="./composer.json"
# Update "version" field
sed -i -E "s/\"version\": \"[0-9\.]+\"/\"version\": \"$version\"/g" $filepath

####################
# Update file ./etc/module.xml
####################
filepath="./etc/module.xml"
# Update setup_version for module Alma_MonthlyPayments
sed -i -E "s/<module name=\"Alma_MonthlyPayments\" setup_version=\"[0-9\.]+\">/<module name=\"Alma_MonthlyPayments\" setup_version=\"$version\">/g" $filepath
