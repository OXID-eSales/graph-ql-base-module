#!/bin/bash
# Flags possible:
# -e for shop edition. Possible values: CE/EE

edition='EE'
while getopts e: flag; do
  case "${flag}" in
  e) edition=${OPTARG} ;;
  *) ;;
  esac
done

SCRIPT_PATH=$(dirname ${BASH_SOURCE[0]})
cd $SCRIPT_PATH/../../ || exit

# Prepare services configuration
make setup
make addbasicservices
make file=services/adminer.yml addservice
make file=services/selenium-chrome.yml addservice
make file=services/node.yml addservice

# Configure containers
perl -pi\
  -e 's#error_reporting = .*#error_reporting = E_ALL ^ E_WARNING ^ E_DEPRECATED#g;'\
  containers/php/custom.ini

perl -pi\
  -e 's#/var/www/#/var/www/source/#g;'\
  containers/httpd/project.conf

perl -pi\
  -e 's#PHP_VERSION=.*#PHP_VERSION=8.2#g;'\
  .env

docker compose up --build -d php

docker compose exec -T php git config --global --add safe.directory /var/www

$SCRIPT_PATH/parts/shared/require_shop_edition_packages.sh -e"${edition}" -v"dev-b-7.3.x"
$SCRIPT_PATH/parts/shared/require_twig_components.sh -e"${edition}" -b"b-7.3.x"
$SCRIPT_PATH/parts/shared/require.sh -n"oxid-esales/developer-tools" -v"dev-b-7.3.x"
$SCRIPT_PATH/parts/shared/require.sh -n"oxid-esales/oxideshop-doctrine-migration-wrapper" -v"dev-b-7.3.x"
docker compose exec -T php composer require oxid-esales/apex-theme dev-b-7.3.x

git clone https://github.com/OXID-eSales/oxapi-documentation source/documentation/oxapi-documentation
make docpath=./source/documentation/oxapi-documentation addsphinxservice

docker-compose exec -T -w /var/www php \
       composer config allow-plugins.oxid-esales/oxideshop-composer-plugin true

perl -pi -e '
    BEGIN {
        $inserted = 0;
        $autoload_dev = qq(  "autoload-dev": {\n    "psr-4": {\n      "OxidEsales\\\\EshopCommunity\\\\Tests\\\\": "./vendor/oxid-esales/oxideshop-ce/tests"\n    }\n  },\n);
    }
    if (!$inserted && $_ =~ /"repositories":/) {
        $_ = $autoload_dev . $_;
        $inserted = 1;
    }
' source/composer.json


make up

docker compose exec php composer update --no-interaction

perl -pi\
  -e 'print "SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=\$1\n\n" if $. == 1'\
  source/source/.htaccess

$SCRIPT_PATH/parts/shared/setup_database.sh --no-demodata

docker compose exec -T php vendor/bin/oe-console oe:module:install ./

$SCRIPT_PATH/parts/shared/reset_database.sh --no-demodata

docker compose exec -T php vendor/bin/oe-console oe:module:activate oe_graphql_base
docker compose exec -T php vendor/bin/oe-console oe:theme:activate apex

$SCRIPT_PATH/parts/shared/create_admin.sh
