#!/bin/bash
# shellcheck disable=SC2154
# Lower case environment variables are passed from the workflow and used here
# We use a validation loop in init to ensure, they're set
# shellcheck disable=SC2086
# We want install_container_options to count as multiple arguments
set -e

function error() {
    echo -e "\033[0;31m${1}\033[0m"
    exit 1
}

function init() {
    for VAR in install_container_method install_container_options install_container_name \
        install_config_idebug install_is_enterprise; do
        echo -n "Checking, if $VAR is set ..."
        if [ -z ${VAR+x} ]; then
            error "Variable '${VAR}' not set"
        fi
        echo "OK, ${VAR}='${!VAR}'"
    done
    echo -n "Locating oe-console ... "
    cd source || exit 1
    if [ -f 'bin/oe-console' ]; then
        OE_CONSOLE='bin/oe-console'
    else
        if [ -f 'vendor/bin/oe-console' ]; then
        OE_CONSOLE='vendor/bin/oe-console'
        else
            error "Can't find oe-console in bin or vendor/bin!"
        fi
    fi
    echo "OK, using '${OE_CONSOLE}'"
    if [ -z "${OXID_BUILD_DIRECTORY}" ]; then
      echo "OXID_BUILD_DIRECTORY is not set, setting it to /var/www/var/cache/"
      export OXID_BUILD_DIRECTORY="/var/www/var/cache/"
    else
      echo "OXID_BUILD_DIRECTORY is set to '${OXID_BUILD_DIRECTORY}'"
    fi
    if [ ! -d "${OXID_BUILD_DIRECTORY/\/var\/www/source}" ]; then
      echo "Creating '${OXID_BUILD_DIRECTORY}'"

      docker compose "${install_container_method}" -T \
        ${install_container_options} \
        "${install_container_name}" \
        mkdir -p "${OXID_BUILD_DIRECTORY}"

      echo "done with build directory"
    fi
}

init

cp vendor/oxid-esales/oxideshop-ce/.env.dist .env
cat .env


# Run Install Shop
docker compose "${install_container_method}" -T \
    ${install_container_options} \
    "${install_container_name}" \
    ${OE_CONSOLE} oe:database:reset --force

# Activate iDebug
if [ "${install_config_idebug}" == 'true' ]; then
    export OXID_DEBUG_MODE="true"
fi

# Activate theme
docker compose "${install_container_method}" -T \
    ${install_container_options} \
    "${install_container_name}" \
    ${OE_CONSOLE} oe:theme:activate apex

# Output PHP error log
if [ -s data/php/logs/error_log.txt ]; then
    echo -e "\033[0;35mPHP error log\033[0m"
    cat data/php/logs/error_log.txt
fi
exit 0
