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

$SCRIPT_PATH/parts/shared/require_shop_edition_packages.sh -e"${edition}" -v"dev-b-8.0.x"
$SCRIPT_PATH/parts/shared/require_twig_components.sh -e"${edition}" -b"b-8.0.x"
$SCRIPT_PATH/parts/shared/require.sh -n"oxid-esales/developer-tools" -v"dev-b-8.0.x"
$SCRIPT_PATH/parts/shared/require.sh -n"oxid-esales/oxideshop-doctrine-migration-wrapper" -v"dev-b-8.0.x"
$SCRIPT_PATH/parts/shared/require_theme_dev.sh -t"apex" -b"b-8.0.x"

git clone https://github.com/OXID-eSales/oxapi-documentation source/documentation/oxapi-documentation
make docpath=./source/documentation/oxapi-documentation addsphinxservice

make up

docker compose exec php composer update --no-interaction --no-scripts --no-plugins
docker compose exec -T php cp /var/www/vendor/oxid-esales/oxideshop-ce/.env.dist /var/www/.env
docker compose exec php composer update --no-interaction

perl -pi\
  -e 'print "SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=\$1\n\n" if $. == 1'\
  source/source/.htaccess

docker compose exec -T php vendor/bin/oe-console oe:module:install ./
docker compose exec -T php vendor/bin/oe-console oe:database:reset --force

docker compose exec -T php vendor/bin/oe-console oe:module:activate oe_graphql_base
docker compose exec -T php vendor/bin/oe-console oe:theme:activate apex

email=${ADMIN_EMAIL:-noreply@oxid-esales.com}
password=${ADMIN_PASSWORD:-admin}
CONSOLE_PATH=$( [ -e "source/bin/oe-console" ] && echo "bin/oe-console" || echo "vendor/bin/oe-console" )
docker compose exec -T php ${CONSOLE_PATH} oe:admin:create "$email" "$password"

# Register all related project packages git repositories
mkdir -p .idea; mkdir -p source/.idea; cp "${SCRIPT_PATH}/parts/bases/vcs.xml.base" .idea/vcs.xml
perl -pi\
  -e 's#</component>#<mapping directory="\$PROJECT_DIR\$/source" vcs="Git" />\n  </component>#g;'\
  -e 's#</component>#<mapping directory="\$PROJECT_DIR\$/source/vendor/oxid-esales/oxideshop-ce" vcs="Git" />\n  </component>#g;'\
  -e 's#</component>#<mapping directory="\$PROJECT_DIR\$/source/vendor/oxid-esales/oxideshop-pe" vcs="Git" />\n  </component>#g;'\
  -e 's#</component>#<mapping directory="\$PROJECT_DIR\$/source/vendor/oxid-esales/oxideshop-ee" vcs="Git" />\n  </component>#g;'\
  .idea/vcs.xml
