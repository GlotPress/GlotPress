#!/bin/bash

PLUGIN_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." && pwd )"
TESTS_DIR="${PLUGIN_DIR}/tests"

cd "${PLUGIN_DIR}"

if [ $# -lt 3 ]; then
    echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [drop-db]"
    exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
DROP_DB=${6-false}

for PROG in mysqladmin composer
do
    which ${PROG}
    if [ 0 -ne $? ]
    then
        echo "${PROG} not found in path."
        exit 1
    fi
done

function init_env() {
    # Composer install
    cd "${PLUGIN_DIR}"
    composer install
    export PATH="${PLUGIN_DIR}/vendor/bin:${PATH}"

    # Drop database.
    if [ 'true' == ${DROP_DB} ]
    then
        mysqladmin drop ${DB_NAME} --user=${DB_USER} --password="${DB_PASS}"$EXTRA --force
    fi
}

init_env

bash "$TESTS_DIR/bin/install-wp-tests.sh" ${DB_NAME} ${DB_USER} "${DB_PASS}" ${DB_HOST} ${WP_VERSION}

phpunit